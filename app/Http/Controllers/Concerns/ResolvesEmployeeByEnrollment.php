<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;
use App\Models\EmployeeOffice;

trait ResolvesEmployeeByEnrollment
{
    /**
     * Resolves a device/external-system employee_no to our Employee UUID
     * through the per-device EmployeeOffice enrollment table when the device
     * is known. Falls back to the identity table's own employee_no for
     * records synced before per-device enrollments existed.
     */
    private function resolveEmployeeIdByEnrollment(?string $employeeNo, ?string $syncedFrom): ?string
    {
        if (!$employeeNo) {
            return null;
        }

        if ($syncedFrom) {
            $employeeId = EmployeeOffice::where('synced_from', $syncedFrom)
                ->where('employee_no', $employeeNo)
                ->value('employee_id');

            if ($employeeId) {
                return $employeeId;
            }
        }

        // No usable device context. employee_no is only unique within the
        // device that assigned it, so if it's held by more than one distinct
        // employee across different devices, guessing which one this record
        // belongs to would risk silently attaching it to the wrong person -
        // refuse instead.
        $matches = EmployeeOffice::where('employee_no', $employeeNo)->pluck('employee_id')->unique();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            return null;
        }

        return Employee::where('employee_no', $employeeNo)->value('id');
    }
}
