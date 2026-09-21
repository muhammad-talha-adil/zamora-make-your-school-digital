{{--
    The student ID card — two-sided.

    Sized to Pakistan's CNIC / CR80 standard (85.60mm x 53.98mm) so a school
    can print onto real ID card stock, or laminate them straight off an A4
    sheet without trimming to a different size. Each card prints as a front
    panel and a back panel side by side (browser-print friendly — no duplex
    needed), matching the front/back reference designs the school shared.
    The photograph is already uploaded and stored; this is the same kind of
    print view as the fee challan.
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
            --brand: #0f5fa6;
            --brand-dark: #0a3d6e;
            --brand-light: #eaf3fb;
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
        .card-pair {
            display: flex;
            gap: 3mm;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .card {
            width: var(--card-w);
            height: var(--card-h);
            background: #fff;
            border: 0.3mm solid #ccc;
            border-radius: 3mm;
            overflow: hidden;
            position: relative;
        }

        /* ===== Front ===== */
        .card-front { display: flex; flex-direction: column; }
        .card-front .header {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: #fff;
            padding: 2mm 3mm;
            display: flex;
            align-items: center;
            gap: 2mm;
        }
        .card-front .logo {
            width: 7mm;
            height: 7mm;
            border-radius: 50%;
            background: #fff;
            object-fit: contain;
            flex-shrink: 0;
        }
        .card-front .school-name {
            font-size: 2.9mm;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.15;
        }
        .card-front .campus { font-size: 2mm; opacity: .85; margin-top: .3mm; }
        .card-front .body {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2mm 3mm 1mm;
            min-height: 0;
        }
        .photo {
            width: 15mm;
            height: 15mm;
            border-radius: 50%;
            border: 0.5mm solid var(--brand);
            object-fit: cover;
            background: #eee;
            flex-shrink: 0;
        }
        .photo-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7mm;
            color: #999;
            text-align: center;
        }
        .card-front .name {
            font-size: 2.9mm;
            font-weight: bold;
            margin-top: 1mm;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        .card-front .role {
            font-size: 2mm;
            color: var(--brand-dark);
            font-weight: 600;
            margin-top: .3mm;
        }
        .card-front .fields {
            width: 100%;
            font-size: 2.1mm;
            line-height: 1.5;
            margin-top: 1.2mm;
        }
        .card-front .fields .row { display: flex; justify-content: center; gap: 1.2mm; }
        .card-front .fields .k { color: #666; }
        .card-front .fields .v { font-weight: bold; }
        .card-front .accent-bar {
            height: 1.5mm;
            background: var(--brand);
        }

        /* ===== Back ===== */
        .card-back { display: flex; flex-direction: column; }
        .card-back .header {
            background: var(--brand-dark);
            height: 3mm;
        }
        .card-back .content {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 3mm;
            padding: 2mm 3mm;
            min-height: 0;
        }
        .card-back .qr {
            width: 17mm;
            height: 17mm;
            flex-shrink: 0;
        }
        .card-back .info {
            flex: 1;
            min-width: 0;
            font-size: 1.9mm;
            line-height: 1.5;
            color: #333;
        }
        .card-back .info .school-name {
            font-size: 2.3mm;
            font-weight: bold;
            color: var(--brand-dark);
            margin-bottom: .6mm;
        }
        .card-back .info .muted { color: #666; }
        .card-back .footer {
            border-top: 0.2mm dashed #bbb;
            padding: 1mm 3mm;
            font-size: 1.7mm;
            color: #555;
            text-align: center;
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
    <div class="card-pair">
        <div class="card card-front">
            <div class="header">
                @if ($school?->logo_path)
                    <img class="logo" src="{{ $school->logo_path }}" alt="">
                @endif
                <div>
                    <div class="school-name">{{ $school?->name ?? 'School' }}</div>
                    @if ($card['campus'])
                        <div class="campus">{{ $card['campus'] }} Campus</div>
                    @endif
                </div>
            </div>

            <div class="body">
                @if ($student->image_url)
                    <img class="photo" src="{{ $student->image_url }}" alt="">
                @else
                    <div class="photo photo-empty">No<br>photo</div>
                @endif

                <div class="name">{{ $student->user?->name ?? '—' }}</div>
                <div class="role">Student</div>

                <div class="fields">
                    <div class="row"><span class="k">Adm No:</span><span class="v">{{ $student->admission_no ?? '—' }}</span></div>
                    <div class="row"><span class="k">Class:</span><span class="v">{{ $card['class'] ?? '—' }}{{ $card['section'] ? ' — '.$card['section'] : '' }}</span></div>
                    <div class="row"><span class="k">Session:</span><span class="v">{{ $card['session'] ?? '—' }}</span></div>
                </div>
            </div>
            <div class="accent-bar"></div>
        </div>

        <div class="card card-back">
            <div class="header"></div>
            <div class="content">
                @if (! empty($card['attendance_qr']))
                    <img class="qr" src="{{ $card['attendance_qr'] }}" alt="Scan to mark attendance">
                @endif
                <div class="info">
                    <div class="school-name">{{ $school?->name ?? 'School' }}</div>
                    @if ($school?->address)
                        <div>{{ $school->address }}</div>
                    @endif
                    @if ($school?->phone)
                        <div>Ph: {{ $school->phone }}</div>
                    @endif
                    <div class="muted">Guardian: {{ $card['guardian_phone'] ?? '—' }}</div>
                    <div class="muted">D.O.B: {{ $student->dob?->format('d M Y') ?? '—' }}</div>
                </div>
            </div>
            <div class="footer">If found, please return this card to the school office</div>
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
