<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmploymentTypeRequest;
use App\Http\Resources\EmploymentType as ResourcesEmploymentType;
use App\Models\EmploymentType;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmploymentTypeController extends Controller
{
    public function index(Request $request)
    {
        $employmentTypes = [];
        if (isset($request->search)) {
            $employmentTypes = EmploymentType::where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $employmentTypes = isset($request->search) && $request->search
            ? $employmentTypes->paginate(10)
            : EmploymentType::paginate(10);

        return ResourcesEmploymentType::collection($employmentTypes);
    }

    public function store(EmploymentTypeRequest $request)
    {
        try {
            $employmentType = new EmploymentType();
            $employmentType->name        = ucwords($request->name);
            $employmentType->description = $request->description;
            $employmentType->save();

            return response()->json(['message' => 'Employment Type has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, EmploymentTypeRequest $request)
    {
        try {
            $employmentType = EmploymentType::findOrFail($id);
            $employmentType->name        = ucwords($request->name);
            $employmentType->description = $request->description;
            $employmentType->update();

            return response(['message' => 'Employment Type has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        EmploymentType::findOrFail($id)->delete();
        return response(['message' => 'Employment Type has been successfully deleted!']);
    }
}
