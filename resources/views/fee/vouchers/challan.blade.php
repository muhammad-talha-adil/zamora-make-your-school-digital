<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Challan - {{ $voucher->voucher_no }}</title>
    <style>
        /*
         * A bank challan is a printed document, not a screen: it is read off
         * paper by a teller and stamped in ink. It is therefore deliberately
         * monochrome and does not follow the application palette — the ink and
         * paper are the only two colours a branch printer can be relied on to
         * produce. The few greys used are named here rather than repeated as
         * literals through the markup.
         */
        :root {
            --ink: #000;
            --paper: #fff;
            --rule: #000;
            --muted: #555;
            --panel: #f2f2f2;
        }

        * { box-sizing: border-box; }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 12px;
            color: var(--ink);
            background: var(--paper);
            font-size: 11px;
            line-height: 1.35;
        }

        .controls { text-align: center; margin-bottom: 14px; }
        .controls button {
            font: inherit;
            padding: 8px 18px;
            margin: 0 4px;
            border: 1px solid var(--ink);
            background: var(--paper);
            color: var(--ink);
            cursor: pointer;
        }

        .challan-sheet {
            display: flex;
            gap: 6px;
            align-items: stretch;
        }

        .copy {
            flex: 1 1 0;
            border: 1px solid var(--rule);
            padding: 8px;
            display: flex;
            flex-direction: column;
            /* Each copy is torn off along the fold, so nothing may bleed over. */
            page-break-inside: avoid;
        }

        .copy-label {
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid var(--rule);
            padding-bottom: 4px;
            margin-bottom: 6px;
            font-size: 11px;
        }

        .school {
            text-align: center;
            margin-bottom: 6px;
        }
        .school strong { display: block; font-size: 12px; text-transform: uppercase; }
        .school span { color: var(--muted); font-size: 10px; }

        .meta { margin-bottom: 6px; }
        .meta div {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            padding: 1px 0;
        }
        .meta dt { color: var(--muted); }

        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid var(--rule); padding: 3px 4px; text-align: left; }
        th { background: var(--panel); font-weight: 600; }
        td.amount, th.amount { text-align: right; white-space: nowrap; }

        .payable {
            border: 2px solid var(--rule);
            padding: 5px;
            text-align: center;
            margin-bottom: 6px;
        }
        .payable span { display: block; font-size: 10px; color: var(--muted); }
        .payable strong { font-size: 15px; }

        .words {
            font-size: 10px;
            margin-bottom: 6px;
            min-height: 24px;
        }

        .ocr {
            margin-top: auto;
            border-top: 1px dashed var(--rule);
            padding-top: 5px;
            text-align: center;
        }
        .ocr code {
            font-family: "OCR A Extended", "Courier New", monospace;
            font-size: 12px;
            letter-spacing: 0.12em;
            font-weight: 700;
            display: block;
            word-break: break-all;
        }
        .ocr span { font-size: 9px; color: var(--muted); }

        .sign {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 9px;
            color: var(--muted);
        }
        .sign div {
            flex: 1;
            border-top: 1px solid var(--rule);
            padding-top: 2px;
            text-align: center;
        }

        @media print {
            body { padding: 0; font-size: 10px; }
            .controls { display: none; }
            @page { size: A4 landscape; margin: 8mm; }
        }

        @media screen and (max-width: 900px) {
            /* Readable on a phone before it is sent to the printer. */
            .challan-sheet { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="controls">
        <button type="button" onclick="window.print()">Print Challan</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>

    @php
        /*
         * Three identical parts: the bank keeps one, sends one back to the
         * school with the day's scroll, and the parent keeps the third as proof
         * of payment. They must carry the same figures and the same reference.
         */
        $copies = ['Bank Copy', 'School Copy', 'Parent Copy'];
        $reference = $voucher->challanReferenceFormatted();
        $payable = (float) $voucher->balance_amount;
    @endphp

    <div class="challan-sheet">
        @foreach($copies as $copy)
        <section class="copy">
            <div class="copy-label">{{ $copy }}</div>

            <div class="school">
                <strong>{{ $school->name ?? $voucher->campus->name ?? 'School' }}</strong>
                @if($school?->address)<span>{{ $school->address }}</span>@endif
                @if($bankAccount)<span>{{ $bankAccount }}</span>@endif
            </div>

            <div class="meta">
                <div><dt>Challan No</dt><dd>{{ $voucher->voucher_no }}</dd></div>
                <div><dt>Student</dt><dd>{{ $voucher->student->name ?? '—' }}</dd></div>
                <div><dt>Father</dt><dd>{{ $voucher->student->father_name ?? '—' }}</dd></div>
                <div>
                    <dt>Class</dt>
                    <dd>{{ $voucher->schoolClass->name ?? '—' }}{{ $voucher->section ? ' / '.$voucher->section->name : '' }}</dd>
                </div>
                <div>
                    <dt>Month</dt>
                    <dd>{{ $voucher->voucherMonth->name ?? '' }} {{ $voucher->voucher_year }}</dd>
                </div>
                <div>
                    <dt>Due Date</dt>
                    <dd>{{ $voucher->due_date ? $voucher->due_date->format('d M Y') : '—' }}</dd>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($voucher->items as $item)
                    <tr>
                        <td>{{ $item->feeHead->name ?? 'Fee' }}</td>
                        <td class="amount">{{ number_format((float) $item->net_amount, 0) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2">No charges on this challan</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="payable">
                <span>Payable by due date</span>
                <strong>Rs. {{ number_format($payable, 0) }}</strong>
                @if($lateFineNote)<span>{{ $lateFineNote }}</span>@endif
            </div>

            <div class="words">
                Rupees {{ $amountInWords }} only.
            </div>

            <div class="ocr">
                <code>{{ $reference }}</code>
                <span>Quote this number at the counter</span>
            </div>

            <div class="sign">
                <div>Depositor</div>
                <div>Bank Stamp</div>
            </div>
        </section>
        @endforeach
    </div>

    <script>
        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
                event.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>
