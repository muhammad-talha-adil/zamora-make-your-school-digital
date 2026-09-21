{{--
    The staff ID card — the same CR80 / CNIC print sizing as
    `resources/views/student/id-card.blade.php`, laid out for a member of
    staff instead of a child: employee number and designation in place of
    admission number and guardian phone.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff ID Cards</title>
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
        .qr {
            width: 12mm;
            height: 12mm;
            flex-shrink: 0;
            align-self: flex-end;
        }
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
    @php $staff = $card['staff']; @endphp
    <div class="card">
        <div class="top">
            <div class="school">{{ $school?->name ?? 'School' }}</div>
            @if ($card['campus'])
                <div class="campus">{{ $card['campus'] }} Campus</div>
            @endif
        </div>

        <div class="body">
            @if ($staff->photo_url)
                <img class="photo" src="{{ $staff->photo_url }}" alt="">
            @else
                <div class="photo photo-empty">No<br>photo</div>
            @endif

            <div class="fields">
                <div class="name">{{ $staff->user?->name ?? '—' }}</div>
                <div class="row"><span class="k">Emp No</span><span class="v">{{ $staff->employee_no ?? '—' }}</span></div>
                <div class="row"><span class="k">Designation</span><span class="v">{{ $card['designation'] ?? '—' }}</span></div>
                <div class="row"><span class="k">Department</span><span class="v">{{ $card['department'] ?? '—' }}</span></div>
                <div class="row"><span class="k">Phone</span><span class="v">{{ $staff->phone ?? '—' }}</span></div>
            </div>

            @if (! empty($card['attendance_qr']))
                <img class="qr" src="{{ $card['attendance_qr'] }}" alt="Scan to mark attendance">
            @endif
        </div>

        <div class="foot">
            <span>Staff of {{ $school?->name ?? 'School' }}</span>
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
