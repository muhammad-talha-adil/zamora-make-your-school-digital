<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Voucher - {{ $voucher->voucher_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 13px;
            line-height: 1.5;
            background-color: #f3f4f6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #222;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            color: #111;
        }
        .header p {
            margin: 8px 0 0;
            font-size: 14px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .info-box p {
            margin: 4px 0;
        }
        .student-info {
            background: #f0f0f0;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            border: 1px solid #333;
            padding: 8px 10px;
            text-align: left;
        }
        table th {
            background: #e9ecef;
            font-weight: 600;
        }
        .total-row {
            background: #e9ecef;
            font-weight: bold;
        }
        .balance-row {
            background: #fff3cd;
            font-weight: bold;
        }
        .paid-row {
            background: #d4edda;
        }
        
        /* Buttons for screen view */
        .controls {
            text-align: center;
            margin-bottom: 30px;
        }
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
        .btn:hover {
            background-color: #004494;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        @media print {
            body {
                padding: 0;
                background: none;
                font-size: 12px;
            }
            .container {
                border: none;
                padding: 0;
                box-shadow: none;
                width: 100%;
                max-width: 100%;
                border-radius: 0;
            }
            .controls {
                display: none;
            }
            @page {
                size: A4;
                margin: 1cm;
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

    <div class="container">
        <div class="header">
            @if(isset($school) && $school->logo_path)
                <img src="{{ Storage::url($school->logo_path) }}" alt="{{ $school->name }}" style="height: 60px; position: absolute; left: 0; top: -10px;">
            @endif
            <h1>Fee Voucher</h1>
            <p><strong>{{ $school->name ?? $voucher->campus->name ?? 'School Name' }}</strong></p>
            @if($school->address ?? false)<p>{{ $school->address }}</p>@endif
            <p>Session: {{ $voucher->voucher_year }}</p>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <p><strong>Voucher No:</strong> {{ $voucher->voucher_no }}</p>
                <p><strong>Issue Date:</strong> {{ \Carbon\Carbon::parse($voucher->issue_date)->format('d M Y') }}</p>
                <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($voucher->due_date)->format('d M Y') }}</p>
            </div>
            <div class="info-box" style="text-align: right;">
                <p><strong>Month:</strong> {{ $voucher->voucherMonth->name ?? '' }} {{ $voucher->voucher_year }}</p>
                <p><strong>Class:</strong> {{ $voucher->schoolClass->name ?? '' }} {{ $voucher->section ? '- ' . $voucher->section->name : '' }}</p>
            </div>
        </div>

        <div class="student-info">
            <p><strong>Student Name:</strong> {{ $voucher->student->name ?? 'N/A' }}</p>
            <p><strong>Registration No:</strong> {{ $voucher->student->registration_number ?? 'N/A' }}</p>
            @if($voucher->student->father_name)
            <p><strong>Father's Name:</strong> {{ $voucher->student->father_name }}</p>
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Sr#</th>
                    <th>Description</th>
                    <th style="width: 100px; text-align: right;">Amount</th>
                    <th style="width: 100px; text-align: right;">Discount</th>
                    <th style="width: 100px; text-align: right;">Net Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($voucher->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div>{{ $item->feeHead->name ?? 'Fee' }}</div>
                        @if($item->description)
                        <div style="font-size: 11px; color: #666;">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td style="text-align: right;">Rs. {{ number_format($item->amount, 2) }}</td>
                    <td style="text-align: right; color: #28a745;">Rs. {{ number_format($item->discount_amount, 2) }}</td>
                    <td style="text-align: right; font-weight: 500;">Rs. {{ number_format($item->net_amount, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center;">No fee items found</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">Total Amount</td>
                    <td style="text-align: right;">Rs. {{ number_format($voucher->net_amount, 2) }}</td>
                </tr>
                @if($voucher->paid_amount > 0)
                <tr class="paid-row">
                    <td colspan="4" style="text-align: right;">Paid Amount</td>
                    <td style="text-align: right; color: #28a745;">Rs. {{ number_format($voucher->paid_amount, 2) }}</td>
                </tr>
                @endif
                @if($voucher->balance_amount > 0)
                <tr class="balance-row">
                    <td colspan="4" style="text-align: right;">Balance Due</td>
                    <td style="text-align: right; color: #dc3545;">Rs. {{ number_format($voucher->balance_amount, 2) }}</td>
                </tr>
                @endif
            </tfoot>
        </table>

        <div style="text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
            <p>Please pay the fee before due date to avoid late payment charges.</p>
            <p>For queries, contact the school administration.</p>
        </div>

        <!-- Payment Record -->
        <div style="margin-top: 30px; padding-top: 15px; border-top: 2px solid #222;">
            <h3 style="margin: 0 0 15px 0; font-size: 14px;">Payment Record</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt No</th>
                        <th style="text-align: right;">Amount</th>
                        <th>Signature</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="height: 40px;"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="height: 40px;"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Keyboard shortcut to trigger print
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
        
        // Auto-print prompt
        window.addEventListener('load', function() {
            setTimeout(function() {
                // Optional: auto-print
                // window.print();
            }, 500);
        });
    </script>
</body>
</html>
