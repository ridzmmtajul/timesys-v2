<?php

namespace App\Http\Controllers;

use App\Models\AttendanceUndertimeView;
use App\Models\Employee;
use App\Models\Office;
use App\Models\OfficeDivision;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function employeeList(Request $request)
    {
        $query = Employee::with(['office', 'employmentType', 'position', 'officeDivision'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $employees = $query->get();

        $officeName = $request->filled('office_id')
            ? (Office::find($request->office_id)?->name ?? 'All Offices')
            : 'All Offices';

        $filename = 'employees_' . str_replace(' ', '_', strtolower($officeName)) . '_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($employees) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Employee No',
                'Last Name',
                'First Name',
                'Middle Name',
                'Extension',
                'Gender',
                'Job Title',
                'Position',
                'Office',
                'Office Division',
                'Employment Type',
                'Contact No',
                'Status',
            ]);

            foreach ($employees as $emp) {
                fputcsv($handle, [
                    $emp->employee_no,
                    $emp->last_name,
                    $emp->first_name,
                    $emp->middle_name,
                    $emp->name_ext,
                    $emp->gender,
                    $emp->job_title,
                    $emp->position?->name,
                    $emp->office?->name,
                    $emp->officeDivision?->name,
                    $emp->employmentType?->name,
                    $emp->contact_no,
                    $emp->is_active ? 'Active' : 'Inactive',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function logbook(Request $request)
    {
        [$rows, $meta] = $this->buildLogbookData($request);

        return response()->json([
            'data' => $rows,
            'meta' => $meta,
        ]);
    }

    public function logbookExport(Request $request)
    {
        [$rows, $meta] = $this->buildLogbookData($request);

        $pdf = Pdf::loadView('logbook.template', [
            'rows'            => $rows,
            'office'          => $meta['office'],
            'office_division' => $meta['office_division'],
            'date'            => $meta['date'],
            'generated_at'    => $meta['generated_at'],
        ])->setPaper('A4', 'portrait');

        $filename = 'logbook_' . str_replace(' ', '_', strtolower($meta['office'])) . '_' . $meta['date'] . '.pdf';

        return $pdf->stream($filename);
    }

    private function buildLogbookData(Request $request): array
    {
        $date = $request->filled('date') ? $request->date : now()->format('Y-m-d');

        $employeesQuery = Employee::with(['office', 'officeDivision'])
            ->where('is_active', true);

        if ($request->filled('office_id')) {
            $employeesQuery->where('office_id', $request->office_id);
        }

        if ($request->filled('office_division_id')) {
            $employeesQuery->where('office_division_id', $request->office_division_id);
        }

        $employees = $employeesQuery->get();

        $records = AttendanceUndertimeView::whereIn('employee_id', $employees->pluck('id'))
            ->where('date', $date)
            ->get()
            ->keyBy('employee_id');

        $rows = $employees
            ->map(fn ($employee) => $this->logbookRow($employee, $records->get($employee->id)))
            ->filter(fn ($row) => $row['am_arrival'] || $row['am_departure'] || $row['pm_arrival'] || $row['pm_departure'])
            ->sortBy('sort_key')
            ->values()
            ->all();

        $officeName = $request->filled('office_id')
            ? (Office::find($request->office_id)?->name ?? 'All Offices')
            : 'All Offices';

        $divisionName = $request->filled('office_division_id')
            ? OfficeDivision::find($request->office_division_id)?->name
            : null;

        return [
            $rows,
            [
                'date'            => $date,
                'office'          => $officeName,
                'office_division' => $divisionName,
                'generated_at'    => now()->format('M d, Y h:i:s A'),
                'total'           => count($rows),
            ],
        ];
    }

    private function logbookRow(Employee $employee, ?AttendanceUndertimeView $rec): array
    {
        $amArrival   = $rec?->actual_timein_AM;
        $amDeparture = $rec?->actual_timeout_AM;
        $pmArrival   = $rec?->actual_timein_PM;
        $pmDeparture = $rec?->actual_timeout_PM;

        $undertimeMinutes = (int) ($rec?->total_undertime_minutes ?? 0);
        $undertimeLabel   = null;
        if ($undertimeMinutes > 0) {
            $hours   = intdiv($undertimeMinutes, 60);
            $minutes = $undertimeMinutes % 60;
            $undertimeLabel = trim(($hours > 0 ? "{$hours} hr" . ($hours > 1 ? 's' : '') . ' ' : '') . ($minutes > 0 ? "{$minutes} mins" : ''));
        }

        return [
            'employee_id'        => $employee->id,
            'employee_name'      => trim(
                $employee->last_name . ', ' . $employee->first_name .
                ($employee->middle_name ? ' ' . strtoupper(substr($employee->middle_name, 0, 1)) . '.' : '') .
                ($employee->name_ext ? ' ' . $employee->name_ext : '')
            ),
            'am_arrival'         => $amArrival ? Carbon::parse($amArrival)->format('g:i') : null,
            'am_departure'       => $amDeparture ? Carbon::parse($amDeparture)->format('g:i') : null,
            'pm_arrival'         => $pmArrival ? Carbon::parse($pmArrival)->format('g:i') : null,
            'pm_departure'       => $pmDeparture ? Carbon::parse($pmDeparture)->format('g:i') : null,
            'undertime_minutes'  => $undertimeMinutes,
            'undertime_label'    => $undertimeLabel,
            'sort_key'           => $amArrival ? Carbon::parse($amArrival)->format('H:i:s') : '99:99:99',
        ];
    }
}
