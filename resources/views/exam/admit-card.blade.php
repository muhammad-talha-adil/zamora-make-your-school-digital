{{--
    Admit cards: one per registered child, one to a page, with the date sheet
    of their class on it so the card is all they need to bring.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admit Cards - {{ $exam->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #222; font-size: 13px; line-height: 1.5; background: #f3f4f6; }
        .bar { max-width: 760px; margin: 0 auto 12px; text-align: right; }
        .bar button { background: #1d4ed8; color: #fff; border: 0; border-radius: 6px; padding: 8px 18px; font-size: 14px; cursor: pointer; }
        .card { max-width: 760px; margin: 0 auto 20px; background: #fff; border: 2px solid #222; border-radius: 6px; padding: 22px 28px; }
        .head { text-align: center; border-bottom: 2px solid #222; padding-bottom: 10px; }
        .head h1 { margin: 0; font-size: 21px; text-transform: uppercase; }
        .head .sub { margin: 3px 0 0; font-size: 12px; color: #444; }
        .head h2 { margin: 10px 0 0; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; }
        .who { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px 24px; margin: 14px 0; }
        .who .label { color: #666; font-size: 11px; text-transform: uppercase; display: block; }
        .who .value { font-weight: bold; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 3px 8px; text-align: left; }
        th { background: #f4f4f4; font-size: 10.5px; text-transform: uppercase; }
        .mid { text-align: center; }
        .signatures { display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; margin-top: 30px; text-align: center; }
        .signatures div { border-top: 1px solid #444; padding-top: 6px; font-size: 11.5px; }
        .empty { padding: 30px; text-align: center; color: #777; background: #fff; border: 1px dashed #ccc; max-width: 760px; margin: 0 auto; }
        @media (max-width: 640px) {
            body { padding: 8px; }
            .card { padding: 14px 12px; max-width: 100%; }
            .head h1 { font-size: 17px; }
            table { display: block; max-width: 100%; overflow-x: auto; }
            .signatures { gap: 16px; }
        }
        @media print {
            body { background: #fff; padding: 0; }
            .bar { display: none; }
            .card { border: 1px solid #222; border-radius: 0; margin: 0; max-width: none; page-break-after: always; }
            .card:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>
<div class="bar"><button type="button" onclick="window.print()">Print</button></div>
@forelse ($cards as $item)
    @php
        $registration = $item['registration'];
        $student = $registration->student;
    @endphp
    <div class="card">
        <div class="head">
            <h1>{{ $school?->name ?? 'School' }}</h1>
            @if ($school?->address)
                <p class="sub">{{ $school->address }}</p>
            @endif
            <h2>Admit Card &mdash; {{ $exam->name }}</h2>
        </div>

        <div class="who">
            <div><span class="label">Name</span><span class="value">{{ $student?->user?->name ?? '—' }}</span></div>
            <div><span class="label">Class</span><span class="value">{{ $registration->class?->name }}{{ $registration->section?->name ? ' — '.$registration->section->name : '' }}</span></div>
            <div><span class="label">Roll No</span><span class="value">{{ $registration->roll_no_snapshot ?? '—' }}</span></div>
            <div><span class="label">Registration No</span><span class="value">{{ $student?->registration_no ?? '—' }}</span></div>
            <div><span class="label">Exam</span><span class="value">{{ $exam->examType?->name ?? $exam->name }}</span></div>
        </div>

        @if ($item['sheet']['paper_count'] > 0)
            <table>
                <thead>
                    <tr><th>Date</th><th>Day</th><th>Subject</th><th class="mid">Time</th></tr>
                </thead>
                <tbody>
                @foreach ($item['sheet']['days'] as $day)
                    @foreach ($day['papers'] as $paper)
                        @continue($paper['is_cancelled'])
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d M Y') }}</td>
                            <td>{{ $day['day_name'] }}</td>
                            <td>{{ $paper['subject'] ?? '—' }}</td>
                            <td class="mid">{{ \Illuminate\Support\Carbon::parse($paper['start_time'])->format('h:i A') }} &ndash; {{ \Illuminate\Support\Carbon::parse($paper['end_time'])->format('h:i A') }}</td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        @else
            <p class="mid">No papers are on the date sheet yet.</p>
        @endif

        <div class="signatures">
            <div>Exam Controller</div>
            <div>Principal</div>
        </div>
    </div>
@empty
    <p class="empty">No children are registered for this exam yet.</p>
@endforelse
</body>
</html>
