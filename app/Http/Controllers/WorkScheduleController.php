<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkScheduleRequest;
use App\Http\Resources\WorkSchedule as ResourcesWorkSchedule;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkSchedule::with(['employee', 'schedule', 'scheduleType']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('schedule_for', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('employee_no', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = min((int) ($request->per_page ?? 10), 1000);
        $workSchedules = $query->paginate($perPage);
        return ResourcesWorkSchedule::collection($workSchedules);
    }

    public function options()
    {
        return response()->json([
            'employees' => Employee::orderBy('last_name')
                ->get(['id', 'employee_no', 'first_name', 'last_name']),
            'schedules' => Schedule::orderBy('name')
                ->get(['id', 'name', 'schedule_type_id', 'default_timein_AM', 'default_timeout_AM', 'default_timein_PM', 'default_timeout_PM', 'no_lunch_gap']),
        ]);
    }

    public function store(WorkScheduleRequest $request)
    {
        try {
            $workSchedule = new WorkSchedule();
            $workSchedule->employee_id      = $request->employee_id;
            $workSchedule->schedule_id      = $request->schedule_id ?: null;
            $workSchedule->schedule_type_id = $request->schedule_type_id ?: null;
            $workSchedule->timein_AM        = $request->timein_AM ?: null;
            $workSchedule->timeout_AM       = $request->timeout_AM ?: null;
            $workSchedule->timein_PM        = $request->timein_PM ?: null;
            $workSchedule->timeout_PM       = $request->timeout_PM ?: null;
            $workSchedule->from_date        = $request->from_date ?: null;
            $workSchedule->to_date          = $request->to_date ?: null;
            $workSchedule->is_others        = $request->boolean('is_others');
            $workSchedule->schedule_for     = $request->schedule_for;
            $workSchedule->days             = $request->days ?? [];
            $workSchedule->no_lunch_gap     = $request->boolean('no_lunch_gap');
            $workSchedule->save();

            return response()->json(['message' => 'Work Schedule has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, WorkScheduleRequest $request)
    {
        try {
            $workSchedule = WorkSchedule::findOrFail($id);
            $workSchedule->employee_id      = $request->employee_id;
            $workSchedule->schedule_id      = $request->schedule_id ?: null;
            $workSchedule->schedule_type_id = $request->schedule_type_id ?: null;
            $workSchedule->timein_AM        = $request->timein_AM ?: null;
            $workSchedule->timeout_AM       = $request->timeout_AM ?: null;
            $workSchedule->timein_PM        = $request->timein_PM ?: null;
            $workSchedule->timeout_PM       = $request->timeout_PM ?: null;
            $workSchedule->from_date        = $request->from_date ?: null;
            $workSchedule->to_date          = $request->to_date ?: null;
            $workSchedule->is_others        = $request->boolean('is_others');
            $workSchedule->schedule_for     = $request->schedule_for;
            $workSchedule->days             = $request->days ?? [];
            $workSchedule->no_lunch_gap     = $request->boolean('no_lunch_gap');
            $workSchedule->update();

            return response(['message' => 'Work Schedule has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        WorkSchedule::findOrFail($id)->delete();
        return response(['message' => 'Work Schedule has been successfully deleted!']);
    }
}
