<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Form - {{ $student->admission_no }}</title>
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
            border: 1px solid #ddd;
            padding: 30px 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            text-transform: uppercase;
            color: #111;
        }
        .header p {
            margin: 8px 0 0;
            font-size: 15px;
        }
        .section-title {
            background: #f8f9fa;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 15px;
            border: 1px solid #ddd;
            border-left: 4px solid #0056b3;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: left;
        }
        table th {
            width: 25%;
            background: #f9fafb;
            color: #555;
            font-weight: 600;
        }
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
            font-weight: 600;
        }
        .photo-box {
            float: right;
            width: 120px;
            height: 150px;
            border: 1px solid #aaa;
            text-align: center;
            line-height: 150px;
            margin-left: 20px;
            margin-bottom: 10px;
            color: #888;
            background: #fbfbfb;
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
            }
            .controls {
                display: none;
            }
            @page {
                size: A4;
                margin: 1cm;
            }
            .section-title {
                margin-top: 20px;
                border: 1px solid #ddd;
                border-left: 4px solid #333;
            }
        }
    </style>
</head>
<body>
    <div class="controls">
        <button class="btn" onclick="window.print()">
            <svg style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Admission Form
        </button>
        <button class="btn" style="background:#6c757d;" onclick="window.close()">Close Form</button>
    </div>

    <div class="container">
        <div class="header">
            @if(isset($school) && $school->logo_path)
                <img src="{{ Storage::url($school->logo_path) }}" alt="{{ $school->name }}" style="height: 60px; position: absolute; left: 0; top: -10px;">
            @endif
            <h1>{{ $school->name ?? 'Admission Form' }}</h1>
            <p><strong>Registration No:</strong> {{ $student->registration_no }} &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Admission No:</strong> {{ $student->admission_no }}</p>
        </div>

        @php
            $enrollment = $student->enrollmentRecords->sortByDesc('admission_date')->first();
        @endphp

        <div class="photo-box">
            @if($student->image)
                <img src="{{ Storage::url($student->image) }}" alt="Photo" style="width:100%; height:100%; object-fit:cover;">
            @else
                Paste Photo Here
            @endif
        </div>

        <div class="section-title">Student Details</div>
        <table>
            <tr>
                <th>Student Name</th>
                <td>{{ optional($student->user)->name ?? 'N/A' }}</td>
                <th>Gender</th>
                <td>{{ optional($student->gender)->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Date of Birth</th>
                <td>{{ optional($student->dob)->format('d M Y') ?? 'N/A' }}</td>
                <th>B-Form / ID</th>
                <td>{{ $student->b_form ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Admission Date</th>
                <td>{{ optional(optional($enrollment)->admission_date)->format('d M Y') ?? 'N/A' }}</td>
                <th>Status</th>
                <td>{{ optional($student->studentStatus)->name ?? 'N/A' }}</td>
            </tr>
        </table>

        <div style="clear: both;"></div>

        <div class="section-title">Academic Details</div>
        <table>
            <tr>
                <th>Campus</th>
                <td>{{ optional(optional($enrollment)->campus)->name ?? 'N/A' }}</td>
                <th>Session</th>
                <td>{{ optional(optional($enrollment)->session)->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Class</th>
                <td>{{ optional(optional($enrollment)->class)->name ?? 'N/A' }}</td>
                <th>Section</th>
                <td>{{ optional(optional($enrollment)->section)->name ?? 'N/A' }}</td>
            </tr>
        </table>

        <div class="section-title">Guardian Details</div>
        @forelse($student->studentGuardians as $sg)
            <div style="margin-bottom: 12px; border: 1px solid #ddd; padding: 10px; background: #fff;">
                <h4 style="margin: 0 0 10px; font-size: 14px; color: #333;">
                    {{ optional($sg->relation)->name ?? 'Guardian' }}
                    @if($sg->is_primary)
                        <span style="color: #28a745; font-size: 11px; border: 1px solid #28a745; padding: 2px 5px; border-radius: 3px; margin-left: 5px;">Primary</span>
                    @endif
                </h4>
                <table style="margin-bottom: 0; box-shadow: none;">
                    <tr>
                        <th style="background: none; border: none; padding: 4px 8px; width: 15%;">Name:</th>
                        <td style="border: none; padding: 4px 8px; width: 35%;">{{ !empty(optional(optional($sg->guardian)->user)->name) ? $sg->guardian->user->name : '--' }}</td>
                        <th style="background: none; border: none; padding: 4px 8px; width: 15%;">Phone:</th>
                        <td style="border: none; padding: 4px 8px; width: 35%;">{{ !empty(optional($sg->guardian)->phone) ? $sg->guardian->phone : '--' }}</td>
                    </tr>
                    <tr>
                        <th style="background: none; border: none; padding: 4px 8px;">CNIC:</th>
                        <td style="border: none; padding: 4px 8px;">{{ !empty(optional($sg->guardian)->cnic) ? $sg->guardian->cnic : '--' }}</td>
                        <th style="background: none; border: none; padding: 4px 8px;">Email:</th>
                        <td style="border: none; padding: 4px 8px;">{{ !empty(optional(optional($sg->guardian)->user)->email) ? $sg->guardian->user->email : '--' }}</td>
                    </tr>
                    <tr>
                        <th style="background: none; border: none; padding: 4px 8px;">Occupation:</th>
                        <td style="border: none; padding: 4px 8px;">{{ !empty(optional($sg->guardian)->occupation) ? $sg->guardian->occupation : '--' }}</td>
                        <th style="background: none; border: none; padding: 4px 8px;">Address:</th>
                        <td style="border: none; padding: 4px 8px;">{{ !empty(optional($sg->guardian)->address) ? $sg->guardian->address : '--' }}</td>
                    </tr>
                </table>
            </div>
        @empty
            <p style="padding: 10px; border: 1px solid #ddd; color: #777;">No guardians found.</p>
        @endforelse

        <div class="section-title">Fee Summary</div>
        <table>
        @if(optional($enrollment)->feeStructure)
            @forelse($enrollment->feeStructure->items as $item)
            <tr>
                <th>{{ optional($item->feeHead)->name ?? 'Fee' }} ({{ optional($item->frequency)->value ?? optional($item->frequency)->name ?? 'Monthly' }})</th>
                <td colspan="3">Rs. {{ number_format($item->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center;">No fee breakdown available in structure.</td>
            </tr>
            @endforelse
        @elseif(optional($enrollment)->fee_mode === 'custom' && !empty($enrollment->custom_fee_entries))
            @foreach($enrollment->custom_fee_entries as $entry)
            <tr>
                <th>{{ $entry['fee_head_name'] ?? 'Fee' }}</th>
                <td colspan="3">Rs. {{ number_format($entry['amount'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        @else
            <tr>
                <th>Monthly Fee</th>
                <td>Rs. {{ number_format(optional($enrollment)->monthly_fee ?? 0, 2) }}</td>
                <th>Annual Fee</th>
                <td>Rs. {{ number_format(optional($enrollment)->annual_fee ?? 0, 2) }}</td>
            </tr>
        @endif
        </table>

        <!-- Uncomment to leave more space if content is short, or just let natural flow handle it -->
        <!-- <div style="height: 50px;"></div> -->

        <div class="footer">
            <div class="signature-box">
                <div class="signature-line">Parent/Guardian Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Principal Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Date</div>
            </div>
        </div>
    </div>

    <script>
        // Use keyboard shortcut to trigger print or rely on the big blue button
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
        
        // Auto-print prompt
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
