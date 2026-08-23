<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inspection Report</title>
    @php
        $hasConcerns = $inspection->overall_result === 'concerns';
        $scoreLabel = $score === null ? 'N/A' : $score . '%';
        $scoreColor = $score === null ? '#868c95' : ($score >= 90 ? '#0d9268' : ($score >= 70 ? '#c07d0a' : '#d23a2c'));
        $resultColor = $hasConcerns ? '#c07d0a' : '#0d9268';
        $roleLabels = ['super-admin'=>'Super Admin','ceo'=>'CEO','manager'=>'Manager','accountant'=>'Accountant','head-of-procurement'=>'Procurement Officer','receptionist'=>'Receptionist','quality-control'=>'Quality Control','staff'=>'Staff'];
        $roleLabel = $inspectorRole ? ($roleLabels[$inspectorRole] ?? ucfirst(str_replace('-', ' ', $inspectorRole))) : 'Inspector';
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #17191e; padding: 40px 44px; }
        .brand { font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; color: #0d9268; font-weight: bold; }
        h1 { font-size: 24px; font-weight: 700; margin: 4px 0 0; }
        .hdr { width: 100%; border-bottom: 2px solid #17191e; padding-bottom: 16px; margin-bottom: 18px; }
        .hdr td { vertical-align: top; }
        .score-badge { display: inline-block; border: 3px solid {{ $scoreColor }}; color: {{ $scoreColor }}; border-radius: 999px; width: 78px; height: 78px; text-align: center; }
        .score-badge .n { display: block; font-size: 22px; font-weight: 700; line-height: 72px; text-align: center; }
        .meta { width: 100%; margin-bottom: 22px; }
        .meta td { padding: 3px 0; font-size: 11.5px; }
        .meta .k { color: #868c95; width: 130px; }
        .meta .v { color: #17191e; font-weight: 600; }
        .pill { display: inline-block; padding: 2px 10px; border-radius: 5px; font-size: 11px; font-weight: 700; }
        .sec { margin-bottom: 4px; }
        .sec-title { font-size: 13px; font-weight: 700; background: #f5f6f5; padding: 7px 10px; border-radius: 6px; margin: 14px 0 6px; }
        .item { border-bottom: 1px solid #eef0ee; padding: 8px 2px; }
        .item table { width: 100%; }
        .item td { vertical-align: top; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; color: #fff; }
        .b-pass { background: #0d9268; }
        .b-fail { background: #d23a2c; }
        .b-na   { background: #868c95; }
        .b-none { background: #d0d3d0; }
        .label { font-size: 12px; }
        .note { font-size: 11px; color: #565b63; margin-top: 3px; }
        .photos { margin-top: 6px; }
        .photos img { height: 78px; border: 1px solid #e4e6e4; border-radius: 4px; margin: 0 5px 5px 0; }
        .attest { margin-top: 26px; border-top: 1px solid #e4e6e4; padding-top: 14px; }
        .attest .sig { font-size: 13px; font-weight: 700; color: #17191e; }
        .attest .sub { font-size: 10.5px; color: #868c95; margin-top: 3px; }
        .footer { margin-top: 26px; font-size: 9.5px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>

<table class="hdr">
    <tr>
        <td>
            <div class="brand">CityStake · Quality Control</div>
            <h1>Inspection Report</h1>
        </td>
        <td style="text-align: right; width: 90px;">
            <div class="score-badge"><span class="n">{{ $scoreLabel }}</span></div>
        </td>
    </tr>
</table>

<table class="meta">
    <tr><td class="k">Property</td><td class="v">{{ $inspection->building?->name }}</td>
        <td class="k">Overall result</td><td><span class="pill" style="background: {{ $resultColor }}22; color: {{ $resultColor }};">{{ $hasConcerns ? 'CONCERNS' : 'PASSED' }}</span></td></tr>
    <tr><td class="k">Unit</td><td class="v">Unit {{ $inspection->unit?->unit_number }} · {{ $inspection->unit?->unitType?->name }}</td>
        <td class="k">Inspector</td><td class="v">{{ $inspection->inspector?->name ?? '—' }}</td></tr>
    <tr><td class="k">Completed</td><td class="v">{{ optional($inspection->completed_at)->format('d M Y, g:i A') ?? '—' }}</td>
        <td class="k">Score</td><td class="v" style="color: {{ $scoreColor }};">{{ $scoreLabel }}{{ $score === null ? '' : ' (passed items)' }}</td></tr>
</table>

@foreach ($groups as $group)
    <div class="sec">
        <div class="sec-title">{{ $group['title'] }}</div>
        @foreach ($group['items'] as $item)
            <div class="item">
                <table>
                    <tr>
                        <td style="width: 46px;">
                            @php $r = $item['result']; @endphp
                            <span class="badge {{ $r === 'pass' ? 'b-pass' : ($r === 'fail' ? 'b-fail' : ($r === 'na' ? 'b-na' : 'b-none')) }}">
                                {{ $r === 'pass' ? 'PASS' : ($r === 'fail' ? 'FAIL' : ($r === 'na' ? 'N/A' : '—')) }}
                            </span>
                        </td>
                        <td>
                            <div class="label">{{ $item['label'] }}</div>
                            @if (!empty($item['note']))
                                <div class="note">{{ $item['note'] }}</div>
                            @endif
                            @if (!empty($item['photos']))
                                <div class="photos">
                                    @foreach ($item['photos'] as $src)
                                        <img src="{{ $src }}" alt="">
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>
@endforeach

<div class="attest">
    <div class="sig">Inspected &amp; signed off by {{ $inspection->inspector?->name ?? '—' }}</div>
    <div class="sub">
        {{ $roleLabel }} ·
        {{ optional($inspection->completed_at)->format('d M Y, g:i A') ?? '—' }} ·
        This is a digitally attested record generated by CityStake Bookings.
    </div>
</div>

<div class="footer">
    Generated {{ $generatedAt->format('d M Y, g:i A') }} · CityStake Bookings Quality Control
</div>

</body>
</html>
