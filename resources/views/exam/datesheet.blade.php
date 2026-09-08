{{--
    The datesheet: the sheet pinned to the notice board and sent home three
    weeks before the exams.

    Grouped by day, because that is how a child reads it. Cancelled papers are
    struck through rather than dropped — a paper that has been called off is
    exactly what a family needs to be told.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date Sheet - {{ $sheet['exam']->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #222;
            font-size: 13px;
            line-height: 1.5;
            background: #f3f4f6;
        }
        .sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #222;
            padding: 26px 32px;
            border-radius: 6px;
        }
        .head { text-align: center; border-bottom: 2px solid #222; padding-bottom: 12px; }
        .head h1 { margin: 0; font-size: 22px; text-transform: uppercase; }
        .head .sub { margin: 4px 0 0; font-size: 12px; color: #444; }
        .head h2 { margin: 12px 0 0; font-size: 16px; text-transform: uppercase; letter-spacing: 1px; }
        .head .span { margin: 4px 0 0; font-size: 12.5px; color: #444; }
        .for {
            margin: 14px 0 0;
            padding: 10px 12px;
            background: #f7f7f7;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }
        .day { margin-top: 18px; }
        .day h3 {
            margin: 0 0 6px;
            font-size: 13.5px;
            background: #eee;
            border: 1px solid #ccc;
            border-radius: 4px 4px 0 0;
            padding: 7px 10px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 6px 9px; text-align: left; }
        th { background: #f4f4f4; font-size: 11.5px; text-transform: uppercase; letter-spacing: .4px; }
        td.num, th.num { text-align: right; }
        td.mid, th.mid { text-align: center; }
        tr.cancelled td { color: #999; text-decoration: line-through; }
        tr.cancelled td.tag { text-decoration: none; color: #b00020; font-weight: bold; }
        .empty { margin-top: 20px; padding: 20px; text-align: center; color: #777; border: 1px dashed #ccc; border-radius: 4px; }
        .notes { margin-top: 22px; font-size: 12px; }
        .notes ul { margin: 6px 0 0; padding-left: 18px; }
        .signatures {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-top: 46px;
            text-align: center;
        }
        .signatures div { border-top: 1px solid #444; padding-top: 6px; font-size: 11.5px; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { border: 1px solid #222; border-radius: 0; margin: 0; max-width: none; }
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="head">
        <h1>{{ $school?->name ?? 'School' }}</h1>
        @if ($school?->address)
            <p class="sub">{{ $school->address }}</p>
        @endif
        <h2>Date Sheet &mdash; {{ $sheet['exam']->name }}</h2>
        @if ($sheet['first_date'])
            <p class="span">
                {{ \Illuminate\Support\Carbon::parse($sheet['first_date'])->format('d M Y') }}
                @if ($sheet['last_date'] && $sheet['last_date'] !== $sheet['first_date'])
                    &ndash; {{ \Illuminate\Support\Carbon::parse($sheet['last_date'])->format('d M Y') }}
                @endif
            </p>
        @endif
    </div>

    @if ($heading)
        <p class="for">{{ $heading }}</p>
    @endif

    @forelse ($sheet['days'] as $day)
        <div class="day">
            <h3>
                {{ $day['day_name'] }},
                {{ \Illuminate\Support\Carbon::parse($day['date'])->format('d M Y') }}
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Class</th>
                        <th class="mid">Time</th>
                        <th class="mid">Duration</th>
                        <th class="num">Total</th>
                        <th class="num">Passing</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($day['papers'] as $paper)
                    <tr class="{{ $paper['is_cancelled'] ? 'cancelled' : '' }}">
                        <td>
                            {{ $paper['subject'] ?? '—' }}
                            @if ($paper['is_cancelled'])
                                <span class="tag">(cancelled)</span>
                            @endif
                        </td>
                        <td>
                            {{ $paper['class'] ?? 'All classes' }}{{ $paper['section'] ? ' — '.$paper['section'] : '' }}
                        </td>
                        <td class="mid">{{ $paper['start_time'] }} &ndash; {{ $paper['end_time'] }}</td>
                        <td class="mid">{{ $paper['duration'] ?? '—' }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($paper['total_marks'], 2), '0'), '.') }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($paper['passing_marks'], 2), '0'), '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="empty">No papers have been put on the timetable for this exam yet.</p>
    @endforelse

    @if ($sheet['paper_count'] > 0)
        <div class="notes">
            <strong>Please note</strong>
            <ul>
                <li>Children should be seated fifteen minutes before the paper begins.</li>
                <li>Roll number slips are to be brought to every paper.</li>
                <li>Any change to this sheet will be sent home in writing.</li>
            </ul>
        </div>

        <div class="signatures">
            <div>Exam Controller</div>
            <div>Principal</div>
        </div>
    @endif
</div>
</body>
</html>
