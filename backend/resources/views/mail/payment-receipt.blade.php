<p>Hello {{ $party?->name ?? 'Customer' }},</p>

<p>We received your payment.</p>

<table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
    <tr>
        <td><strong>Invoice</strong></td>
        <td>{{ $invoice?->number ?? $payment->invoice_id }}</td>
    </tr>
    <tr>
        <td><strong>Payment date</strong></td>
        <td>{{ $payment->payment_date?->toDateString() }}</td>
    </tr>
    <tr>
        <td><strong>Method</strong></td>
        <td>{{ str($payment->payment_method->value)->replace('_', ' ')->title() }}</td>
    </tr>
    <tr>
        <td><strong>Reference</strong></td>
        <td>{{ $payment->reference ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Amount</strong></td>
        <td>{{ number_format($payment->amount_fils / 100, 2) }} AED</td>
    </tr>
</table>

<p>Thank you,<br>SortLot</p>
