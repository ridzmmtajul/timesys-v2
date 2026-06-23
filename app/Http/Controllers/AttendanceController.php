<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;

class AttendanceController extends Controller
{
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
}
