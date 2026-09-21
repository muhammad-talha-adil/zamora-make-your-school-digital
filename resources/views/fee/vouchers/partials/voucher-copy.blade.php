{{--
    One voucher's content, sized to occupy exactly one third of an A4 page.
    Used both for a single voucher printed alone (see
    fee/vouchers/print.blade.php) and for three different vouchers stacked on
    one page (batch print, see fee/vouchers/print-batch.blade.php).

    Expects: $voucher, $school, $copyLabel (optional — omit to render without
    a copy-type label, as the single-voucher print does)
--}}
<section class="copy">
    @if($copyLabel ?? false)
        <div class="copy-label">{{ $copyLabel }}</div>
    @endif

    <div class="header">
        @if(isset($school) && $school->logo_path)
            <img src="{{ $school->logo_path }}" alt="{{ $school->name }}" class="logo">
        @endif
        <h1>Fee Voucher</h1>
        <p><strong>{{ $school->name ?? $voucher->campus->name ?? 'School Name' }}</strong></p>
        @if($school->address ?? false)<p>{{ $school->address }}</p>@endif
        @if($school->phone ?? false)<p>Ph: {{ $school->phone }}</p>@endif
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
                <th class="col-sr">Sr#</th>
                <th>Description</th>
                <th class="amount">Amount</th>
                <th class="amount">Discount</th>
                <th class="amount">Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($voucher->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->feeHead->name ?? 'Fee' }}</td>
                <td class="amount">{{ number_format((float) $item->amount, 0) }}</td>
                <td class="amount discount">{{ number_format((float) $item->discount_amount, 0) }}</td>
                <td class="amount">{{ number_format((float) $item->net_amount, 0) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center;">No fee items found</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="amount">Total Amount</td>
                <td class="amount">{{ number_format((float) $voucher->net_amount, 0) }}</td>
            </tr>
            @if($voucher->paid_amount > 0)
            <tr class="paid-row">
                <td colspan="4" class="amount">Paid Amount</td>
                <td class="amount">{{ number_format((float) $voucher->paid_amount, 0) }}</td>
            </tr>
            @endif
            @if($voucher->balance_amount > 0)
            <tr class="balance-row">
                <td colspan="4" class="amount">Balance Due</td>
                <td class="amount">{{ number_format((float) $voucher->balance_amount, 0) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>

    <div class="sign">
        <div>Depositor Signature</div>
        <div>Bank / School Stamp</div>
    </div>
</section>
