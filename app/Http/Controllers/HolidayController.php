<?php

namespace App\Http\Controllers;

use App\Http\Requests\HolidayRequest;
use App\Http\Resources\Holiday as ResourcesHoliday;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $holidays = [];
        if (isset($request->search)) {
            $holidays = Holiday::where('name', 'like', '%' . $request->search . '%')
                ->orWhere('month', 'like', '%' . $request->search . '%');
        }

        $holidays = isset($request->search) && $request->search
            ? $holidays->paginate(10)
            : Holiday::paginate(10);

        return ResourcesHoliday::collection($holidays);
    }

    public function store(HolidayRequest $request)
    {
        try {
            $holiday = new Holiday();
            $holiday->name        = ucwords($request->name);
            $holiday->description = $request->description;
            $holiday->month       = $request->month;
            $holiday->day         = $request->day;
            $holiday->save();

            return response()->json(['message' => 'Holiday has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, HolidayRequest $request)
    {
        try {
            $holiday = Holiday::findOrFail($id);
            $holiday->name        = ucwords($request->name);
            $holiday->description = $request->description;
            $holiday->month       = $request->month;
            $holiday->day         = $request->day;
            $holiday->is_active   = $request->is_active;
            $holiday->update();

            return response(['message' => 'Holiday has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        Holiday::findOrFail($id)->delete();
        return response(['message' => 'Holiday has been successfully deleted!']);
    }
}
