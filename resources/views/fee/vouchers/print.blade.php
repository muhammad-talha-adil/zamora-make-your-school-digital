<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Voucher - {{ $voucher->voucher_no }}</title>
    <style>
        /*
         * A standard fee voucher: three identical copies of the SAME voucher
         * stacked on one A4 sheet, each occupying exactly a third of the page
         * height, cut apart along a dashed line — Bank keeps one, School
         * keeps one, the Student/Parent keeps the third.
         */
        :root {
            --page-height: 297mm;
            --page-margin: 10mm;
            --copy-height: calc((var(--page-height) - (2 * var(--page-margin))) / 3);
        }

        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 11px;
            line-height: 1.35;
            background-color: #f3f4f6;
        }

        .controls { text-align: center; margin-bottom: 20px; }
        .btn {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 12px 24px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 0 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn:hover { background-color: #004494; }
        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }

        .sheet {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .copy {
            padding: 12px 20px;
            display: flex;
            flex-direction: column;
        }
        .copy + .copy {
            border-top: 2px dashed #333;
        }

        .copy-label {
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-size: 11px;
            margin-bottom: 4px;
            color: #555;
        }

        .header { text-align: center; border-bottom: 1px solid #222; padding-bottom: 6px; margin-bottom: 8px; position: relative; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; color: #111; }
        .header p { margin: 2px 0 0; font-size: 11px; }
        .logo { height: 36px; position: absolute; left: 0; top: 0; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }
        .info-box { background: #f8f9fa; padding: 6px 8px; border-radius: 4px; border: 1px solid #ddd; }
        .info-box p { margin: 2px 0; }

        .student-info { background: #f0f0f0; padding: 6px 8px; border-radius: 4px; margin-bottom: 8px; border: 1px solid #ccc; }
        .student-info p { margin: 2px 0; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table th, table td { border: 1px solid #333; padding: 3px 6px; text-align: left; }
        table th { background: #e9ecef; font-weight: 600; }
        .col-sr { width: 30px; }
        .amount { text-align: right; white-space: nowrap; }
        .discount { color: #28a745; }
        .total-row { background: #e9ecef; font-weight: bold; }
        .balance-row { background: #fff3cd; font-weight: bold; }
        .paid-row { background: #d4edda; }

        .sign {
            margin-top: auto;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 10px;
            color: #666;
        }
        .sign div { flex: 1; border-top: 1px solid #999; padding-top: 3px; text-align: center; }

        @media print {
            body { padding: 0; background: none; }
            .controls { display: none; }
            .sheet { max-width: 100%; box-shadow: none; }
            .copy {
                height: var(--copy-height);
                page-break-inside: avoid;
            }
            @page {
                size: A4;
                margin: var(--page-margin);
            }
        }
    </style>
</head>
<body>
    <div class="controls">
        <button class="btn" onclick="window.print()">
            <svg style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Voucher
        </button>
        <button class="btn btn-secondary" onclick="window.close()">Close</button>
    </div>

    @php
        /*
         * Same voucher, three copies — the bank keeps one, the school keeps
         * one, the student/parent keeps the third as proof of payment.
         */
        $copies = ['Bank Copy', 'School Copy', 'Student Copy'];
    @endphp

    <div class="sheet">
        @foreach($copies as $copyLabel)
            @include('fee.vouchers.partials.voucher-copy', ['voucher' => $voucher, 'school' => $school ?? null, 'copyLabel' => $copyLabel])
        @endforeach
    </div>

    <script>
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>
