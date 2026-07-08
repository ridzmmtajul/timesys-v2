<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Biometric;
use App\Models\Checkinout;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rats\Zkteco\Lib\ZKTeco;

class BiometricController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Biometric::orderBy('device_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
        ]);

        $details = $this->readDeviceInfo(
            $validated['ip_address'],
            (int) $request->input('port', 4370)
        );

        $biometric = Biometric::updateOrCreate(
            ['device_name' => $validated['device_name']],
            array_merge($validated, $details)
        );

        return response()->json([
            'success' => true,
            'message' => 'Biometric device connected successfully.',
            'data' => $biometric,
        ]);
    }

    public function show(Biometric $biometric)
    {
        return response()->json([
            'success' => true,
            'data' => $biometric,
        ]);
    }

    public function update(Request $request, Biometric $biometric)
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'product_name' => ['nullable', 'string', 'max:255'],
        ]);

        $biometric->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Biometric device updated successfully.',
            'data' => $biometric->fresh(),
        ]);
    }

    public function destroy(Biometric $biometric)
    {
        $biometric->delete();

        return response()->json([
            'success' => true,
            'message' => 'Biometric device removed.',
        ]);
    }

    public function connect(Biometric $biometric)
    {
        try {
            $details = $this->readDeviceInfo(
                $biometric->ip_address,
                (int) ($biometric->port ?: 4370)
            );

            $biometric->update($details);

            return response()->json([
                'success' => true,
                'message' => 'Biometric device connected successfully.',
                'data' => $biometric->fresh(),
            ]);
        } catch (\Throwable $e) {
            $biometric->update([
                'status' => 'Disconnected',
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $biometric->fresh(),
            ], 500);
        }
    }


    public function disconnect(Biometric $biometric)
    {
        $biometric->update([
            'status' => 'Disconnected',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Biometric device disconnected.',
            'data' => $biometric->fresh(),
        ]);
    }

    public function refresh(Biometric $biometric)
    {
        try {
            $details = $this->readDeviceInfo(
                $biometric->ip_address,
                (int) ($biometric->port ?: 4370)
            );

            $biometric->update(array_merge($details, [
                'device_name' => $biometric->device_name,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Biometric data refreshed.',
                'data' => $biometric->fresh(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function syncTime(Biometric $biometric)
    {
        $zk = new ZKTeco($biometric->ip_address, (int) ($biometric->port ?: 4370));

        if (!$zk->connect()) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to biometric device.'
            ], 500);
        }

        try {
            $now = Carbon::now()->format('Y-m-d H:i:s');
            $zk->setTime($now);

            return response()->json([
                'success' => true,
                'message' => 'Biometric device time synced.',
                'data' => [
                    'synced_at' => $now,
                ],
            ]);
        } finally {
            $zk->disconnect();
        }
    }

    public function downloadLog(Biometric $biometric)
    {
        $zk = new ZKTeco($biometric->ip_address, (int) ($biometric->port ?: 4370));

        if (!$zk->connect()) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to biometric device.'
            ], 500);
        }

        try {
            // getAttendance() returns rows shaped [uid, id, state, timestamp, type],
            // where 'id' is the badge number punched on the device.
            $logs = (array) $zk->getAttendance();
        } finally {
            $zk->disconnect();
        }

        $serialNumber = $biometric->serial_number;
        $rows = [];

        foreach ($logs as $log) {
            $badgeNumber = $log['id'] ?? null;
            $checkTime   = $log['timestamp'] ?? null;

            if (!$badgeNumber || !$checkTime) {
                continue;
            }

            $record = Checkinout::firstOrCreate(
                [
                    'badge_number'  => $badgeNumber,
                    'check_time'    => $checkTime,
                    'serial_number' => $serialNumber,
                ],
                ['status' => false]
            );

            $rows[] = [
                'record'    => $record,
                'checktype' => in_array((int) ($log['state'] ?? 0), [1, 3, 5], true) ? 'O' : 'I',
            ];
        }

        $this->postCheckinoutToCentral($rows, $serialNumber);

        $filename = 'biometric-' . Str::slug($biometric->device_name) . '-' . now()->format('Ymd_His') . '.csv';

        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, ['Badge Number', 'Check Time', 'Type', 'Posted to Server']);
        foreach ($rows as $row) {
            fputcsv($csv, [
                $row['record']->badge_number,
                $row['record']->check_time->format('Y-m-d H:i:s'),
                $row['checktype'] === 'O' ? 'Check Out' : 'Check In',
                $row['record']->status ? 'Yes' : 'No',
            ]);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Push newly-downloaded checkinout rows to the central AttendanceLog server
     * (USERINFO/CHECKINOUT). That database is never migrated or updated in place —
     * matching USERIDs are resolved by BADGENUMBER and only missing CHECKINOUT
     * rows are inserted. Local rows are marked posted once confirmed present.
     */
    private function postCheckinoutToCentral(array $rows, ?string $serialNumber): void
    {
        $pending = array_filter($rows, fn ($row) => !$row['record']->status);

        if (empty($pending)) {
            return;
        }

        try {
            $badgeNumbers = collect($pending)->pluck('record.badge_number')->unique()->values()->all();

            $userIdsByBadge = DB::connection('sqlsrv2')
                ->table('USERINFO')
                ->whereIn('BADGENUMBER', $badgeNumbers)
                ->pluck('USERID', 'BADGENUMBER');

            if ($userIdsByBadge->isEmpty()) {
                return;
            }

            $existingKeys = DB::connection('sqlsrv2')
                ->table('CHECKINOUT')
                ->whereIn('USERID', $userIdsByBadge->values()->unique()->all())
                ->where('sn', $serialNumber)
                ->get(['USERID', 'CHECKTIME'])
                ->map(fn ($row) => $row->USERID . '|' . Carbon::parse($row->CHECKTIME)->format('Y-m-d H:i:s'))
                ->flip();

            foreach ($pending as $row) {
                $record = $row['record'];
                $userId = $userIdsByBadge[$record->badge_number] ?? null;

                if (!$userId) {
                    continue;
                }

                $checkTime = $record->check_time->format('Y-m-d H:i:s');
                $key       = $userId . '|' . $checkTime;

                if (!$existingKeys->has($key)) {
                    DB::connection('sqlsrv2')->table('CHECKINOUT')->insert([
                        'USERID'     => $userId,
                        'CHECKTIME'  => $checkTime,
                        'CHECKTYPE'  => $row['checktype'],
                        'VERIFYCODE' => 0,
                        'sn'         => $serialNumber,
                        'WorkCode'   => 0,
                    ]);
                }

                $record->update([
                    'status'      => true,
                    'date_posted' => now(),
                    'posted_by'   => optional(auth()->user())->username ?? 'system',
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function pullLogs()
    {
        try {
            $zk = new ZKTeco('192.168.24.250', 4370);
            if (!$zk->connect()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to connect to device'
                ], 500);
            }

            $logs = $zk->getAttendance();

            $count = 0;

            foreach ($logs as $log) {
                Attendance::updateOrCreate(
                    [
                        'employee_id' => $log['id'],
                        'timestamp' => $log['timestamp']
                    ],
                    [
                        'status' => $log['status'] ?? null,
                    ]
                );

                $count++;
            }

            $zk->disconnect();

            return response()->json([
                'success' => true,
                'records' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function readDeviceInfo(string $ipAddress, int $port = 4370): array
    {
        $zk = new ZKTeco($ipAddress, $port);

        if (!$zk->connect()) {
            throw new \RuntimeException('Unable to connect to biometric device.');
        }

        try {
            $users = (array) $zk->getUser();
            $logs = (array) $zk->getAttendance();

            $adminCount = 0;
            $passwordCount = 0;

            foreach ($users as $user) {
                if (!empty($user['role']) && (int) $user['role'] === 14) {
                    $adminCount++;
                }

                if (!empty($user['password'])) {
                    $passwordCount++;
                }
            }

            return [
                'status' => 'Connected',
                'serial_number' => $zk->serialNumber() ? str_replace('~SerialNumber=', '', trim($zk->serialNumber())) : null,
                'ip_address' => $ipAddress,
                'port' => $port,
                'product_name' => $zk->deviceName() ? str_replace('~DeviceName=', '', trim($zk->deviceName())) : null,
                'user_count' => count($users),
                'admin_count' => $adminCount,
                'password' => (string) $passwordCount,
                'log_count' => count($logs),
            ];
        } finally {
            $zk->disconnect();
        }
    }
}
