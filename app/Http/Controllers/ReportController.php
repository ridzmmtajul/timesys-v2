<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Office;
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
}
