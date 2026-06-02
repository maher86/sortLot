<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment Receipt {{ $payment->reference ?? $payment->id }}</title>
    <style>
        body {
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #172033;
            margin-bottom: 24px;
            padding-bottom: 18px;
        }

        .company {
            float: left;
            width: 58%;
        }

        .receipt-meta {
            float: right;
            text-align: right;
            width: 38%;
        }

        .clear {
            clear: both;
        }

        h1 {
            font-size: 24px;
            letter-spacing: 0;
            margin: 0 0 8px;
        }

        h2 {
            font-size: 16px;
            letter-spacing: 0;
            margin: 0 0 8px;
        }

        .muted {
            color: #5c6678;
        }

        .label {
            background: #eef2f7;
            border: 1px solid #d9e0ea;
            display: inline-block;
            font-weight: 700;
            margin-top: 8px;
            padding: 5px 8px;
        }

        table {
            border-collapse: collapse;
            margin-top: 20px;
            width: 100%;
        }

        th {
            background: #172033;
            color: #fff;
            font-size: 11px;
            padding: 8px;
            text-align: left;
            width: 34%;
        }

        td {
            border-bottom: 1px solid #dde3ed;
            padding: 8px;
            vertical-align: top;
        }

        .amount {
            font-size: 18px;
            font-weight: 700;
        }
    </style>
</head>
<body>
@php
    $formatMoney = fn (int $fils): string => number_format($fils / 100, 2).' '.($invoice?->currency ?? 'AED');
@endphp

<div class="header">
    <div class="company">
        <h1>{{ $company['company_name'] ?? 'SortLot Trading' }}</h1>
        <div class="muted">Hamriyah Free Zone, UAE</div>
        @if($company['company_trn'] ?? null)
            <div>TRN: {{ $company['company_trn'] }}</div>
        @endif
    </div>
    <div class="receipt-meta">
        <h2>Payment Receipt</h2>
        <div class="label">{{ $payment->reference ?? $payment->id }}</div>
    </div>
    <div class="clear"></div>
</div>

<table>
    <tr>
        <th>Payment Date</th>
        <td>{{ $payment->payment_date?->toDateString() }}</td>
    </tr>
    <tr>
        <th>Amount</th>
        <td class="amount">{{ $formatMoney($payment->amount_fils) }}</td>
    </tr>
    <tr>
        <th>Payment Method</th>
        <td>{{ str_replace('_', ' ', ucfirst($payment->payment_method->value)) }}</td>
    </tr>
    <tr>
        <th>Reference</th>
        <td>{{ $payment->reference ?? 'N/A' }}</td>
    </tr>
    <tr>
        <th>Invoice</th>
        <td>{{ $invoice?->number ?? $payment->invoice_id }}</td>
    </tr>
    <tr>
        <th>Customer / Supplier</th>
        <td>{{ $party?->name ?? 'N/A' }}</td>
    </tr>
    @if($payment->bank_name)
        <tr>
            <th>Bank</th>
            <td>{{ $payment->bank_name }}</td>
        </tr>
    @endif
    @if($payment->notes)
        <tr>
            <th>Notes</th>
            <td>{{ $payment->notes }}</td>
        </tr>
    @endif
</table>
</body>
</html>
