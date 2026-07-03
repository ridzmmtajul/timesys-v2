<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Office;
use App\Models\OfficeDivision;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SyncController extends Controller
{
    // ── LOCAL INSTANCE ──────────────────────────────────────────────────────
    // Reads local employees and pushes them to the central server.

    public function pushEmployees()
    {
        $centralUrl = config('services.sync.central_url');
        $apiKey     = config('services.sync.api_key');

        if (!$centralUrl || !$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Sync not configured. Set CENTRAL_SERVER_URL and SYNC_API_KEY in .env.',
            ], 500);
        }

        $employees = Employee::with(['office', 'employmentType', 'position', 'officeDivision'])->get();

        $payload = $employees->map(fn ($emp) => [
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
        ])->values()->all();

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post(rtrim($centralUrl, '/') . '/api/sync/receive-employees', [
                'employees' => $payload,
            ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Central server returned an error.',
                'details' => $response->json(),
            ], 502);
        }

        return response()->json($response->json());
    }

    // ── SERVER INSTANCE ─────────────────────────────────────────────────────
    // Receives employee payload from a local instance and upserts into the central DB.

    public function receiveEmployees(Request $request)
    {
        $employees = $request->input('employees', []);
        $synced    = 0;
        $skipped   = 0;
        $errors    = [];

        foreach ($employees as $data) {
            try {
                $officeId = $this->resolveOffice($data['office_name'] ?? null, $data['office_code'] ?? null);

                if (!$officeId) {
                    $skipped++;
                    $errors[] = "Employee {$data['employee_no']}: office name is missing, skipped.";
                    continue;
                }

                Employee::updateOrCreate(
                    ['employee_no' => $data['employee_no']],
                    [
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
                    ]
                );

                $synced++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Employee {$data['employee_no']}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'success' => true,
            'synced'  => $synced,
            'skipped' => $skipped,
            'errors'  => $errors,
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
}
