<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\Employee as ResourcesEmployee;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Office;
use App\Models\OfficeDivision;
use App\Models\Position;
use App\Models\Schedule;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
                'offices'          => Office::orderBy('name')->get(['id', 'name', 'prefix', 'latest_employee_no']),
                'employment_types' => EmploymentType::orderBy('name')->get(['id', 'name']),
                'positions'        => Position::orderBy('name')->get(['id', 'name']),
                'office_divisions' => OfficeDivision::orderBy('name')->get(['id', 'name', 'office_id']),
            ],
        ]);
    }

    public function store(EmployeeRequest $request)
    {
        try {
            $employee = Employee::create($request->validated());

            $defaultSchedule = Schedule::find(1);

            WorkSchedule::create([
                'employee_id'  => $employee->id,
                'schedule_id'  => $defaultSchedule?->id,
                'schedule_for' => 'everyday',
                'days'         => [],
            ]);

            Office::where('id', $employee->office_id)
                ->update(['latest_employee_no' => $employee->employee_no]);

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully.',
                'data'    => new ResourcesEmployee($employee->load(['office', 'employmentType', 'position', 'officeDivision'])),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Employee $employee)
    {
        return new ResourcesEmployee($employee->load(['office', 'employmentType', 'position', 'officeDivision']));
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        try {
            $employee->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully.',
                'data'    => new ResourcesEmployee($employee->load(['office', 'employmentType', 'position', 'officeDivision'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully.',
        ]);
    }

    public function toggleStatus(Employee $employee)
    {
        $employee->update(['is_active' => !$employee->is_active]);

        return response()->json([
            'success' => true,
            'message' => $employee->is_active ? 'Employee activated.' : 'Employee deactivated.',
        ]);
    }
}
