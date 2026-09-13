{{--
    The student ID card.

    The photograph is already uploaded and stored; this is the same kind of
    print view as the fee challan. Eight to a page, because a school prints a
    section at a time and then cuts them.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Cards</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 18px;
            background: #f3f4f6;
            color: #222;
        }
        .sheet {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            max-width: 840px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border: 1.5px solid #222;
            border-radius: 8px;
            padding: 0;
            overflow: hidden;
            /* CR80 proportions, the size a school's laminator takes. */
            height: 210px;
            display: flex;
            flex-direction: column;
        }
        .card .top {
            background: #1f2937;
            color: #fff;
            padding: 7px 10px;
            text-align: center;
        }
        .card .top .school { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .card .top .campus { font-size: 9px; opacity: .85; margin-top: 1px; }
        .card .body { display: flex; gap: 10px; padding: 9px 10px; flex: 1; }
        .photo {
            width: 62px;
            height: 76px;
            border: 1px solid #999;
            border-radius: 3px;
            object-fit: cover;
            background: #eee;
            flex-shrink: 0;
        }
        .photo-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #999;
            text-align: center;
        }
        .fields { font-size: 10px; line-height: 1.5; flex: 1; min-width: 0; }
        .fields .name {
            font-size: 12.5px;
            font-weight: bold;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .fields .row { display: flex; gap: 4px; }
        .fields .k { color: #666; min-width: 58px; }
        .fields .v { font-weight: bold; }
        .card .foot {
            border-top: 1px dashed #bbb;
            padding: 5px 10px;
            font-size: 8.5px;
            color: #555;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { max-width: none; gap: 8px; }
            .card { break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="sheet">
@foreach ($cards as $card)
    @php $student = $card['student']; @endphp
    <div class="card">
        <div class="top">
            <div class="school">{{ $school?->name ?? 'School' }}</div>
            @if ($card['campus'])
                <div class="campus">{{ $card['campus'] }} Campus</div>
            @endif
        </div>

        <div class="body">
            @if ($student->image)
                <img class="photo" src="{{ asset('storage/'.$student->image) }}" alt="">
            @else
                <div class="photo photo-empty">No<br>photo</div>
            @endif

            <div class="fields">
                <div class="name">{{ $student->user?->name ?? '—' }}</div>
                <div class="row"><span class="k">Adm No</span><span class="v">{{ $student->admission_no ?? '—' }}</span></div>
                <div class="row"><span class="k">Class</span><span class="v">{{ $card['class'] ?? '—' }}{{ $card['section'] ? ' — '.$card['section'] : '' }}</span></div>
                <div class="row"><span class="k">Session</span><span class="v">{{ $card['session'] ?? '—' }}</span></div>
                <div class="row"><span class="k">D.O.B</span><span class="v">{{ $student->dob?->format('d M Y') ?? '—' }}</span></div>
                <div class="row"><span class="k">Guardian</span><span class="v">{{ $card['guardian_phone'] ?? '—' }}</span></div>
            </div>
        </div>

        <div class="foot">
            <span>Valid for the session shown</span>
            <span>If found, please return to the school</span>
        </div>
    </div>
@endforeach
</div>
</body>
</html>
