<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfficeRequest;
use App\Http\Resources\Office as ResourcesOffice;
use App\Models\Office;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OfficeController extends Controller
{
    public function index(Request $request)
    {
        $offices = [];
        if (isset($request->search)) {
            $offices = Office::where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%');
        }

        $offices = isset($request->search) && $request->search ? $offices->paginate(10) : Office::paginate(10);
        return ResourcesOffice::collection($offices);
    }

    public function options()
    {
        return response()->json(Office::select('id', 'code', 'name')->orderBy('name')->get());
    }

    public function store(OfficeRequest $request)
    {
        try {
            $office = new Office();
            $office->code = strtoupper($request->code);
            $office->name = ucwords($request->name);
            $office->description = $request->description;
            $office->prefix = $request->prefix ? strtoupper($request->prefix) : null;
            $office->latest_employee_no = $request->latest_employee_no;
            $office->save();

            return response()->json(['message' => 'Office has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, OfficeRequest $request)
    {
        try {
            $office = Office::findOrFail($id);
            $office->code = strtoupper($request->code);
            $office->name = ucwords($request->name);
            $office->description = $request->description;
            $office->prefix = $request->prefix ? strtoupper($request->prefix) : null;
            $office->latest_employee_no = $request->latest_employee_no;
            $office->update();

            return response(['message' => 'Office has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        Office::findOrFail($id)->delete();
        return response(['message' => 'Office has been successfully deleted!']);
    }
}
