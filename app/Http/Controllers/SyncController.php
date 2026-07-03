<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Office;
use App\Models\OfficeDivision;
use App\Models\Position;
use App\Models\PostNumber;
use App\Models\Schedule;
use App\Models\ScheduleType;
use App\Models\SyncLog;
use App\Models\WorkSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SyncController extends Controller
{
    // ── LOCAL INSTANCE ──────────────────────────────────────────────────────
    // Reads local records that haven't been sent yet and pushes them to the central server.

    public function pushEmployees()
    {
        return $this->pushModule(
            Employee::class,
            Employee::whereNull('synced_at')->with(['office', 'employmentType', 'position', 'officeDivision']),
            '/api/sync/receive-employees',
            'employees',
            fn ($emp) => [
                'employee_no'          => $emp->employee_no,
                'first_name'           => $emp->first_name,
                'middle_name'          => $emp->middle_name,
                'last_name'            => $emp->last_name,
                'name_ext'             => $emp->name_ext,
                'gender'               => $emp->gender,
                'contact_no'           => $emp->contact_no,
                'job_title'            => $emp->job_title,
                'is_active'            => $emp->is_active,
                'image'                => $emp->image,
                'signature'            => $emp->signature,
                'office_name'          => $emp->office?->name,
                'office_code'          => $emp->office?->code,
                'employment_type_name' => $emp->employmentType?->name,
                'position_name'        => $emp->position?->name,
                'office_division_name' => $emp->officeDivision?->name,
                'office_division_code' => $emp->officeDivision?->code,
            ]
        );
    }

    public function pushOffices()
    {
        return $this->pushModule(
            Office::class,
            Office::whereNull('synced_at'),
            '/api/sync/receive-offices',
            'offices',
            fn ($office) => [
                'code'        => $office->code,
                'name'        => $office->name,
                'description' => $office->description,
                'prefix'      => $office->prefix,
            ]
        );
    }

    public function pushOfficeDivisions()
    {
        return $this->pushModule(
            OfficeDivision::class,
            OfficeDivision::whereNull('synced_at')->with('office'),
            '/api/sync/receive-office-divisions',
            'office_divisions',
            fn ($division) => [
                'code'        => $division->code,
                'name'        => $division->name,
                'description' => $division->description,
                'office_name' => $division->office?->name,
                'office_code' => $division->office?->code,
            ]
        );
    }

    public function pushWorkSchedules()
    {
        return $this->pushModule(
            WorkSchedule::class,
            WorkSchedule::whereNull('synced_at')->with(['employee', 'schedule', 'scheduleType']),
            '/api/sync/receive-work-schedules',
            'work_schedules',
            fn ($ws) => [
                'employee_no'        => $ws->employee?->employee_no,
                'schedule_name'      => $ws->schedule?->name,
                'schedule_type_name' => $ws->scheduleType?->name,
                'timein_AM'          => $ws->timein_AM,
                'timeout_AM'         => $ws->timeout_AM,
                'timein_PM'          => $ws->timein_PM,
                'timeout_PM'         => $ws->timeout_PM,
                'from_date'          => $ws->from_date,
                'to_date'            => $ws->to_date,
                'is_others'          => $ws->is_others,
                'schedule_for'       => $ws->schedule_for,
                'days'               => $ws->days,
                'no_lunch_gap'       => $ws->no_lunch_gap,
            ]
        );
    }

    public function pushAttendances()
    {
        return $this->pushModule(
            Attendance::class,
            Attendance::whereNull('synced_at')->with(['employee', 'postNumber']),
            '/api/sync/receive-attendances',
            'attendances',
            fn ($att) => [
                'employee_no'  => $att->employee?->employee_no,
                'check_time'   => optional($att->check_time)->format('Y-m-d H:i:s'),
                'serial_no'    => $att->serial_no,
                'post_no_name' => $att->postNumber?->name,
                'void'         => $att->void,
            ]
        );
    }

    public function pendingCounts()
    {
        return response()->json([
            'employees'        => Employee::whereNull('synced_at')->count(),
            'offices'          => Office::whereNull('synced_at')->count(),
            'office_divisions' => OfficeDivision::whereNull('synced_at')->count(),
            'work_schedules'   => WorkSchedule::whereNull('synced_at')->count(),
            'attendances'      => Attendance::whereNull('synced_at')->count(),
        ]);
    }

    /**
     * Shared push routine: sends only records that have never been synced
     * (synced_at is null) and marks them synced on success. The sync_logs
     * trail is written on the receiving (central) side only, once the
     * payload has actually been received and processed.
     */
    private function pushModule(string $modelClass, $query, string $endpoint, string $payloadKey, callable $mapper): JsonResponse
    {
        $centralUrl = config('services.sync.central_url');
        $apiKey     = config('services.sync.api_key');

        if (!$centralUrl || !$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Sync not configured. Set CENTRAL_SERVER_URL and SYNC_API_KEY in .env.',
            ], 500);
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success'  => true,
                'synced'   => 0,
                'existing' => 0,
                'skipped'  => 0,
                'message'  => 'Nothing to sync. All records are already synced to the central server.',
                'errors'   => [],
            ]);
        }

        $payload = $records->map($mapper)->values()->all();

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post(rtrim($centralUrl, '/') . $endpoint, [$payloadKey => $payload]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to reach the central server.',
            ], 502);
        }

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Central server returned an error.',
                'details' => $response->json(),
            ], 502);
        }

        $result = $response->json();

        $modelClass::whereIn('id', $records->pluck('id'))->update(['synced_at' => now()]);

        return response()->json($result);
    }

    // ── SERVER INSTANCE ─────────────────────────────────────────────────────
    // Receives a payload from a local instance and upserts into the central DB.

    public function receiveEmployees(Request $request)
    {
        $employees = $request->input('employees', []);
        $synced    = 0;
        $skipped   = 0;
        $existing  = 0;
        $errors    = [];
        $seenNos   = [];
        $seenNames = [];

        foreach ($employees as $data) {
            try {
                $fullName = mb_strtolower(trim(($data['first_name'] ?? '') . '|' . ($data['middle_name'] ?? '') . '|' . ($data['last_name'] ?? '')));

                if (in_array($data['employee_no'], $seenNos, true)) {
                    $skipped++;
                    $errors[] = "Employee {$data['employee_no']}: duplicate employee_no in payload, skipped.";
                    continue;
                }

                if (in_array($fullName, $seenNames, true)) {
                    $skipped++;
                    $errors[] = "Employee {$data['employee_no']}: duplicate full name in payload, skipped.";
                    continue;
                }

                if (Employee::where('employee_no', $data['employee_no'])->exists()) {
                    $existing++;
                    continue;
                }

                if (Employee::where('first_name', $data['first_name'] ?? null)
                    ->where('middle_name', $data['middle_name'] ?? null)
                    ->where('last_name', $data['last_name'] ?? null)
                    ->exists()) {
                    $existing++;
                    continue;
                }

                $seenNos[]   = $data['employee_no'];
                $seenNames[] = $fullName;

                $officeId = $this->resolveOffice($data['office_name'] ?? null, $data['office_code'] ?? null);

                if (!$officeId) {
                    $skipped++;
                    $errors[] = "Employee {$data['employee_no']}: office name is missing, skipped.";
                    continue;
                }

                Employee::create([
                    'employee_no'          => $data['employee_no'],
                    'first_name'           => $data['first_name'],
                    'middle_name'          => $data['middle_name'] ?? null,
                    'last_name'            => $data['last_name'],
                    'name_ext'             => $data['name_ext'] ?? null,
                    'gender'               => $data['gender'] ?? null,
                    'contact_no'           => $data['contact_no'] ?? null,
                    'job_title'            => $data['job_title'] ?? null,
                    'is_active'            => $data['is_active'] ?? true,
                    'image'                => $data['image'] ?? null,
                    'signature'            => $data['signature'] ?? null,
                    'office_id'            => $officeId,
                    'employment_type_id'   => $this->resolveEmploymentType($data['employment_type_name'] ?? null),
                    'position_id'          => $this->resolvePosition($data['position_name'] ?? null),
                    'office_division_id'   => $this->resolveOfficeDivision($data['office_division_name'] ?? null, $data['office_division_code'] ?? null, $officeId),
                ]);

                $synced++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Employee {$data['employee_no']}: {$e->getMessage()}";
            }
        }

        return $this->respondReceive('employees', $synced, $existing, $skipped, $errors, 'employee(s)');
    }

    public function receiveOffices(Request $request)
    {
        $offices  = $request->input('offices', []);
        $synced   = 0;
        $existing = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($offices as $data) {
            try {
                if (empty($data['code']) && empty($data['name'])) {
                    $skipped++;
                    $errors[] = 'Office is missing code and name, skipped.';
                    continue;
                }

                $exists = Office::where('code', $data['code'] ?? null)
                    ->orWhere('name', $data['name'] ?? null)
                    ->exists();

                if ($exists) {
                    $existing++;
                    continue;
                }

                Office::create([
                    'code'                => $data['code'] ?? $data['name'],
                    'name'                => $data['name'],
                    'description'         => $data['description'] ?? null,
                    'prefix'              => $data['prefix'] ?? null,
                    'latest_employee_no'  => ($data['prefix'] ?? null) ? 0 : null,
                ]);

                $synced++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Office {$data['code']}: {$e->getMessage()}";
            }
        }

        return $this->respondReceive('offices', $synced, $existing, $skipped, $errors, 'office(s)');
    }

    public function receiveOfficeDivisions(Request $request)
    {
        $divisions = $request->input('office_divisions', []);
        $synced    = 0;
        $existing  = 0;
        $skipped   = 0;
        $errors    = [];

        foreach ($divisions as $data) {
            try {
                $officeId = $this->resolveOffice($data['office_name'] ?? null, $data['office_code'] ?? null);

                if (!$officeId) {
                    $skipped++;
                    $errors[] = "Division {$data['name']}: office is missing, skipped.";
                    continue;
                }

                $exists = OfficeDivision::where('office_id', $officeId)
                    ->where(function ($q) use ($data) {
                        $q->where('code', $data['code'] ?? null)
                          ->orWhere('name', $data['name'] ?? null);
                    })
                    ->exists();

                if ($exists) {
                    $existing++;
                    continue;
                }

                OfficeDivision::create([
                    'code'        => $data['code'] ?? $data['name'],
                    'name'        => $data['name'],
                    'description' => $data['description'] ?? null,
                    'office_id'   => $officeId,
                ]);

                $synced++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Division {$data['name']}: {$e->getMessage()}";
            }
        }

        return $this->respondReceive('office_divisions', $synced, $existing, $skipped, $errors, 'division(s)');
    }

    public function receiveWorkSchedules(Request $request)
    {
        $items    = $request->input('work_schedules', []);
        $synced   = 0;
        $existing = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($items as $data) {
            try {
                $employeeId = Employee::where('employee_no', $data['employee_no'] ?? null)->value('id');

                if (!$employeeId) {
                    $skipped++;
                    $errors[] = "Work schedule: employee {$data['employee_no']} not found on central server, skipped.";
                    continue;
                }

                $exists = WorkSchedule::where('employee_id', $employeeId)
                    ->where('schedule_for', $data['schedule_for'] ?? null)
                    ->where('from_date', $data['from_date'] ?? null)
                    ->where('to_date', $data['to_date'] ?? null)
                    ->exists();

                if ($exists) {
                    $existing++;
                    continue;
                }

                WorkSchedule::create([
                    'employee_id'      => $employeeId,
                    'schedule_id'      => $this->resolveSchedule($data['schedule_name'] ?? null),
                    'schedule_type_id' => $this->resolveScheduleType($data['schedule_type_name'] ?? null),
                    'timein_AM'        => $data['timein_AM'] ?? null,
                    'timeout_AM'       => $data['timeout_AM'] ?? null,
                    'timein_PM'        => $data['timein_PM'] ?? null,
                    'timeout_PM'       => $data['timeout_PM'] ?? null,
                    'from_date'        => $data['from_date'] ?? null,
                    'to_date'          => $data['to_date'] ?? null,
                    'is_others'        => $data['is_others'] ?? false,
                    'schedule_for'     => $data['schedule_for'] ?? null,
                    'days'             => $data['days'] ?? [],
                    'no_lunch_gap'     => $data['no_lunch_gap'] ?? false,
                ]);

                $synced++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Work schedule for {$data['employee_no']}: {$e->getMessage()}";
            }
        }

        return $this->respondReceive('work_schedules', $synced, $existing, $skipped, $errors, 'work schedule(s)');
    }

    public function receiveAttendances(Request $request)
    {
        $items    = $request->input('attendances', []);
        $synced   = 0;
        $existing = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($items as $data) {
            try {
                $employeeId = Employee::where('employee_no', $data['employee_no'] ?? null)->value('id');

                if (!$employeeId) {
                    $skipped++;
                    $errors[] = "Attendance: employee {$data['employee_no']} not found on central server, skipped.";
                    continue;
                }

                $postNo = $this->resolvePostNumber($data['post_no_name'] ?? null);

                $exists = Attendance::where('check_time', $data['check_time'] ?? null)
                    ->where('employee_id', $employeeId)
                    ->where('post_no', $postNo)
                    ->exists();

                if ($exists) {
                    $existing++;
                    continue;
                }

                Attendance::create([
                    'check_time'  => $data['check_time'] ?? null,
                    'employee_id' => $employeeId,
                    'serial_no'   => $data['serial_no'] ?? null,
                    'post_no'     => $postNo,
                    'void'        => $data['void'] ?? false,
                ]);

                $synced++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Attendance for {$data['employee_no']}: {$e->getMessage()}";
            }
        }

        return $this->respondReceive('attendances', $synced, $existing, $skipped, $errors, 'attendance record(s)');
    }

    // ── SYNC LOGS ────────────────────────────────────────────────────────────

    public function logs(Request $request)
    {
        $query = SyncLog::query()->latest();

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        return response()->json($query->paginate(15));
    }

    // ── HELPERS ──────────────────────────────────────────────────────────────

    private function respondReceive(string $module, int $synced, int $existing, int $skipped, array $errors, string $label): JsonResponse
    {
        $status = 'success';
        if (!empty($errors)) {
            $status = ($synced > 0 || $existing > 0) ? 'partial' : 'failed';
        }

        $message = "{$synced} {$label} synced, {$existing} already exist, {$skipped} skipped.";

        $this->recordLog($module, 'receive', $status, [
            'total'    => $synced + $existing + $skipped,
            'synced'   => $synced,
            'existing' => $existing,
            'skipped'  => $skipped,
        ], $errors, $message);

        return response()->json([
            'success'  => $status !== 'failed',
            'synced'   => $synced,
            'existing' => $existing,
            'skipped'  => $skipped,
            'message'  => $message,
            'errors'   => $errors,
        ]);
    }

    private function recordLog(string $module, string $direction, string $status, array $counts, array $errors = [], ?string $message = null): void
    {
        SyncLog::create([
            'module'         => $module,
            'direction'      => $direction,
            'status'         => $status,
            'total_records'  => $counts['total'] ?? 0,
            'synced_count'   => $counts['synced'] ?? 0,
            'existing_count' => $counts['existing'] ?? 0,
            'skipped_count'  => $counts['skipped'] ?? 0,
            'errors'         => $errors,
            'message'        => $message,
        ]);
    }

    private function resolveOffice(?string $name, ?string $code): ?int
    {
        if (!$name) return null;
        return Office::firstOrCreate(['name' => $name], ['code' => $code ?? $name])->id;
    }

    private function resolveEmploymentType(?string $name): ?int
    {
        if (!$name) return null;
        return EmploymentType::firstOrCreate(['name' => $name])->id;
    }

    private function resolvePosition(?string $name): ?int
    {
        if (!$name) return null;
        return Position::firstOrCreate(['name' => $name])->id;
    }

    private function resolveOfficeDivision(?string $name, ?string $code, int $officeId): ?int
    {
        if (!$name) return null;
        return OfficeDivision::firstOrCreate(
            ['name' => $name, 'office_id' => $officeId],
            ['code' => $code ?? $name]
        )->id;
    }

    private function resolveSchedule(?string $name): ?int
    {
        if (!$name) return null;
        return Schedule::firstOrCreate(['name' => $name])->id;
    }

    private function resolveScheduleType(?string $name): ?int
    {
        if (!$name) return null;
        return ScheduleType::firstOrCreate(['name' => $name])->id;
    }

    private function resolvePostNumber(?string $name): ?int
    {
        if (!$name) return null;
        return PostNumber::firstOrCreate(['name' => $name])->id;
    }
}
