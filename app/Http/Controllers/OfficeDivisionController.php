<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfficeDivisionRequest;
use App\Http\Resources\OfficeDivision as ResourcesOfficeDivision;
use App\Models\OfficeDivision;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OfficeDivisionController extends Controller
{
    public function index(Request $request)
    {
        $query = OfficeDivision::with('office');

        if (isset($request->search) && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        $divisions = $query->paginate(10);
        return ResourcesOfficeDivision::collection($divisions);
    }

    public function store(OfficeDivisionRequest $request)
    {
        try {
            $division = new OfficeDivision();
            $division->code = strtoupper($request->code);
            $division->name = ucwords($request->name);
            $division->description = $request->description;
            $division->office_id = $request->office_id;
            $division->save();

            return response()->json(['message' => 'Office Division has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, OfficeDivisionRequest $request)
    {
        try {
            $division = OfficeDivision::findOrFail($id);
            $division->code = strtoupper($request->code);
            $division->name = ucwords($request->name);
            $division->description = $request->description;
            $division->office_id = $request->office_id;
            $division->update();

            return response(['message' => 'Office Division has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        OfficeDivision::findOrFail($id)->delete();
        return response(['message' => 'Office Division has been successfully deleted!']);
    }
}
