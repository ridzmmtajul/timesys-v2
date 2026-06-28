<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleTypeRequest;
use App\Http\Resources\ScheduleType as ResourcesScheduleType;
use App\Models\ScheduleType;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleTypeController extends Controller
{
    public function index(Request $request)
    {
        $scheduleTypes = [];
        if (isset($request->search)) {
            $scheduleTypes = ScheduleType::where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $scheduleTypes = isset($request->search) && $request->search ? $scheduleTypes->paginate(10) : ScheduleType::paginate(10);
        return ResourcesScheduleType::collection($scheduleTypes);
    }

    public function options()
    {
        return response()->json(ScheduleType::select('id', 'name')->orderBy('name')->get());
    }

    public function store(ScheduleTypeRequest $request)
    {
        try {
            $scheduleType = new ScheduleType();
            $scheduleType->name        = ucwords($request->name);
            $scheduleType->description = $request->description;
            $scheduleType->save();

            return response()->json(['message' => 'Schedule Type has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, ScheduleTypeRequest $request)
    {
        try {
            $scheduleType = ScheduleType::findOrFail($id);
            $scheduleType->name        = ucwords($request->name);
            $scheduleType->description = $request->description;
            $scheduleType->update();

            return response(['message' => 'Schedule Type has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        ScheduleType::findOrFail($id)->delete();
        return response(['message' => 'Schedule Type has been successfully deleted!']);
    }
}
