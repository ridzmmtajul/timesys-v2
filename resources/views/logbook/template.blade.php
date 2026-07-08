<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
<style>
    @page { size: A4 portrait; margin: 14mm; }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 9pt; color: #000; margin: 0; padding: 0; }
    table { border-collapse: collapse; width: 100%; }
    p { margin: 0; padding: 0; }

    .header { text-align: center; margin-bottom: 14pt; }
    .header h1 { font-size: 14pt; margin: 0; letter-spacing: 0.5pt; }
    .header .office { font-size: 10pt; margin-top: 4pt; }
    .header .date { font-size: 9pt; margin-top: 2pt; color: #333; }
    .header .date strong { text-decoration: underline; }

    .generated { text-align: right; font-size: 7.5pt; color: #555; margin-bottom: 6pt; }

    th, td { border: 1pt solid #999; padding: 5pt 6pt; text-align: center; }
    thead th { background: #eef7f6; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3pt; }
    td.name, th.name { text-align: left; }
    td.undertime { color: #c0392b; font-weight: bold; }
</style>
</head>
<body>

<div class="header">
    <h1>DAILY ATTENDANCE LOGBOOK</h1>
    <div class="office">
        {{ $office }}@if($office_division) &mdash; {{ $office_division }}@endif
    </div>
    <div class="date">for <strong>{{ $date }}</strong></div>
</div>

<div class="generated">Generated: {{ $generated_at }}</div>

<table>
    <thead>
        <tr>
            <th class="name" rowspan="2">Employee Name</th>
            <th colspan="2">AM</th>
            <th colspan="2">PM</th>
            <th rowspan="2">Undertime</th>
        </tr>
        <tr>
            <th>Arrival</th>
            <th>Departure</th>
            <th>Arrival</th>
            <th>Departure</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td class="name">{{ $row['employee_name'] }}</td>
                <td>{{ $row['am_arrival'] ?: '—' }}</td>
                <td>{{ $row['am_departure'] ?: '—' }}</td>
                <td>{{ $row['pm_arrival'] ?: '—' }}</td>
                <td>{{ $row['pm_departure'] ?: '—' }}</td>
                <td class="undertime">{{ $row['undertime_label'] ?: '' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No attendance logged for this date.</td>
            </tr>
        @endforelse
    </tbody>
</table>

</body>
</html>
