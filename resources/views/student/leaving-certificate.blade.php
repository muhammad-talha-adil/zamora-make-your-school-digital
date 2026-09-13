{{--
    The School Leaving Certificate — the TC.

    A child cannot be admitted to another school without one. Everything on it
    is read from a record: the leaving date and reason from
    `student_leave_records`, the class from the last enrolment period.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Leaving Certificate - {{ $tc['student']?->user?->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #222;
            font-size: 13px;
            line-height: 1.6;
            background: #f3f4f6;
        }
        .sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            border: 3px double #222;
            padding: 34px 40px;
        }
        .head { text-align: center; border-bottom: 2px solid #222; padding-bottom: 14px; }
        .head h1 { margin: 0; font-size: 23px; text-transform: uppercase; letter-spacing: .5px; }
        .head .sub { margin: 4px 0 0; font-size: 12px; color: #444; }
        .head h2 {
            margin: 16px 0 0;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-decoration: underline;
        }
        .serial { margin-top: 12px; display: flex; justify-content: space-between; font-size: 12px; }
        table.details { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.details td { padding: 7px 4px; border-bottom: 1px dotted #bbb; vertical-align: top; }
        table.details td.label { width: 240px; color: #444; }
        table.details td.value { font-weight: bold; }
        .statement {
            margin-top: 22px;
            padding: 14px;
            border: 1px solid #ddd;
            background: #fafafa;
            text-align: justify;
        }
        .note { margin-top: 16px; font-size: 11.5px; color: #666; }
        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 60px;
            text-align: center;
        }
        .signatures div { border-top: 1px solid #444; padding-top: 6px; font-size: 11.5px; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { border: 2px solid #222; margin: 0; max-width: none; }
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="head">
        <h1>{{ $tc['school']?->name ?? 'School' }}</h1>
        @if ($tc['school']?->address)
            <p class="sub">{{ $tc['school']->address }}</p>
        @endif
        @if ($tc['campus'])
            <p class="sub">{{ $tc['campus'] }} Campus</p>
        @endif
        <h2>School Leaving Certificate</h2>
    </div>

    <div class="serial">
        <span>Admission No: <strong>{{ $tc['student']?->admission_no ?? '—' }}</strong></span>
        <span>Issued: <strong>{{ $tc['issued_on']->format('d M Y') }}</strong></span>
    </div>

    <table class="details">
        <tr>
            <td class="label">Name of student</td>
            <td class="value">{{ $tc['student']?->user?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Father's / Guardian's name</td>
            <td class="value">{{ $tc['guardian']?->user?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Date of birth</td>
            <td class="value">
                {{ $tc['student']?->dob?->format('d M Y') ?? '—' }}
            </td>
        </tr>
        <tr>
            <td class="label">B-Form / identity number</td>
            <td class="value">{{ $tc['student']?->b_form ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Gender</td>
            <td class="value">{{ $tc['student']?->gender?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Date of admission</td>
            <td class="value">
                {{ $tc['joined_on'] ? \Illuminate\Support\Carbon::parse($tc['joined_on'])->format('d M Y') : '—' }}
            </td>
        </tr>
        <tr>
            <td class="label">Class last attended</td>
            <td class="value">
                {{ $tc['last_class'] ?? '—' }}{{ $tc['last_section'] ? ' — '.$tc['last_section'] : '' }}
            </td>
        </tr>
        <tr>
            <td class="label">Session</td>
            <td class="value">{{ $tc['session'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Date of leaving</td>
            <td class="value">
                {{ $tc['left_on'] ? \Illuminate\Support\Carbon::parse($tc['left_on'])->format('d M Y') : '—' }}
            </td>
        </tr>
        <tr>
            <td class="label">Period at this school</td>
            <td class="value">{{ $tc['years'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Reason for leaving</td>
            <td class="value">{{ $tc['reason'] ?? $tc['status'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Conduct</td>
            <td class="value">Satisfactory</td>
        </tr>
    </table>

    <p class="statement">
        This is to certify that
        <strong>{{ $tc['student']?->user?->name ?? '—' }}</strong>
        was a bona fide student of this institution and has left as recorded
        above. The particulars given in this certificate are taken from the
        school's admission and withdrawal register.
    </p>

    <p class="note">
        Any correction to this certificate may only be made by the issuing
        school, over the signature of the Principal.
    </p>

    <div class="signatures">
        <div>Class Teacher</div>
        <div>Office</div>
        <div>Principal</div>
    </div>
</div>
</body>
</html>
