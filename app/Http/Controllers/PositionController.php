<?php

namespace App\Http\Controllers;

use App\Http\Requests\PositionRequest;
use App\Http\Resources\Position as ResourcesPosition;
use App\Models\Position;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $positions = [];
        if (isset($request->search)) {
            $positions = Position::where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $positions = isset($request->search) && $request->search ? $positions->paginate(10) : Position::paginate(10);
        return ResourcesPosition::collection($positions);
    }

    public function store(PositionRequest $request)
    {
        try {
            $position = new Position();
            $position->name        = ucwords($request->name);
            $position->description = $request->description;
            $position->save();

            return response()->json(['message' => 'Position has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, PositionRequest $request)
    {
        try {
            $position = Position::findOrFail($id);
            $position->name        = ucwords($request->name);
            $position->description = $request->description;
            $position->update();

            return response(['message' => 'Position has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        Position::findOrFail($id)->delete();
        return response(['message' => 'Position has been successfully deleted!']);
    }
}
