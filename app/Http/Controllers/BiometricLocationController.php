<?php

namespace App\Http\Controllers;

use App\Http\Requests\BiometricLocationRequest;
use App\Http\Resources\BiometricLocation as ResourcesBiometricLocation;
use App\Models\BiometricLocation;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BiometricLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $locations = [];
        if (isset($request->search)) {
            $locations = BiometricLocation::where('name', 'like', '%' . $request->search . '%');
        }

        $locations = isset($request->search) && $request->search ? $locations->paginate(10) : BiometricLocation::paginate(10);
        return ResourcesBiometricLocation::collection($locations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BiometricLocationRequest $request)
    {
        try {
            $location = new BiometricLocation();
            $location->name = $request->name;
            $location->save();

            return response()->json(['message' => 'Biometric location has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, BiometricLocationRequest $request)
    {
        try {
            $location = BiometricLocation::findOrFail($id);
            $location->name = $request->name;
            $location->update();
            return response(['message' => 'Biometric location has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        BiometricLocation::findOrFail($id)->delete();
        return response(['message' => 'Biometric location has been successfully deleted!']);
    }
}
