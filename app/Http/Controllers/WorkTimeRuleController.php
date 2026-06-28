<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkTimeRuleRequest;
use App\Http\Resources\WorkTimeRule as ResourcesWorkTimeRule;
use App\Models\WorkTimeRule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkTimeRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkTimeRule::query();

        if (isset($request->search) && $request->search) {
            $query->where('rule', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        return ResourcesWorkTimeRule::collection($query->paginate(10));
    }

    public function store(WorkTimeRuleRequest $request)
    {
        try {
            $workTimeRule = new WorkTimeRule();
            $workTimeRule->rule        = $request->rule;
            $workTimeRule->description = $request->description;
            $workTimeRule->time        = $request->time;
            $workTimeRule->offices     = $request->offices ?: null;
            $workTimeRule->save();

            return response()->json(['message' => 'Work Time Rule has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update(int $id, WorkTimeRuleRequest $request)
    {
        try {
            $workTimeRule = WorkTimeRule::findOrFail($id);
            $workTimeRule->rule        = $request->rule;
            $workTimeRule->description = $request->description;
            $workTimeRule->time        = $request->time;
            $workTimeRule->offices     = $request->offices ?: null;
            $workTimeRule->update();

            return response(['message' => 'Work Time Rule has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy(int $id)
    {
        WorkTimeRule::findOrFail($id)->delete();
        return response(['message' => 'Work Time Rule has been successfully deleted!']);
    }
}
