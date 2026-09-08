{{--
    The result card.

    One card per page when a whole section is printed, which is how a school
    runs off forty of them on a Thursday afternoon. Everything on it comes from
    a record — marks, grace, pass or fail, position, attendance — so the card
    never says anything the screens do not.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Card{{ count($cards) === 1 ? ' - '.($cards[0]['student']?->user?->name ?? '') : '' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #222;
            font-size: 12.5px;
            line-height: 1.45;
            background: #f3f4f6;
        }
        .card {
            max-width: 820px;
            margin: 0 auto 24px;
            background: #fff;
            border: 2px solid #222;
            padding: 26px 32px;
            border-radius: 6px;
        }
        .head { text-align: center; border-bottom: 2px solid #222; padding-bottom: 12px; }
        .head h1 { margin: 0; font-size: 22px; text-transform: uppercase; }
        .head .sub { margin: 4px 0 0; font-size: 12px; color: #444; }
        .head h2 {
            margin: 12px 0 0;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .who {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 24px;
            margin: 16px 0;
            padding: 12px;
            background: #f7f7f7;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .who div { display: flex; gap: 6px; }
        .who .label { color: #555; min-width: 96px; }
        .who .value { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #bbb; padding: 6px 8px; text-align: left; }
        th { background: #eee; font-size: 11.5px; text-transform: uppercase; letter-spacing: .4px; }
        td.num, th.num { text-align: right; }
        td.mid, th.mid { text-align: center; }
        tfoot td { font-weight: bold; background: #f2f2f2; }
        .section-title {
            margin: 18px 0 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #444;
        }
        .fail { color: #b00020; font-weight: bold; }
        .pass { color: #14672f; font-weight: bold; }
        .muted { color: #777; }
        .grace-note { font-size: 11px; color: #7a4a00; }
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 16px;
        }
        .summary .box {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
        }
        .summary .box .k { font-size: 10.5px; text-transform: uppercase; color: #666; }
        .summary .box .v { font-size: 17px; font-weight: bold; margin-top: 3px; }
        .remarks {
            margin-top: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px 12px;
            min-height: 46px;
        }
        .remarks .k { font-size: 10.5px; text-transform: uppercase; color: #666; }
        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 46px;
            text-align: center;
        }
        .signatures div { border-top: 1px solid #444; padding-top: 6px; font-size: 11.5px; }
        .printed { margin-top: 14px; text-align: center; font-size: 10.5px; color: #888; }
        @media print {
            body { background: #fff; padding: 0; }
            .card {
                border: 1px solid #222;
                border-radius: 0;
                margin: 0;
                max-width: none;
                page-break-after: always;
            }
            .card:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>
@foreach ($cards as $card)
    @php
        $totals = $card['totals'];
        $result = $card['result'];
        $attendance = $card['attendance'];
    @endphp
    <div class="card">
        <div class="head">
            <h1>{{ $card['school']?->name ?? 'School' }}</h1>
            @if ($card['school']?->address)
                <p class="sub">{{ $card['school']->address }}</p>
            @endif
            @if ($card['campus'])
                <p class="sub">{{ $card['campus']->name }} Campus</p>
            @endif
            <h2>{{ $card['exam']?->name }} &mdash; Result Card</h2>
        </div>

        <div class="who">
            <div><span class="label">Name</span><span class="value">{{ $card['student']?->user?->name ?? '—' }}</span></div>
            <div><span class="label">Class</span><span class="value">{{ $card['class']?->name }}{{ $card['section'] ? ' — '.$card['section']->name : '' }}</span></div>
            <div><span class="label">Admission No</span><span class="value">{{ $card['student']?->admission_no ?? '—' }}</span></div>
            <div><span class="label">Exam</span><span class="value">{{ $card['exam']?->examType?->name ?? $card['exam']?->name }}</span></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th class="num">Total</th>
                    <th class="num">Passing</th>
                    <th class="num">Obtained</th>
                    <th class="num">Grace</th>
                    <th class="num">Marks</th>
                    <th class="num">%</th>
                    <th class="mid">Grade</th>
                    <th class="mid">Result</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($card['subjects'] as $row)
                <tr>
                    <td>
                        {{ $row['subject'] ?? '—' }}
                        @if ($row['role'] === 'elective')
                            <span class="muted">({{ $row['role_label'] }})</span>
                        @endif
                    </td>
                    <td class="num">{{ rtrim(rtrim(number_format($row['total'], 2), '0'), '.') }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($row['passing'], 2), '0'), '.') }}</td>
                    <td class="num">
                        @if ($row['is_absent']) <span class="fail">ABS</span>
                        @elseif ($row['is_exempt']) <span class="muted">Exempt</span>
                        @elseif ($row['obtained'] === null) <span class="muted">—</span>
                        @else {{ rtrim(rtrim(number_format($row['obtained'], 2), '0'), '.') }}
                        @endif
                    </td>
                    <td class="num">
                        @if ($row['grace'])
                            <span class="grace-note">+{{ rtrim(rtrim(number_format($row['grace'], 2), '0'), '.') }}</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td class="num">
                        {{ $row['marks'] === null ? '—' : rtrim(rtrim(number_format($row['marks'], 2), '0'), '.') }}
                    </td>
                    <td class="num">{{ $row['percentage'] === null ? '—' : number_format($row['percentage'], 2) }}</td>
                    <td class="mid">{{ $row['grade'] ?? '—' }}</td>
                    <td class="mid">
                        @if ($row['is_pass'] === true) <span class="pass">Pass</span>
                        @elseif ($row['is_pass'] === false) <span class="fail">Fail</span>
                        @else <span class="muted">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="num">{{ rtrim(rtrim(number_format($totals['maximum'], 2), '0'), '.') }}</td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num">
                        @if ($totals['grace_total'] > 0)
                            +{{ rtrim(rtrim(number_format($totals['grace_total'], 2), '0'), '.') }}
                        @endif
                    </td>
                    <td class="num">
                        {{ $totals['obtained'] === null ? '—' : rtrim(rtrim(number_format($totals['obtained'], 2), '0'), '.') }}
                    </td>
                    <td class="num">{{ $totals['percentage'] === null ? '—' : number_format($totals['percentage'], 2) }}</td>
                    <td class="mid">{{ $totals['grade'] ?? '—' }}</td>
                    <td class="mid">
                        @if ($result['status'] === 'pass') <span class="pass">Pass</span>
                        @elseif ($result['status'] === 'fail') <span class="fail">Fail</span>
                        @else <span class="muted">Pending</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>

        @if (! empty($card['additional_subjects']))
            {{-- Below the total, because they change neither the percentage nor
                 the position. --}}
            <p class="section-title">Additional subjects &mdash; not counted towards the total</p>
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th class="num">Total</th>
                        <th class="num">Marks</th>
                        <th class="num">%</th>
                        <th class="mid">Grade</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($card['additional_subjects'] as $row)
                    <tr>
                        <td>{{ $row['subject'] ?? '—' }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($row['total'], 2), '0'), '.') }}</td>
                        <td class="num">{{ $row['marks'] === null ? '—' : rtrim(rtrim(number_format($row['marks'], 2), '0'), '.') }}</td>
                        <td class="num">{{ $row['percentage'] === null ? '—' : number_format($row['percentage'], 2) }}</td>
                        <td class="mid">{{ $row['grade'] ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        <div class="summary">
            <div class="box">
                <div class="k">Percentage</div>
                <div class="v">{{ $totals['percentage'] === null ? '—' : number_format($totals['percentage'], 2).'%' }}</div>
            </div>
            <div class="box">
                <div class="k">Grade</div>
                <div class="v">{{ $totals['grade'] ?? '—' }}</div>
            </div>
            <div class="box">
                <div class="k">Position</div>
                {{-- Blank rather than invented: a card printed before the
                     results were published has no position yet. --}}
                <div class="v">{{ $card['position']['line'] ?? '—' }}</div>
            </div>
            <div class="box">
                <div class="k">Attendance</div>
                <div class="v" style="font-size: 13px;">{{ $attendance['line'] ?? '—' }}</div>
            </div>
        </div>

        @if ($result['status'] === 'fail' && $result['failed_subjects'] > 0)
            <p class="fail" style="margin-top: 12px;">
                Failed in {{ $result['failed_subjects'] }}
                {{ $result['failed_subjects'] === 1 ? 'subject' : 'subjects' }}.
            </p>
        @endif

        <div class="remarks">
            <div class="k">Class teacher's remarks</div>
            <div>{{ $card['remarks'] }}</div>
        </div>

        <div class="signatures">
            <div>Class Teacher</div>
            <div>Principal</div>
            <div>Parent / Guardian</div>
        </div>

        <p class="printed">Printed {{ $card['printed_at']->format('d M Y, g:i a') }}</p>
    </div>
@endforeach
</body>
</html>
