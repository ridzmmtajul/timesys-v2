<table width="100%" style="border-collapse: collapse; margin-bottom: 1pt;">
    <tr>
        <td valign="top" style="font-size: 6pt;">Civil Service Form No. 48</td>
        <td valign="top" style="text-align: right; width: 45pt;">
            <img src="{{ public_path('images/zamboanga-seal.png') }}" width="45" height="45" style="display:block; margin-left:auto;" />
        </td>
    </tr>
</table>
<p style="font-size: 9.5pt; font-weight: bold; text-align: center; margin: 0 0 1pt 0;">DAILY TIME RECORD</p>
<p style="text-align: center; font-size: 6.5pt; margin: 0 0 6pt 0;">-----o0o-----</p>

{{-- Name --}}
<div style="text-align: center; margin-bottom: 3pt;">
    <div style="border-bottom: 0.5pt solid #000; font-weight: bold; font-size: 8.5pt; padding-bottom: 1pt; min-height: 11pt;">{{ strtoupper($emp['full_name']) }}</div>
    <div style="font-size: 6pt;">(Name)</div>
</div>

{{-- Month --}}
<table width="100%" style="border-collapse: collapse; margin-bottom: 2pt; font-size: 6.5pt;">
    <tr>
        <td style="white-space: nowrap; padding-right: 3pt; width: 1px;">For the month of</td>
        <td style="border-bottom: 0.5pt solid #000; font-weight: 600; text-align: right;">{{ strtoupper($month) }}</td>
    </tr>
</table>

{{-- Official hours --}}
<table width="100%" style="border-collapse: collapse; margin-bottom: 3pt; font-size: 6pt; font-style: italic;">
    <tr>
        <td rowspan="2" style="vertical-align: top; white-space: nowrap; padding-right: 3pt; width: 1px;">
            Official hours for<br/>arrival and departure
        </td>
        <td style="white-space: nowrap; padding-right: 3pt; width: 1px;">Regular days</td>
        <td style="border-bottom: 0.5pt solid #000;">{{ $regular_days }}</td>
    </tr>
    <tr>
        <td style="white-space: nowrap; padding-right: 3pt;">Saturdays</td>
        <td style="border-bottom: 0.5pt solid #000;">{{ $saturdays }}</td>
    </tr>
</table>

{{-- DTR table --}}
<table width="100%" style="border-collapse: collapse; font-size: 6pt;">
    <thead>
        <tr>
            <th rowspan="2" style="border: 0.5pt solid #000; width: 7%; font-weight: bold; padding: 2pt;">Day</th>
            <th colspan="2" style="border: 0.5pt solid #000; font-weight: bold; padding: 2pt;">A.M.</th>
            <th colspan="2" style="border: 0.5pt solid #000; font-weight: bold; padding: 2pt;">P.M.</th>
            <th colspan="2" style="border: 0.5pt solid #000; font-weight: bold; padding: 2pt;">Undertime</th>
        </tr>
        <tr>
            <th style="border: 0.5pt solid #000; font-weight: bold; width: 15%; padding: 2pt;">Arrival</th>
            <th style="border: 0.5pt solid #000; font-weight: bold; width: 15%; padding: 2pt;">Departure</th>
            <th style="border: 0.5pt solid #000; font-weight: bold; width: 15%; padding: 2pt;">Arrival</th>
            <th style="border: 0.5pt solid #000; font-weight: bold; width: 15%; padding: 2pt;">Departure</th>
            <th style="border: 0.5pt solid #000; font-weight: bold; width: 16%; padding: 2pt;">Hours</th>
            <th style="border: 0.5pt solid #000; font-weight: bold; width: 17%; padding: 2pt;">Minutes</th>
        </tr>
    </thead>
    <tbody>
        @for ($day = 1; $day <= $days_in_month; $day++)
            @php $rec = $emp['daily_records'][$day]; @endphp
            @if ($rec === null)
                <tr style="height: 11pt;">
                    <td style="border: 0.5pt solid #000; text-align: center; padding: 1pt 2pt;">{{ $day }}</td>
                    <td style="border: 0.5pt solid #000; padding: 1pt 2pt;"></td><td style="border: 0.5pt solid #000; padding: 1pt 2pt;"></td>
                    <td style="border: 0.5pt solid #000; padding: 1pt 2pt;"></td><td style="border: 0.5pt solid #000; padding: 1pt 2pt;"></td>
                    <td style="border: 0.5pt solid #000; padding: 1pt 2pt;"></td><td style="border: 0.5pt solid #000; padding: 1pt 2pt;"></td>
                </tr>
            @elseif (($rec['day_type'] === 'HOLIDAY' && $with_holiday) || (in_array($rec['day_type'], ['SATURDAY', 'SUNDAY']) && $with_saturday))
                <tr style="height: 11pt;">
                    <td style="border: 0.5pt solid #000; text-align: center; padding: 1pt 2pt;">{{ $day }}</td>
                    <td colspan="4" style="border: 0.5pt solid #000; text-align: center; font-style: italic; padding: 1pt 2pt;">{{ $rec['day_type'] }}</td>
                    <td style="border: 0.5pt solid #000; padding: 1pt 2pt;"></td>
                    <td style="border: 0.5pt solid #000; padding: 1pt 2pt;"></td>
                </tr>
            @else
                <tr style="height: 11pt;">
                    <td style="border: 0.5pt solid #000; text-align: center; padding: 1pt 2pt;">{{ $day }}</td>
                    <td style="border: 0.5pt solid #000; text-align: center; padding: 1pt 2pt;">{{ $fmt($rec['am_arrival']) }}</td>
                    <td style="border: 0.5pt solid #000; text-align: center; padding: 1pt 2pt;">{{ $fmt($rec['am_departure']) }}</td>
                    <td style="border: 0.5pt solid #000; text-align: center; padding: 1pt 2pt;">{{ $fmt($rec['pm_arrival']) }}</td>
                    <td style="border: 0.5pt solid #000; text-align: center; padding: 1pt 2pt;">{{ $fmt($rec['pm_departure']) }}</td>
                    <td style="border: 0.5pt solid #000; text-align: center; padding: 1pt 2pt;">{{ $rec['undertime_hours'] !== null ? $rec['undertime_hours'] : '' }}</td>
                    <td style="border: 0.5pt solid #000; text-align: center; padding: 1pt 2pt;">{{ $rec['undertime_minutes'] !== null ? $rec['undertime_minutes'] : '' }}</td>
                </tr>
            @endif
        @endfor
        <tr style="height: 11pt;">
            <td colspan="5" style="border: 0.5pt solid #000; text-align: right; font-style: italic; font-weight: bold; padding: 1pt 3pt;">Total</td>
            <td style="border: 0.5pt solid #000; text-align: center; font-weight: bold; padding: 1pt 2pt;">
                {{ $emp['total_undertime_hours'] > 0 ? $emp['total_undertime_hours'] : '' }}
            </td>
            <td style="border: 0.5pt solid #000; text-align: center; font-weight: bold; padding: 1pt 2pt;">
                {{ $emp['total_undertime_minutes'] > 0 ? $emp['total_undertime_minutes'] : '' }}
            </td>
        </tr>
    </tbody>
</table>

{{-- Certification --}}
<p style="font-size: 5.5pt; font-style: italic; margin: 4pt 0 0 0; line-height: 1.45;">
    I certify on my honor that the above is a true and correct report of the hours of work performed,
    record of which was made daily at the time of arrival and departure from office.
</p>

{{-- Employee signature line --}}
<table width="100%" style="border-collapse: collapse; margin-top: 7pt;">
    <tr>
        <td style="border-bottom: 0.5pt solid #000; text-align: center; font-weight: bold; font-size: 7pt; padding-top: 30pt;">
            {{ strtoupper($emp['full_name']) }}
        </td>
    </tr>
</table>

{{-- Verified --}}
<p style="font-size: 5.5pt; font-style: italic; margin: 5pt 0 0 0;">VERIFIED as to the prescribed office hours:</p>

{{-- Supervisor signature line --}}
<table width="100%" style="border-collapse: collapse; margin-top: 7pt;">
    <tr>
        <td style="border-bottom: 0.5pt solid #000; text-align: center; font-weight: bold; font-size: 7pt; padding-top: 40pt;">
            @if ($signatory) {{ strtoupper($signatory) }} @else &nbsp; @endif
        </td>
    </tr>
</table>

{{-- In Charge label --}}
<p style="text-align: center; font-size: 6pt; margin: 2pt 0 0 0;">In Charge</p>
