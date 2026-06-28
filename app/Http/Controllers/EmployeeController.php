<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Office;
use App\Models\OfficeDivision;
use App\Models\Position;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['office', 'employmentType', 'position', 'officeDivision']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $employees = $query->orderBy('last_name')->orderBy('first_name')->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $employees,
            'meta'    => [
                'active_count' => Employee::where('is_active', true)->count(),
            ],
        ]);
    }

    public function options()
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'offices'          => Office::orderBy('name')->get(['id', 'name']),
                'employment_types' => EmploymentType::orderBy('name')->get(['id', 'name']),
                'positions'        => Position::orderBy('name')->get(['id', 'name']),
                'office_divisions' => OfficeDivision::orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_no'        => ['required', 'string', 'max:255'],
            'first_name'         => ['required', 'string', 'max:255'],
            'middle_name'        => ['nullable', 'string', 'max:255'],
            'last_name'          => ['required', 'string', 'max:255'],
            'name_ext'           => ['nullable', 'string', 'max:255'],
            'gender'             => ['nullable', 'string', 'in:Male,Female'],
            'contact_no'         => ['nullable', 'string', 'max:255'],
            'job_title'          => ['nullable', 'string', 'max:255'],
            'is_active'          => ['boolean'],
            'office_id'          => ['required', 'exists:offices,id'],
            'employment_type_id' => ['nullable', 'exists:employment_types,id'],
            'position_id'        => ['nullable', 'exists:positions,id'],
            'office_division_id' => ['nullable', 'exists:office_divisions,id'],
            'title_id'           => ['nullable', 'integer'],
        ]);

        $employee = Employee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully.',
            'data'    => $employee->load(['office', 'employmentType', 'position', 'officeDivision']),
        ], 201);
    }

    public function show(Employee $employee)
    {
        return response()->json([
            'success' => true,
            'data'    => $employee->load(['office', 'employmentType', 'position', 'officeDivision']),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_no'        => ['required', 'string', 'max:255'],
            'first_name'         => ['required', 'string', 'max:255'],
            'middle_name'        => ['nullable', 'string', 'max:255'],
            'last_name'          => ['required', 'string', 'max:255'],
            'name_ext'           => ['nullable', 'string', 'max:255'],
            'gender'             => ['nullable', 'string', 'in:Male,Female'],
            'contact_no'         => ['nullable', 'string', 'max:255'],
            'job_title'          => ['nullable', 'string', 'max:255'],
            'is_active'          => ['boolean'],
            'office_id'          => ['required', 'exists:offices,id'],
            'employment_type_id' => ['nullable', 'exists:employment_types,id'],
            'position_id'        => ['nullable', 'exists:positions,id'],
            'office_division_id' => ['nullable', 'exists:office_divisions,id'],
            'title_id'           => ['nullable', 'integer'],
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully.',
            'data'    => $employee->load(['office', 'employmentType', 'position', 'officeDivision']),
        ]);
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully.',
        ]);
    }
}
