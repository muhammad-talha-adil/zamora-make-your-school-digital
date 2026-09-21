{{--
    The staff ID card — two-sided, the same CR80 / CNIC print sizing as
    `resources/views/student/id-card.blade.php`, laid out for a member of
    staff instead of a child: employee number and designation in place of
    admission number and guardian phone. Uses a dark navy / orange accent
    scheme (instead of the student card's blue / teal) so the two card types
    are distinguishable at a glance, but shares the same wearable-badge
    treatment: wave header, punch hole, and a photo straddling the header
    boundary.
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
            --brand: #16324a;
            --brand-dark: #0a1c2b;
            --brand-light: #e7ebee;
            --accent: #f5811f;
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
            border: 0.3mm solid #d9dde1;
            border-radius: 3mm;
            overflow: hidden;
            position: relative;
            box-shadow: 0 0.3mm 1mm rgba(0, 0, 0, .08);
        }

        /* ===== Shared: punch hole / lanyard loop ===== */
        .punch-hole {
            position: absolute;
            top: 1.1mm;
            left: 50%;
            transform: translateX(-50%);
            width: 3.4mm;
            height: 3.4mm;
            border-radius: 50%;
            background: #fff;
            border: 0.3mm solid rgba(0, 0, 0, .12);
            z-index: 3;
        }

        /* ===== Front ===== */
        .card-front { display: flex; flex-direction: column; }
        .card-front .header {
            position: relative;
            height: 17mm;
            background: linear-gradient(120deg, var(--brand) 0%, var(--brand-dark) 75%);
            clip-path: polygon(0 0, 100% 0, 100% 68%, 70% 84%, 40% 70%, 0 88%);
            padding: 4.6mm 3mm 0;
            display: flex;
            align-items: flex-start;
            gap: 1.8mm;
        }
        .card-front .logo {
            width: 6.5mm;
            height: 6.5mm;
            border-radius: 50%;
            background: #fff;
            object-fit: contain;
            flex-shrink: 0;
            padding: 0.3mm;
        }
        .card-front .school-name {
            font-size: 2.8mm;
            font-weight: 800;
            color: #fff;
            text-transform: uppercase;
            line-height: 1.1;
            letter-spacing: .1px;
        }
        .card-front .campus { font-size: 1.8mm; color: #fff; opacity: .9; margin-top: .3mm; }
        .card-front .body {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 3mm 1.4mm;
            min-height: 0;
        }
        .photo {
            width: 14mm;
            height: 14mm;
            border-radius: 50%;
            border: 0.7mm solid #fff;
            box-shadow: 0 0 0 0.5mm var(--accent);
            object-fit: cover;
            background: #eee;
            flex-shrink: 0;
            margin-top: -8mm;
            position: relative;
            z-index: 2;
        }
        .photo-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6mm;
            color: #999;
            text-align: center;
            background: #f0f0f0;
        }
        .card-front .name {
            font-size: 2.9mm;
            font-weight: 800;
            margin-top: 1.2mm;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            color: #16222b;
        }
        .role-badge {
            display: inline-block;
            font-size: 1.8mm;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(120deg, var(--accent), #c9600f);
            border-radius: 3mm;
            padding: .6mm 2.6mm;
            margin-top: .8mm;
            letter-spacing: .2px;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-front .fields {
            display: grid;
            grid-template-columns: 14.5mm 1fr;
            column-gap: 1.8mm;
            row-gap: 1.3mm;
            align-content: center;
            width: 100%;
            max-width: 62mm;
            flex: 1;
            margin: 1.8mm auto 0;
            padding: 1.8mm 2.8mm;
            font-size: 2.15mm;
            line-height: 1.3;
            background: var(--brand-light);
            border-radius: 1.6mm;
        }
        .card-front .fields .k { color: #6b7176; text-align: left; }
        .card-front .fields .v { font-weight: 700; color: #16222b; text-align: left; }
        .card-front .accent-bar {
            height: 1.3mm;
            background: linear-gradient(90deg, var(--accent), var(--brand), var(--brand-dark));
        }

        /* ===== Back ===== */
        .card-back { display: flex; flex-direction: column; }
        .card-back .top-bar {
            height: 3mm;
            background: linear-gradient(90deg, var(--accent), var(--brand-dark));
        }
        .card-back .content {
            flex: 1;
            display: flex;
            align-items: stretch;
            gap: 2.8mm;
            padding: 2.2mm 3mm;
            min-height: 0;
        }
        .qr-frame {
            flex-shrink: 0;
            align-self: center;
            width: 16.5mm;
            height: 16.5mm;
            padding: 0.9mm;
            background: #fff;
            border: 0.25mm solid #dde2e6;
            border-radius: 1.2mm;
            box-shadow: 0 0.2mm .6mm rgba(0, 0, 0, .08);
        }
        .card-back .qr {
            width: 100%;
            height: 100%;
            display: block;
        }
        .card-back .info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: .8mm;
            font-size: 1.9mm;
            line-height: 1.4;
            color: #333;
        }
        .card-back .info .school-name {
            font-size: 2.3mm;
            font-weight: 800;
            color: var(--brand-dark);
        }
        .card-back .info .school-address {
            color: #555;
        }
        .card-back .datafields {
            display: grid;
            grid-template-columns: 15mm 1fr;
            column-gap: 1.6mm;
            row-gap: .9mm;
            margin-top: .6mm;
            padding: 1.4mm 2mm;
            background: var(--brand-light);
            border-radius: 1.4mm;
            font-size: 1.85mm;
        }
        .card-back .datafields .k { color: #6b7176; text-align: left; }
        .card-back .datafields .v { font-weight: 700; color: #16222b; text-align: left; }
        .card-back .footer {
            position: relative;
            height: 7mm;
            background: var(--brand-dark);
            clip-path: polygon(0 40%, 25% 15%, 50% 35%, 75% 10%, 100% 30%, 100% 100%, 0 100%);
            color: #fff;
            font-size: 1.6mm;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 0.9mm;
            text-align: center;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .controls { display: none; }
            .sheet { gap: 4mm; }
            .card { box-shadow: none; }
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
    <div class="card-pair">
        <div class="card card-front">
            <div class="punch-hole"></div>
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
                @if ($staff->photo_url)
                    <img class="photo" src="{{ $staff->photo_url }}" alt="">
                @else
                    <div class="photo photo-empty">No<br>photo</div>
                @endif

                <div class="name">{{ $staff->user?->name ?? '—' }}</div>
                <div class="role-badge">{{ $card['designation'] ?? 'Staff' }}</div>

                <div class="fields">
                    <span class="k">Emp No:</span><span class="v">{{ $staff->employee_no ?? '—' }}</span>
                    <span class="k">Dept:</span><span class="v">{{ $card['department'] ?? '—' }}</span>
                </div>
            </div>
            <div class="accent-bar"></div>
        </div>

        <div class="card card-back">
            <div class="punch-hole"></div>
            <div class="top-bar"></div>
            <div class="content">
                <div class="qr-frame">
                    @if (! empty($card['attendance_qr']))
                        <img class="qr" src="{{ $card['attendance_qr'] }}" alt="Scan to mark attendance">
                    @endif
                </div>
                <div class="info">
                    <div class="school-name">{{ $school?->name ?? 'School' }}</div>
                    @if ($school?->address)
                        <div class="school-address">{{ $school->address }}</div>
                    @endif
                    @if ($school?->phone)
                        <div class="school-address">Ph: {{ $school->phone }}</div>
                    @endif
                    <div class="datafields">
                        <span class="k">Designation:</span><span class="v">{{ $card['designation'] ?? 'Staff' }}</span>
                        <span class="k">Phone:</span><span class="v">{{ $staff->phone ?? '—' }}</span>
                    </div>
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
