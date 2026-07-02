<?php

namespace App\Http\Controllers;

use App\Models\AttendanceUndertimeView;
use App\Models\Employee;
use App\Models\Holiday;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DtrController extends Controller
{
    public function options()
    {
        $offices   = \App\Models\Office::orderBy('name')->get(['id', 'code', 'name']);
        $divisions = \App\Models\OfficeDivision::orderBy('name')->get(['id', 'office_id', 'code', 'name']);
        $employees = Employee::where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'employee_no', 'first_name', 'middle_name', 'last_name', 'name_ext', 'office_id', 'office_division_id']);

        return response()->json([
            'data' => [
                'offices'   => $offices,
                'divisions' => $divisions,
                'employees' => $employees->map(fn ($e) => [
                    'id'                 => $e->id,
                    'employee_no'        => $e->employee_no,
                    'first_name'         => $e->first_name,
                    'middle_name'        => $e->middle_name ? strtoupper(substr($e->middle_name, 0, 1)) . '.' : '',
                    'last_name'          => $e->last_name,
                    'full_name'          => trim(
                        $e->last_name . ', ' . $e->first_name .
                        ($e->middle_name ? ' ' . $e->middle_name : '') .
                        ($e->name_ext   ? ' ' . $e->name_ext   : '')
                    ),
                    'office_id'          => $e->office_id,
                    'office_division_id' => $e->office_division_id,
                ]),
            ],
        ]);
    }

    public function generate(Request $request)
    {
        $this->validateRequest($request);
        return response()->json($this->buildDtrData($request));
    }

    public function pdf(Request $request)
    {
        $this->validateRequest($request);
        $data = $this->buildDtrData($request);

        $pdf = Pdf::loadView('dtr.template', array_merge($data, ['employees' => $data['data']]))
                  ->setPaper('A4', 'portrait');

        $filename = 'DTR_' . str_replace(' ', '_', $data['month']) . '.pdf';

        return $pdf->stream($filename);
    }

    // ── Private helpers ──────────────────────────────────────

    private function validateRequest(Request $request): void
    {
        $request->validate([
            'employee_ids'        => 'required|array|min:1',
            'employee_ids.*'      => 'required|string',
            'month'               => 'required|date_format:Y-m',
            'date_range'          => 'nullable|in:1-15,16-end,full',
            'official_timein_AM'  => 'nullable|date_format:H:i',
            'official_timeout_AM' => 'nullable|date_format:H:i',
            'official_timein_PM'  => 'nullable|date_format:H:i',
            'official_timeout_PM' => 'nullable|date_format:H:i',
            'regular_days'        => 'nullable|string|max:100',
            'saturdays'           => 'nullable|string|max:100',
            'signatory'           => 'nullable|string|max:255',
            'with_saturday'       => 'nullable|boolean',
            'with_holiday'        => 'nullable|boolean',
            'show_signature'      => 'nullable|boolean',
        ]);
    }

    private function buildDtrData(Request $request): array
    {
        $monthCarbon  = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $daysInMonth  = $monthCarbon->daysInMonth;
        $monthNum     = (int) $monthCarbon->month;
        $yearNum      = (int) $monthCarbon->year;

        $dateRange = $request->date_range ?? 'full';
        $fromDay   = 1;
        $toDay     = $daysInMonth;
        $rangeLabel = null;
        if ($dateRange === '1-15')    { $toDay = 15; $rangeLabel = '1-15'; }
        elseif ($dateRange === '16-end') { $fromDay = 16; $rangeLabel = '16-' . $daysInMonth; }

        $monthYear = $rangeLabel
            ? $monthCarbon->format('F') . ' ' . $rangeLabel . ', ' . $yearNum
            : $monthCarbon->format('F Y');

        $fromDate = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $fromDay);
        $toDate   = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $toDay);

        $holidayDays = Holiday::where('is_active', true)->get()
            ->filter(fn ($h) => (int) $h->month === $monthNum)
            ->pluck('day')
            ->map(fn ($d) => (int) $d)
            ->flip()
            ->all();

        $employees = Employee::with(['office', 'position'])
            ->whereIn('id', $request->employee_ids)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $results = [];

        foreach ($employees as $employee) {
            $viewByDate = AttendanceUndertimeView::where('employee_id', $employee->id)
                ->whereBetween('date', [$fromDate, $toDate])
                ->get()
                ->keyBy(fn ($r) => $r->date->toDateString());

            $dailyRecords          = [];
            $totalUndertimeMinutes = 0;

            for ($day = 1; $day <= 31; $day++) {
                if ($day > $daysInMonth || $day < $fromDay || $day > $toDay) {
                    $dailyRecords[$day] = null;
                    continue;
                }

                $date      = sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $day);
                $rec       = $viewByDate[$date] ?? null;
                $dayOfWeek = Carbon::createFromDate($yearNum, $monthNum, $day)->dayOfWeek;

                $dayType = null;
                if (isset($holidayDays[$day])) {
                    $dayType = 'HOLIDAY';
                } elseif ($dayOfWeek === 0) {
                    $dayType = 'SUNDAY';
                } elseif ($dayOfWeek === 6) {
                    $dayType = 'SATURDAY';
                }

                $amArrival   = $rec?->actual_timein_AM  ? Carbon::parse($rec->actual_timein_AM)->format('H:i')  : null;
                $amDeparture = $rec?->actual_timeout_AM ? Carbon::parse($rec->actual_timeout_AM)->format('H:i') : null;
                $pmArrival   = $rec?->actual_timein_PM  ? Carbon::parse($rec->actual_timein_PM)->format('H:i')  : null;
                $pmDeparture = $rec?->actual_timeout_PM ? Carbon::parse($rec->actual_timeout_PM)->format('H:i') : null;

                $undertimeMinutes       = (int) ($rec?->total_undertime_minutes ?? 0);
                $totalUndertimeMinutes += $undertimeMinutes;

                $dailyRecords[$day] = [
                    'date'              => $date,
                    'day_type'          => $dayType,
                    'am_arrival'        => $amArrival,
                    'am_departure'      => $amDeparture,
                    'pm_arrival'        => $pmArrival,
                    'pm_departure'      => $pmDeparture,
                    'undertime_hours'   => (int) ($undertimeMinutes / 60) > 0 ? (int) ($undertimeMinutes / 60) : null,
                    'undertime_minutes' => $undertimeMinutes > 0 ? $undertimeMinutes % 60 : null,
                ];
            }

            $results[] = [
                'id'                      => $employee->id,
                'employee_no'             => $employee->employee_no,
                'full_name'               => trim(
                    $employee->last_name . ', ' . $employee->first_name .
                    ($employee->middle_name ? ' ' . $employee->middle_name : '') .
                    ($employee->name_ext   ? ' ' . $employee->name_ext   : '')
                ),
                'position'                => $employee->position?->name ?? '',
                'office'                  => $employee->office?->name ?? '',
                'daily_records'           => $dailyRecords,
                'total_undertime_hours'   => (int) ($totalUndertimeMinutes / 60),
                'total_undertime_minutes' => $totalUndertimeMinutes % 60,
            ];
        }

        return [
            'data'                => $results,
            'month'               => $monthYear,
            'days_in_month'       => $daysInMonth,
            'date_range'          => $dateRange,
            'from_day'            => $fromDay,
            'to_day'              => $toDay,
            'signatory'           => $request->signatory ?? '',
            'regular_days'        => $request->regular_days ?? '',
            'saturdays'           => $request->saturdays ?? '',
            'official_timein_AM'  => $request->official_timein_AM ?? '',
            'official_timeout_AM' => $request->official_timeout_AM ?? '',
            'official_timein_PM'  => $request->official_timein_PM ?? '',
            'official_timeout_PM' => $request->official_timeout_PM ?? '',
            'with_saturday'       => $request->boolean('with_saturday', true),
            'with_holiday'        => $request->boolean('with_holiday', true),
            'show_signature'      => $request->boolean('show_signature', true),
        ];
    }
}
