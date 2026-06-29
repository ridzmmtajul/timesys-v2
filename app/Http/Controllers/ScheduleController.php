<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleRequest;
use App\Http\Resources\Schedule as ResourcesSchedule;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = [];
        if (isset($request->search)) {
            $schedules = Schedule::with('scheduleType')
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $schedules = isset($request->search) && $request->search
            ? $schedules->paginate(10)
            : Schedule::with('scheduleType')->paginate(10);

        return ResourcesSchedule::collection($schedules);
    }

    public function options()
    {
        return response()->json(Schedule::select('id', 'name')->orderBy('name')->get());
    }

    public function store(ScheduleRequest $request)
    {
        try {
            $schedule = new Schedule();
            $schedule->name               = ucwords($request->name);
            $schedule->description        = $request->description;
            $schedule->default_timein_AM  = $request->default_timein_AM ?: null;
            $schedule->default_timeout_AM = $request->default_timeout_AM ?: null;
            $schedule->default_timein_PM  = $request->default_timein_PM ?: null;
            $schedule->default_timeout_PM = $request->default_timeout_PM ?: null;
            $schedule->schedule_type_id   = $request->schedule_type_id ?: null;
            $schedule->no_lunch_gap       = $request->boolean('no_lunch_gap');
            $schedule->save();

            return response()->json(['message' => 'Schedule has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, ScheduleRequest $request)
    {
        try {
            $schedule = Schedule::findOrFail($id);
            $schedule->name               = ucwords($request->name);
            $schedule->description        = $request->description;
            $schedule->default_timein_AM  = $request->default_timein_AM ?: null;
            $schedule->default_timeout_AM = $request->default_timeout_AM ?: null;
            $schedule->default_timein_PM  = $request->default_timein_PM ?: null;
            $schedule->default_timeout_PM = $request->default_timeout_PM ?: null;
            $schedule->schedule_type_id   = $request->schedule_type_id ?: null;
            $schedule->no_lunch_gap       = $request->boolean('no_lunch_gap');
            $schedule->update();

            return response(['message' => 'Schedule has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        Schedule::findOrFail($id)->delete();
        return response(['message' => 'Schedule has been successfully deleted!']);
    }
}
