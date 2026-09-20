{{--
    The student ID card.

    Sized to Pakistan's CNIC / CR80 standard (85.60mm x 53.98mm) so a school
    can print onto real ID card stock, or laminate them straight off an A4
    sheet without trimming to a different size. The photograph is already
    uploaded and stored; this is the same kind of print view as the fee
    challan.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Cards</title>
    <style>
        * { box-sizing: border-box; }
        :root {
            /* CR80 / CNIC card size. */
            --card-w: 85.6mm;
            --card-h: 53.98mm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 12mm;
            background: #f3f4f6;
            color: #222;
        }
        .controls {
            text-align: center;
            margin-bottom: 16px;
        }
        .btn {
            background-color: #0056b3;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        .sheet {
            display: flex;
            flex-wrap: wrap;
            gap: 5mm;
            justify-content: center;
        }
        .card {
            width: var(--card-w);
            height: var(--card-h);
            background: #fff;
            border: 1px solid #222;
            border-radius: 3mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .card .top {
            background: #1f2937;
            color: #fff;
            padding: 2mm 3mm;
            text-align: center;
        }
        .card .top .school {
            font-size: 3mm;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .card .top .campus { font-size: 2.2mm; opacity: .85; margin-top: .5mm; }
        .card .body { display: flex; gap: 2.5mm; padding: 2.5mm 3mm; flex: 1; min-height: 0; }
        .photo {
            width: 16mm;
            height: 20mm;
            border: 0.3mm solid #999;
            border-radius: 1mm;
            object-fit: cover;
            background: #eee;
            flex-shrink: 0;
        }
        .photo-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2mm;
            color: #999;
            text-align: center;
        }
        .fields { font-size: 2.4mm; line-height: 1.45; flex: 1; min-width: 0; overflow: hidden; }
        .fields .name {
            font-size: 3mm;
            font-weight: bold;
            margin-bottom: .8mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .fields .row { display: flex; gap: 1mm; }
        .fields .k { color: #666; min-width: 13mm; }
        .fields .v { font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card .foot {
            border-top: 0.2mm dashed #bbb;
            padding: 1mm 3mm;
            font-size: 1.9mm;
            color: #555;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .controls { display: none; }
            .sheet { gap: 4mm; }
            @page {
                size: auto;
                margin: 8mm;
            }
        }
    </style>
</head>
<body>
<div class="controls">
    <button class="btn" onclick="window.print()">Print ID Cards</button>
</div>
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
            @if ($student->image_url)
                <img class="photo" src="{{ $student->image_url }}" alt="">
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
            <span>If found, please return</span>
        </div>
    </div>
@endforeach
</div>
<script>
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.print();
        }, 400);
    });
</script>
</body>
</html>
