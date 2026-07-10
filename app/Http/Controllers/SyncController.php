<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BiometricLocation;
use App\Models\Employee;
use App\Models\EmployeeOffice;
use App\Models\EmploymentType;
use App\Models\Office;
use App\Models\OfficeDivision;
use App\Models\Position;
use App\Models\Schedule;
use App\Models\ScheduleType;
use App\Models\SyncLog;
use App\Models\WorkSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
                'from_date'          => $this->nullIfZeroDate($ws->from_date),
                'to_date'            => $this->nullIfZeroDate($ws->to_date),
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
            Attendance::whereNull('synced_at')->with(['employee']),
            '/api/sync/receive-attendances',
            'attendances',
            fn ($att) => [
                'employee_no' => $att->employee?->employee_no,
                'check_time'  => optional($att->check_time)->format('Y-m-d H:i:s'),
                'serial_no'   => $att->serial_no,
                'post_no'     => $att->post_no,
                'void'        => $att->void,
            ]
        );
    }

    public function pendingCounts()
    {
        $isCentral = !config('services.sync.central_url');

        return response()->json([
            'is_central'       => $isCentral,
            'employees'        => $isCentral ? Employee::count() : Employee::whereNull('synced_at')->count(),
            'offices'          => Office::whereNull('synced_at')->count(),
            'office_divisions' => OfficeDivision::whereNull('synced_at')->count(),
            'work_schedules'   => WorkSchedule::whereNull('synced_at')->count(),
            'attendances'      => $isCentral ? Attendance::count() : Attendance::whereNull('synced_at')->count(),
        ]);
    }

    /**
     * The canonical list of biometric device labels a local instance can push
     * as synced_from - see employee_offices, where synced_from scopes
     * employee_no uniqueness. Called by the local instance's dashboard so
     * operators pick from this list instead of free-typing a value that could
     * drift from what's already on file.
     */
    public function biometricLocations(): JsonResponse
    {
        return response()->json([
            'data' => BiometricLocation::orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Shared push routine: sends only records that have never been synced
     * (synced_at is null) and marks them synced on success. A sync_logs
     * entry is written on the pushing (local) side for the attempt, and
     * separately on the receiving (central) side once the payload has
     * actually been received and processed.
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

        $syncedFrom = config('app.name');
        $payload    = $records->map($mapper)->values()->all();

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post(rtrim($centralUrl, '/') . $endpoint, [$payloadKey => $payload, 'synced_from' => $syncedFrom]);
        } catch (\Throwable $e) {
            $message = 'Unable to reach the central server.';

            $this->recordLog($payloadKey, 'push', 'failed', ['total' => $records->count()], [$message], $message, $syncedFrom);

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 502);
        }

        if ($response->failed()) {
            $message = 'Central server returned an error.';

            $this->recordLog($payloadKey, 'push', 'failed', ['total' => $records->count()], [$message], $message, $syncedFrom);

            return response()->json([
                'success' => false,
                'message' => $message,
                'details' => $response->json(),
            ], 502);
        }

        $result = $response->json();

        $modelClass::whereIn('id', $records->pluck('id'))->update(['synced_at' => now()]);

        $synced   = $result['synced'] ?? 0;
        $existing = $result['existing'] ?? 0;
        $skipped  = $result['skipped'] ?? 0;
        $errors   = $result['errors'] ?? [];

        $status = 'success';
        if (!($result['success'] ?? true)) {
            $status = 'failed';
        } elseif (!empty($errors)) {
            $status = ($synced > 0 || $existing > 0) ? 'partial' : 'failed';
        }

        $this->recordLog($payloadKey, 'push', $status, [
            'total'    => $synced + $existing + $skipped,
            'synced'   => $synced,
            'existing' => $existing,
            'skipped'  => $skipped,
        ], $errors, $result['message'] ?? null, $syncedFrom);

        return response()->json($result);
    }

    // ── SERVER INSTANCE ─────────────────────────────────────────────────────
    // Receives a payload from a local instance and upserts into the central DB.

    /**
     * An employee can hold a different employee_no on each biometric device
     * they're enrolled in (see employee_offices), so full name is the actual
     * identity key here - employee_no is only unique within a single device's
     * own enrollment list (identified by synced_from), not globally.
     */
    public function receiveEmployees(Request $request)
    {
        $employees   = $request->input('employees', []);
        $syncedFrom  = $request->input('synced_from');
        $synced      = 0;
        $skipped     = 0;
        $existing    = 0;
        $errors      = [];
        $seenEnrollments = [];
        $skippedIndexes  = [];

        foreach ($employees as $idx => $data) {
            $label = "{$data['employee_no']} ({$data['first_name']} {$data['last_name']})";

            try {
                $officeId = $this->resolveOffice($data['office_name'] ?? null, $data['office_code'] ?? null);

                if (!$officeId) {
                    $skipped++;
                    $skippedIndexes[] = $idx;
                    $errors[] = "Employee {$label}: office name is missing, skipped.";
                    continue;
                }

                $enrollmentKey = $syncedFrom . '|' . $data['employee_no'];

                if (in_array($enrollmentKey, $seenEnrollments, true)) {
                    $skipped++;
                    $skippedIndexes[] = $idx;
                    $errors[] = "Employee {$label}: duplicate employee_no for this device in payload, skipped.";
                    continue;
                }
                $seenEnrollments[] = $enrollmentKey;

                $employee = $this->findMatchingEmployee($data);

                if (!$employee) {
                    $employee = Employee::create([
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
                        'synced_from'          => $syncedFrom,
                    ]);
                }

                $enrollmentExists = EmployeeOffice::where('synced_from', $syncedFrom)
                    ->where('employee_no', $data['employee_no'])
                    ->exists();

                if ($enrollmentExists) {
                    $existing++;
                    continue;
                }

                EmployeeOffice::create([
                    'employee_id' => $employee->id,
                    'office_id'   => $officeId,
                    'employee_no' => $data['employee_no'],
                    'synced_from' => $syncedFrom,
                ]);

                $synced++;
            } catch (\Throwable $e) {
                $skipped++;
                $skippedIndexes[] = $idx;
                $errors[] = "Employee {$label}: {$e->getMessage()}";
            }
        }

        return $this->respondReceive('employees', $synced, $existing, $skipped, $errors, 'employee(s)', $syncedFrom, $skippedIndexes, $request->boolean('is_final_chunk', true));
    }

    public function receiveOffices(Request $request)
    {
        $offices        = $request->input('offices', []);
        $synced         = 0;
        $existing       = 0;
        $skipped        = 0;
        $errors         = [];
        $skippedIndexes = [];

        foreach ($offices as $idx => $data) {
            try {
                if (empty($data['code']) && empty($data['name'])) {
                    $skipped++;
                    $skippedIndexes[] = $idx;
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
                $skippedIndexes[] = $idx;
                $errors[] = "Office {$data['code']}: {$e->getMessage()}";
            }
        }

        return $this->respondReceive('offices', $synced, $existing, $skipped, $errors, 'office(s)', null, $skippedIndexes, $request->boolean('is_final_chunk', true));
    }

    public function receiveOfficeDivisions(Request $request)
    {
        $divisions      = $request->input('office_divisions', []);
        $synced         = 0;
        $existing       = 0;
        $skipped        = 0;
        $errors         = [];
        $skippedIndexes = [];

        foreach ($divisions as $idx => $data) {
            try {
                $officeId = $this->resolveOffice($data['office_name'] ?? null, $data['office_code'] ?? null);

                if (!$officeId) {
                    $skipped++;
                    $skippedIndexes[] = $idx;
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
                $skippedIndexes[] = $idx;
                $errors[] = "Division {$data['name']}: {$e->getMessage()}";
            }
        }

        return $this->respondReceive('office_divisions', $synced, $existing, $skipped, $errors, 'division(s)', null, $skippedIndexes, $request->boolean('is_final_chunk', true));
    }

    public function receiveWorkSchedules(Request $request)
    {
        $items          = $request->input('work_schedules', []);
        $syncedFrom     = $request->input('synced_from');
        $synced         = 0;
        $existing       = 0;
        $skipped        = 0;
        $errors         = [];
        $skippedIndexes = [];

        foreach ($items as $idx => $data) {
            try {
                $employeeId = $this->resolveEmployeeIdByEnrollment($data['employee_no'] ?? null, $syncedFrom);

                if (!$employeeId) {
                    $skipped++;
                    $skippedIndexes[] = $idx;
                    $label    = trim(($data['employee_no'] ?? 'unknown no.') . ' (' . ($data['employee_name'] ?? 'unknown name') . ')');
                    $sourceId = $data['source_id'] ?? '?';
                    $errors[] = "Work schedule (id {$sourceId} on source): employee {$label} not found on central server, skipped.";
                    continue;
                }

                $fromDate = $this->nullIfZeroDate($data['from_date'] ?? null);
                $toDate   = $this->nullIfZeroDate($data['to_date'] ?? null);

                $exists = WorkSchedule::where('employee_id', $employeeId)
                    ->where('schedule_for', $data['schedule_for'] ?? null)
                    ->where('from_date', $fromDate)
                    ->where('to_date', $toDate)
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
                    'from_date'        => $fromDate,
                    'to_date'          => $toDate,
                    'is_others'        => $data['is_others'] ?? false,
                    'schedule_for'     => $data['schedule_for'] ?? null,
                    'days'             => $data['days'] ?? [],
                    'no_lunch_gap'     => $data['no_lunch_gap'] ?? false,
                    'synced_from'      => $syncedFrom,
                ]);

                $synced++;
            } catch (\Throwable $e) {
                $skipped++;
                $skippedIndexes[] = $idx;
                $errors[] = "Work schedule (id " . ($data['source_id'] ?? '?') . " on source) for {$data['employee_no']}: {$e->getMessage()}";
            }
        }

        return $this->respondReceive('work_schedules', $synced, $existing, $skipped, $errors, 'work schedule(s)', $syncedFrom, $skippedIndexes, $request->boolean('is_final_chunk', true));
    }

    public function receiveAttendances(Request $request)
    {
        $items          = $request->input('attendances', []);
        $syncedFrom     = $request->input('synced_from');
        $synced         = 0;
        $existing       = 0;
        $skipped        = 0;
        $errors         = [];
        $skippedIndexes = [];

        foreach ($items as $idx => $data) {
            try {
                $employeeId = $this->resolveEmployeeIdByEnrollment($data['employee_no'] ?? null, $syncedFrom);

                if (!$employeeId) {
                    $skipped++;
                    $skippedIndexes[] = $idx;
                    $errors[] = "Attendance: employee {$data['employee_no']} not found on central server, skipped.";
                    continue;
                }

                $postNo = $data['post_no'] ?? null;

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
                    'synced_from' => $syncedFrom,
                ]);

                $synced++;
            } catch (\Throwable $e) {
                $skipped++;
                $skippedIndexes[] = $idx;
                $errors[] = "Attendance for {$data['employee_no']}: {$e->getMessage()}";
            }
        }

        return $this->respondReceive('attendances', $synced, $existing, $skipped, $errors, 'attendance record(s)', $syncedFrom, $skippedIndexes, $request->boolean('is_final_chunk', true));
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

    private function respondReceive(string $module, int $synced, int $existing, int $skipped, array $errors, string $label, ?string $syncedFrom = null, array $skippedIndexes = [], bool $isFinal = true): JsonResponse
    {
        $status = 'success';
        if (!empty($errors)) {
            $status = ($synced > 0 || $existing > 0) ? 'partial' : 'failed';
        }

        $message = "{$synced} {$label} synced, {$existing} already exist, {$skipped} skipped.";

        $this->recordAggregatedLog($module, 'receive', $syncedFrom, $isFinal, [
            'synced'   => $synced,
            'existing' => $existing,
            'skipped'  => $skipped,
        ], $errors, $label);

        return response()->json([
            'success'         => $status !== 'failed',
            'synced'          => $synced,
            'existing'        => $existing,
            'skipped'         => $skipped,
            'message'         => $message,
            'errors'          => $errors,
            'skipped_indexes' => $skippedIndexes,
        ]);
    }

    private function recordLog(string $module, string $direction, string $status, array $counts, array $errors = [], ?string $message = null, ?string $syncedFrom = null): void
    {
        SyncLog::create([
            'module'         => $module,
            'synced_from'    => $syncedFrom,
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

    /**
     * The sender chunks large payloads into several requests, each flagged
     * with is_final_chunk. Rather than writing a sync_logs row per chunk,
     * this accumulates counts/errors in cache (keyed per module+source) and
     * only writes the row once the final chunk arrives, so one logical sync
     * run produces exactly one log entry with the full error/detail list.
     */
    private function recordAggregatedLog(string $module, string $direction, ?string $syncedFrom, bool $isFinal, array $counts, array $errors, string $label): void
    {
        $key      = "sync_progress:{$direction}:{$module}:" . ($syncedFrom ?? 'default');
        $progress = Cache::get($key, ['synced' => 0, 'existing' => 0, 'skipped' => 0, 'errors' => []]);

        $progress['synced']   += $counts['synced'] ?? 0;
        $progress['existing'] += $counts['existing'] ?? 0;
        $progress['skipped']  += $counts['skipped'] ?? 0;
        $progress['errors']    = array_merge($progress['errors'], $errors);

        if (!$isFinal) {
            Cache::put($key, $progress, now()->addHour());
            return;
        }

        Cache::forget($key);

        $status = 'success';
        if (!empty($progress['errors'])) {
            $status = ($progress['synced'] > 0 || $progress['existing'] > 0) ? 'partial' : 'failed';
        }

        $message = "{$progress['synced']} {$label} synced, {$progress['existing']} already exist, {$progress['skipped']} skipped.";

        $this->recordLog($module, $direction, $status, [
            'total'    => $progress['synced'] + $progress['existing'] + $progress['skipped'],
            'synced'   => $progress['synced'],
            'existing' => $progress['existing'],
            'skipped'  => $progress['skipped'],
        ], $progress['errors'], $message, $syncedFrom);
    }

    /**
     * employee_no is only unique within the biometric device that assigned it
     * (identified by synced_from - see employee_offices), so it's resolved
     * through that table when the device is known. Falls back to the
     * identity table's own employee_no for records synced before per-device
     * enrollments existed.
     */
    private function resolveEmployeeIdByEnrollment(?string $employeeNo, ?string $syncedFrom): ?string
    {
        if (!$employeeNo) {
            return null;
        }

        if ($syncedFrom) {
            $employeeId = EmployeeOffice::where('synced_from', $syncedFrom)
                ->where('employee_no', $employeeNo)
                ->value('employee_id');

            if ($employeeId) {
                return $employeeId;
            }
        }

        // No usable device context. employee_no is only unique within the
        // device that assigned it, so if it's held by more than one distinct
        // employee across different devices, guessing which one this record
        // belongs to would risk silently attaching it to the wrong person -
        // refuse instead.
        $matches = EmployeeOffice::where('employee_no', $employeeNo)->pluck('employee_id')->unique();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            return null;
        }

        // withTrashed(): a soft-deleted employee on the source side is still
        // a real employee here and should resolve normally — deletion there
        // is reflected via employee_is_active, not by refusing to match.
        return Employee::withTrashed()->where('employee_no', $employeeNo)->value('id');
    }

    /**
     * Full name is the identity key for employees (see receiveEmployees), but
     * different offices don't always record it the same way - one office may
     * store a full middle name ("Jose") where another only recorded an
     * initial ("J." / "J"). name_ext ("Jr.", "Sr.") is still matched exactly,
     * since it's what actually distinguishes different people who share a name
     * (e.g. "Jose Manalo Jr." vs "Jose Manalo").
     */
    private function findMatchingEmployee(array $data): ?Employee
    {
        $firstName = trim($data['first_name'] ?? '');
        $lastName  = trim($data['last_name'] ?? '');
        $nameExt   = trim($data['name_ext'] ?? '') ?: null;

        $candidates = Employee::whereRaw('LOWER(TRIM(first_name)) = ?', [mb_strtolower($firstName)])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [mb_strtolower($lastName)])
            ->where(function ($q) use ($nameExt) {
                if ($nameExt === null) {
                    $q->whereNull('name_ext')->orWhere('name_ext', '');
                } else {
                    $q->whereRaw('LOWER(TRIM(name_ext)) = ?', [mb_strtolower($nameExt)]);
                }
            })
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->middleNamesMatch($candidate->middle_name, $data['middle_name'] ?? null)) {
                return $candidate;
            }
        }

        return null;
    }

    private function middleNamesMatch(?string $a, ?string $b): bool
    {
        $a = $a !== null && trim($a) !== '' ? trim($a) : null;
        $b = $b !== null && trim($b) !== '' ? trim($b) : null;

        if ($a === null && $b === null) {
            return true;
        }

        if ($a === null || $b === null) {
            return false;
        }

        if (mb_strtolower($a) === mb_strtolower($b)) {
            return true;
        }

        $aIsInitial = $this->isMiddleInitial($a);
        $bIsInitial = $this->isMiddleInitial($b);

        if ($aIsInitial === $bIsInitial) {
            // Either both are initials but different letters, or both are
            // full names but spelled differently - not a safe match.
            return false;
        }

        $initial = $aIsInitial ? $a : $b;
        $full    = $aIsInitial ? $b : $a;

        return mb_strtolower(rtrim($initial, '.')) === mb_strtolower(mb_substr($full, 0, 1));
    }

    private function isMiddleInitial(string $value): bool
    {
        return (bool) preg_match('/^[A-Za-z]\.?$/', $value);
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

    private function nullIfZeroDate(?string $date): ?string
    {
        return $date === '0000-00-00' ? null : $date;
    }

    private function resolveScheduleType(?string $name): ?int
    {
        if (!$name) return null;
        return ScheduleType::firstOrCreate(['name' => $name])->id;
    }

}
