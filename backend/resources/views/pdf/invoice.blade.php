<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
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

        .invoice-meta {
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

        .paid-stamp {
            border: 2px solid #17803d;
            color: #17803d;
            display: inline-block;
            font-size: 18px;
            font-weight: 700;
            margin-top: 12px;
            padding: 6px 16px;
            text-transform: uppercase;
        }

        .block {
            margin-bottom: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #172033;
            color: #fff;
            font-size: 11px;
            padding: 8px;
            text-align: left;
        }

        td {
            border-bottom: 1px solid #dde3ed;
            padding: 8px;
            vertical-align: top;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .totals {
            margin-left: auto;
            margin-top: 18px;
            width: 42%;
        }

        .totals td {
            border-bottom: 0;
            padding: 5px 8px;
        }

        .total-row td {
            border-top: 2px solid #172033;
            font-size: 14px;
            font-weight: 700;
        }

        .notes {
            background: #f7f9fc;
            border: 1px solid #dde3ed;
            padding: 10px;
        }
    </style>
</head>
<body>
@php
    $formatMoney = fn (int $fils): string => number_format($fils / 100, 2).' '.$invoice->currency;
    $partyLabel = $invoice->type->value === 'purchase_order' ? 'Supplier' : 'Customer';
@endphp

<div class="header">
    <div class="company">
        <h1>{{ $company['company_name'] ?? 'SortLot Trading' }}</h1>
        <div class="muted">Hamriyah Free Zone, UAE</div>
        @if($company['company_trn'] ?? null)
            <div>TRN: {{ $company['company_trn'] }}</div>
        @endif
    </div>
    <div class="invoice-meta">
        <h2>{{ $taxLabel }}</h2>
        <div class="label">{{ $invoice->number }}</div>
        @if($isPaid)
            <div class="paid-stamp">Paid</div>
        @endif
    </div>
    <div class="clear"></div>
</div>

<div class="block">
    <table>
        <tr>
            <td style="width: 55%;">
                <strong>{{ $partyLabel }}</strong><br>
                {{ $party?->name }}<br>
                @if($party?->contact_name) Contact: {{ $party->contact_name }}<br> @endif
                @if($party?->address) {{ $party->address }}<br> @endif
                @if($party?->country) {{ $party->country }}<br> @endif
                @if($party?->trn) TRN: {{ $party->trn }} @endif
            </td>
            <td>
                <strong>Invoice Details</strong><br>
                Type: {{ str_replace('_', ' ', ucfirst($invoice->type->value)) }}<br>
                Status: {{ str_replace('_', ' ', ucfirst($invoice->status->value)) }}<br>
                Issue Date: {{ $invoice->issue_date?->toDateString() }}<br>
                Due Date: {{ $invoice->due_date?->toDateString() ?? 'N/A' }}<br>
                Delivery Date: {{ $invoice->delivery_date?->toDateString() ?? 'N/A' }}<br>
                @if($invoice->reference) Reference: {{ $invoice->reference }} @endif
            </td>
        </tr>
    </table>
</div>

<div class="block">
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="number">Qty</th>
                <th class="number">Unit Price</th>
                <th class="number">Discount</th>
                <th class="number">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="number">{{ $line->quantity }}</td>
                    <td class="number">{{ $formatMoney($line->unit_price_fils) }}</td>
                    <td class="number">{{ $line->discount_pct }}%</td>
                    <td class="number">{{ $formatMoney($line->line_total_fils) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<table class="totals">
    <tr>
        <td>Subtotal</td>
        <td class="number">{{ $formatMoney($invoice->subtotal_fils) }}</td>
    </tr>
    <tr>
        <td>Discount</td>
        <td class="number">{{ $formatMoney($invoice->discount_fils) }}</td>
    </tr>
    <tr>
        <td>VAT ({{ $invoice->vat_rate }}%)</td>
        <td class="number">{{ $formatMoney($invoice->vat_amount_fils) }}</td>
    </tr>
    <tr class="total-row">
        <td>Total</td>
        <td class="number">{{ $formatMoney($invoice->total_fils) }}</td>
    </tr>
    <tr>
        <td>Paid</td>
        <td class="number">{{ $formatMoney($invoice->paid_amount_fils) }}</td>
    </tr>
    <tr>
        <td>Balance</td>
        <td class="number">{{ $formatMoney($invoice->balance_fils) }}</td>
    </tr>
</table>

<div class="clear"></div>

@if($invoice->terms || $invoice->notes)
    <div class="block notes">
        @if($invoice->terms)
            <strong>Payment Terms</strong><br>
            {{ $invoice->terms }}<br><br>
        @endif
        @if($invoice->notes)
            <strong>Notes</strong><br>
            {{ $invoice->notes }}
        @endif
    </div>
@endif
</body>
</html>
