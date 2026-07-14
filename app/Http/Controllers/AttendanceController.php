<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ResolvesEmployeeByEnrollment;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    use ResolvesEmployeeByEnrollment;

    public function import(Request $request)
    {
        foreach ($request->logs as $log) {

            Attendance::updateOrCreate(
                [
                    'employee_id' => $log['employee_id'],
                    'timestamp' => $log['timestamp']
                ],
                [
                    'employee_name' => $log['employee_name'],
                    'status' => $log['status']
                ]
            );
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function index()
    {
        return Attendance::latest()->paginate(20);
    }

    public function receive(Request $request)
    {
        $validated = $request->validate([
            'check_time' => 'required|date',
            'employee_no' => 'required|string',
            'synced_from' => 'nullable|string',
            'serial_no' => 'nullable|string',
            'post_no' => 'nullable|integer|exists:post_numbers,id',
        ]);

        $employeeId = $this->resolveEmployeeIdByEnrollment($validated['employee_no'], $validated['synced_from'] ?? null);

        if (!$employeeId) {
            return response()->json(['status' => 'not_found', 'message' => "Employee {$validated['employee_no']} not found."], 404);
        }

        $exists = Attendance::where('check_time', $validated['check_time'])
            ->where('employee_id', $employeeId)
            ->where('post_no', $validated['post_no'] ?? null)
            ->exists();

        if ($exists) {
            return response()->json(['status' => 'existing', 'message' => 'Attendance already exists.']);
        }

        $attendance = Attendance::create([
            'check_time' => $validated['check_time'],
            'employee_id' => $employeeId,
            'serial_no' => $validated['serial_no'] ?? null,
            'post_no' => $validated['post_no'] ?? null,
            'synced_from' => $validated['synced_from'] ?? null,
        ]);

        return response()->json(['status' => 'created', 'data' => $attendance], 201);
    }
}
