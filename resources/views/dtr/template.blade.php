<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
<style>
    @page { size: A4 portrait; margin: 10mm 14mm; }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 7pt; color: #000; margin: 0; padding: 0; }
    table { border-collapse: collapse; }
    p { margin: 0; padding: 0; }
</style>
</head>
<body>

@php
    $fmt = function ($t) {
        if (!$t) return '';
        [$h, $m] = explode(':', $t);
        $h = (int) $h;
        $hour = $h % 12 ?: 12;
        return $hour . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
    };
@endphp

@foreach ($employees as $index => $emp)
    @if ($index > 0)
        <div style="page-break-before: always;"></div>
    @endif

    <table width="100%" style="border-collapse: collapse;">
        <tr>
            <td width="50%" valign="top" style="padding-right: 10mm;">
                @include('dtr._form', ['emp' => $emp, 'days_in_month' => $days_in_month])
            </td>
            <td width="1" style="border-left: 1pt dotted #aaa;"></td>
            <td width="50%" valign="top" style="padding-left: 10mm;">
                @include('dtr._form', ['emp' => $emp, 'days_in_month' => $days_in_month])
            </td>
        </tr>
    </table>
@endforeach

</body>
</html>
