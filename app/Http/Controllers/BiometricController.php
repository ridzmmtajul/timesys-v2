<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Biometric;
use App\Models\BiometricLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Rats\Zkteco\Lib\ZKTeco;

class BiometricController extends Controller
{
    public function index()
    {
        $devices = Biometric::orderBy('device_name')->get();

        $devices->each(function (Biometric $device) {
            $liveStatus = $this->pingDevice($device);

            if ($device->status !== $liveStatus) {
                $device->update(['status' => $liveStatus]);
            }
        });

        $lastSyncedByDevice = BiometricLog::query()
            ->where('status', 'success')
            ->where('action', '!=', 'disconnect')
            ->whereNotNull('biometric_id')
            ->selectRaw('biometric_id, MAX(created_at) as last_synced_at')
            ->groupBy('biometric_id')
            ->pluck('last_synced_at', 'biometric_id');

        $devices->each(function (Biometric $device) use ($lastSyncedByDevice) {
            $device->last_synced_at = $lastSyncedByDevice->get($device->id);
        });

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    public function logs(Request $request)
    {
        $query = BiometricLog::query()->latest();

        if ($request->filled('biometric_id')) {
            $query->where('biometric_id', $request->biometric_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit((int) $request->input('limit', 10))->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
        ]);

        try {
            $details = $this->readDeviceInfo(
                $validated['ip_address'],
                (int) $request->input('port', 4370)
            );
        } catch (\Throwable $e) {
            $this->logAction($request, null, 'add-device', 'failed', $e->getMessage(), [], $validated['device_name']);
            throw $e;
        }

        $biometric = Biometric::updateOrCreate(
            ['device_name' => $validated['device_name']],
            array_merge($validated, $details)
        );

        $this->logAction($request, $biometric, 'add-device', 'success', 'Biometric device connected successfully.', $details);

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

        $this->logAction($request, $biometric, 'update-device', 'success', 'Biometric device updated successfully.');

        return response()->json([
            'success' => true,
            'message' => 'Biometric device updated successfully.',
            'data' => $biometric->fresh(),
        ]);
    }

    public function destroy(Request $request, Biometric $biometric)
    {
        $this->logAction($request, $biometric, 'delete-device', 'success', 'Biometric device removed.');

        $biometric->delete();

        return response()->json([
            'success' => true,
            'message' => 'Biometric device removed.',
        ]);
    }

    public function connect(Request $request, Biometric $biometric)
    {
        try {
            $details = $this->readDeviceInfo(
                $biometric->ip_address,
                (int) ($biometric->port ?: 4370)
            );

            $biometric->update($details);

            $this->logAction($request, $biometric, 'connect', 'success', 'Biometric device connected successfully.', $details);

            return response()->json([
                'success' => true,
                'message' => 'Biometric device connected successfully.',
                'data' => $biometric->fresh(),
            ]);
        } catch (\Throwable $e) {
            $biometric->update([
                'status' => 'Disconnected',
            ]);

            $this->logAction($request, $biometric, 'connect', 'failed', $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $biometric->fresh(),
            ], 500);
        }
    }


    public function disconnect(Request $request, Biometric $biometric)
    {
        $biometric->update([
            'status' => 'Disconnected',
        ]);

        $this->logAction($request, $biometric, 'disconnect', 'success', 'Biometric device disconnected.');

        return response()->json([
            'success' => true,
            'message' => 'Biometric device disconnected.',
            'data' => $biometric->fresh(),
        ]);
    }

    public function refresh(Request $request, Biometric $biometric)
    {
        try {
            $details = $this->readDeviceInfo(
                $biometric->ip_address,
                (int) ($biometric->port ?: 4370)
            );

            $biometric->update(array_merge($details, [
                'device_name' => $biometric->device_name,
            ]));

            $this->logAction($request, $biometric, 'refresh', 'success', 'Biometric data refreshed.', $details);

            return response()->json([
                'success' => true,
                'message' => 'Biometric data refreshed.',
                'data' => $biometric->fresh(),
            ]);
        } catch (\Throwable $e) {
            $this->logAction($request, $biometric, 'refresh', 'failed', $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function syncTime(Request $request, Biometric $biometric)
    {
        $zk = new ZKTeco($biometric->ip_address, (int) ($biometric->port ?: 4370));

        if (!$zk->connect()) {
            $this->logAction($request, $biometric, 'sync-time', 'failed', 'Unable to connect to biometric device.');

            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to biometric device.'
            ], 500);
        }

        try {
            $now = Carbon::now()->format('Y-m-d H:i:s');
            $zk->setTime($now);

            $this->logAction($request, $biometric, 'sync-time', 'success', 'Biometric device time synced.', ['synced_at' => $now]);

            return response()->json([
                'success' => true,
                'message' => 'Biometric device time synced.',
                'data' => [
                    'synced_at' => $now,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logAction($request, $biometric, 'sync-time', 'failed', $e->getMessage());
            throw $e;
        } finally {
            $zk->disconnect();
        }
    }

    public function downloadLog(Request $request, Biometric $biometric)
    {
        // ZKTeco reads over UDP; a single dropped packet makes the library retry
        // with a 60s socket timeout, which blows past PHP's default 30s limit.
        set_time_limit(180);

        $zk = new ZKTeco($biometric->ip_address, (int) ($biometric->port ?: 4370));

        if (!$zk->connect()) {
            $this->logAction($request, $biometric, 'download-log', 'failed', 'Unable to connect to biometric device.');

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

        $badgeNumbers = collect($logs)->pluck('id')->filter()->unique()->values()->all();

        $userIdsByBadge = DB::connection('sqlsrv2')
            ->table('USERINFO')
            ->whereIn('BADGENUMBER', $badgeNumbers)
            ->pluck('USERID', 'BADGENUMBER');

        $existingKeys = DB::connection('sqlsrv2')
            ->table('CHECKINOUT')
            ->whereIn('USERID', $userIdsByBadge->values()->unique()->all())
            ->where('sn', $serialNumber)
            ->get(['USERID', 'CHECKTIME'])
            ->map(fn ($row) => $row->USERID . '|' . Carbon::parse($row->CHECKTIME)->format('Y-m-d H:i:s'))
            ->flip();

        $newCount = 0;
        $existingCount = 0;

        foreach ($logs as $log) {
            $badgeNumber = $log['id'] ?? null;
            $checkTime   = $log['timestamp'] ?? null;

            if (!$badgeNumber || !$checkTime) {
                continue;
            }

            $userId = $userIdsByBadge[$badgeNumber] ?? null;

            if (!$userId) {
                continue;
            }

            $checkTime = Carbon::parse($checkTime)->format('Y-m-d H:i:s');
            $key       = $userId . '|' . $checkTime;

            if ($existingKeys->has($key)) {
                $existingCount++;
                continue;
            }

            DB::connection('sqlsrv2')->table('CHECKINOUT')->insert([
                'USERID'     => $userId,
                'CHECKTIME'  => $checkTime,
                'CHECKTYPE'  => in_array((int) ($log['state'] ?? 0), [1, 3, 5], true) ? 'O' : 'I',
                'VERIFYCODE' => 0,
                'sn'         => $serialNumber,
                'WorkCode'   => 0,
            ]);

            $existingKeys->put($key, true);
            $newCount++;
        }

        $meta = [
            'total_downloaded' => $newCount,
            'already_exists'   => $existingCount,
            'total_logs'       => count($logs),
        ];

        $this->logAction($request, $biometric, 'download-log', 'success', 'Biometric logs saved to central server.', $meta);

        return response()->json([
            'success' => true,
            'message' => 'Biometric logs saved to central server.',
            'data' => $meta,
        ]);
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

    private function logAction(Request $request, ?Biometric $biometric, string $action, string $status, ?string $message = null, array $meta = [], ?string $deviceName = null): void
    {
        BiometricLog::create([
            'biometric_id' => $biometric?->id,
            'device_name'  => $biometric?->device_name ?? $deviceName,
            'action'       => $action,
            'status'       => $status,
            'message'      => $message,
            'meta'         => $meta,
            'performed_by' => $request->user()?->username,
        ]);
    }

    /**
     * Live-check a device's reachability instead of trusting the last
     * persisted status, which only updates on explicit connect/refresh calls.
     */
    protected function pingDevice(Biometric $device): string
    {
        if (!$device->ip_address) {
            return 'Disconnected';
        }

        try {
            $zk = new ZKTeco($device->ip_address, (int) ($device->port ?: 4370));

            // The library defaults to a 60.5s socket receive timeout, which is
            // fine for a deliberate connect but far too slow to check status
            // for every device on every dashboard load.
            socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 500000]);

            $connected = $zk->connect();

            if ($connected) {
                $zk->disconnect();
            }

            return $connected ? 'Connected' : 'Disconnected';
        } catch (\Throwable $e) {
            return 'Disconnected';
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
