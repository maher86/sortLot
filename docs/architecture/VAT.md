# UAE VAT — Rules & Implementation

> Reference: UAE Federal Decree-Law No. 8 of 2017. FTA Public Clarification VATP013 (Designated Zones).

---

## Business Context

SortLot operates from **Hamriyah Free Zone**, Sharjah. This is a **Designated Zone** under UAE VAT law. The Hamriyah Free Zone is treated as *outside the UAE* for VAT purposes on goods — but only under specific conditions.

---

## VAT Treatment Rules

### Rule 1 — Sales to UAE Mainland Customers
- **VAT Rate: 5%** (standard rated)
- This applies when goods physically move from the free zone INTO the UAE mainland
- Invoice must show: VAT amount, seller TRN (Tax Registration Number)
- Customer is in `customers.vat_type = 'mainland'`

### Rule 2 — Sales to Other Designated Zones (UAE)
- **VAT Rate: 0%** (zero-rated supply between Designated Zones)
- Conditions: goods must physically move under customs supervision, commercial agreement must exist
- Customer is in `customers.vat_type = 'free_zone'`

### Rule 3 — Export Sales (International)
- **VAT Rate: 0%** (zero-rated export)
- Goods leave UAE entirely
- Customer is in `customers.vat_type = 'international'`

### Rule 4 — Purchases from UAE Mainland Suppliers
- Supplier charges 5% VAT
- Company can **recover input VAT** on business purchases
- Recorded in `invoices` as purchase_order with `vat_amount_fils` = input VAT
- Reported on VAT return as input tax credit

### Rule 5 — Purchases from Free Zone / International Suppliers
- No VAT charged
- `vat_rate = 0` on purchase order

---

## VAT Registration

- Company **must** register for VAT if taxable supplies > AED 375,000/year
- TRN stored in `preferences` table, key: `company_trn`
- TRN format: 15-digit number (e.g. 100234567890003)

---

## Invoice VAT Calculation Logic

```php
// InvoiceService::calculateVat()

$vatRate = match($customer->vat_type) {
    'mainland'      => 5.00,  // standard rated
    'free_zone'     => 0.00,  // zero-rated (between DZ)
    'international' => 0.00,  // zero-rated (export)
};

// For purchase orders, rate comes from supplier type (same logic)
$subtotal = sum(invoice_lines.line_total_fils);
$discount = subtotal * (discount_pct / 100);  // or flat discount
$taxable  = subtotal - discount;
$vatAmount = round($taxable * ($vatRate / 100));
$total    = $taxable + $vatAmount;
```

---

## Invoice Display Requirements (FTA Mandated)

For any invoice with VAT (mainland sales), the following MUST appear:
1. The word "Tax Invoice" (in Arabic and English on formal docs)
2. Seller's TRN
3. Buyer's TRN (if registered, i.e. B2B mainland)
4. Invoice date
5. Supply date (delivery date if different)
6. Description of goods
7. Unit price, quantity, subtotal per line
8. Total excluding VAT
9. VAT rate and amount
10. Total including VAT
11. Currency (if not AED, include AED equivalent)

For zero-rated invoices (FZ-to-FZ or export):
- Mark as "Zero-Rated Supply" with reason
- Seller TRN still required if VAT registered

---

## VAT Return (Quarterly)

FTA requires quarterly VAT returns. System should be able to generate:

### Output Tax Summary
```
Standard rated sales (mainland):    AED X  → VAT X
Zero-rated sales (FZ/export):       AED X  → VAT 0
Exempt supplies:                    AED X  → VAT 0
Total output tax:                   AED X
```

### Input Tax Summary
```
Purchases from mainland suppliers:  AED X  → Input VAT X
Total input tax:                    AED X
```

### Net VAT Payable
```
Output tax - Input tax = Net payable (or refund if negative)
```

### Report Query
```sql
-- Output tax (sales orders, mainland customers, paid/partial)
SELECT 
    SUM(subtotal_fils) as taxable_amount,
    SUM(vat_amount_fils) as vat_collected
FROM invoices i
JOIN customers c ON i.customer_id = c.id
WHERE i.type = 'sales_order'
  AND c.vat_type = 'mainland'
  AND i.status IN ('paid','partial')
  AND i.issue_date BETWEEN :start AND :end;
```

---

## Implementation Checklist

- [ ] `preferences` seeded with `company_trn`, `vat_rate_mainland = 5.00`
- [ ] `customers.vat_type` and `suppliers.vat_type` enums in migration
- [ ] `InvoiceService::calculateVat()` implemented and unit-tested
- [ ] PDF invoice template has all FTA mandatory fields
- [ ] "Zero-Rated" badge shown on FZ/export invoices
- [ ] VAT report endpoint: `GET /api/v1/reports/vat?from=&to=`
- [ ] VAT report exportable as PDF and CSV
- [ ] Warning shown in Preferences if TRN is not set

---

## Edge Cases

| Scenario | Treatment |
|----------|-----------|
| Mainland customer buys, picks up from FZ | Still 5% — tax point is customer's location/use |
| Customer doesn't provide TRN (B2C mainland) | Still 5%, no buyer TRN on invoice |
| Return/credit note | VAT on CN = same rate as original invoice |
| Mixed order (some mainland, some FZ items) | System treats per customer type, not per item |
| Partial payment | VAT accrues on full invoice at issue date (accrual basis) |
