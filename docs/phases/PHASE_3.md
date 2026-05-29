# PHASE 3 — Customers, Suppliers & Invoicing

> Prerequisites: Phase 2 complete

---

## Step 3.1 — Customers & Suppliers

### Tasks
- [ ] Migrations: `customers`, `suppliers` (see DATABASE.md)
- [ ] Models with `SoftDeletes`, `Auditable`
- [ ] Controllers, Requests, Resources, Policies
- [ ] `GET /customers/{id}/statement` — account statement (balance, invoice history)
- [ ] Credit limit warning logic (flag when new invoice would exceed limit)

### Tests
- [ ] `tests/Feature/Customers/CustomerCrudTest.php`
- [ ] `tests/Feature/Customers/CustomerStatementTest.php`
- [ ] `tests/Feature/Suppliers/SupplierCrudTest.php`

---

## Step 3.2 — Invoice Core

### Tasks
- [ ] Migrations: `invoices`, `invoice_lines`, `payments`
- [ ] Model: `Invoice` with:
  - Relationships: customer/supplier, lines (hasMany), payments (hasMany)
  - `recalculate()` method — recalculates subtotal, VAT, total, balance from lines
  - `markOverdue()` — called by scheduled command daily
  - Status machine: validates allowed transitions
  - Observers: on line change → recalculate
  - Auto invoice number generation (uses `preferences` sequence + prefix)
- [ ] Model: `InvoiceLine` — on save/delete, triggers parent `recalculate()`
- [ ] Model: `Payment` — on save/delete, triggers invoice `updatePaidAmount()` and status check
- [ ] `InvoiceService`:
  - `create(array $data): Invoice`
  - `confirm(Invoice $invoice): void` — draft → pending, reserves items
  - `cancel(Invoice $invoice): void` — releases reserved items
  - `generatePdf(Invoice $invoice): string` — queues job, returns path
  - `sendEmail(Invoice $invoice): void` — queues job
  - `calculateVat(Invoice $invoice, Customer|Supplier $party): array`
  - `generateCreditNote(Invoice $original): Invoice`
- [ ] `InvoiceNumberService` — thread-safe sequential number generation using DB lock

### Tests
- [ ] `tests/Unit/Services/InvoiceServiceTest.php` — all methods
- [ ] `tests/Unit/Services/InvoiceNumberServiceTest.php` — concurrent generation
- [ ] `tests/Unit/Services/VatCalculationTest.php` — all 3 vat_types
- [ ] `tests/Feature/Invoices/SalesOrderCrudTest.php`
- [ ] `tests/Feature/Invoices/PurchaseOrderCrudTest.php`
- [ ] `tests/Feature/Invoices/InvoiceStatusFlowTest.php`
- [ ] `tests/Feature/Invoices/PaymentTest.php`
- [ ] `tests/Feature/Invoices/CreditNoteTest.php`
- [ ] `tests/Feature/Invoices/InvoicePermissionTest.php`

---

## Step 3.3 — Invoice PDF Generation

### Tasks
- [ ] Install `barryvdh/laravel-dompdf` or `spatie/laravel-pdf`
- [ ] Blade template: `resources/views/pdf/invoice.blade.php`
  - Company logo, name, address, TRN
  - Invoice number, type, issue/due date
  - Customer/supplier details + their TRN if present
  - Line items table
  - Subtotal, discount, VAT rate + amount, total
  - "Tax Invoice" / "Zero-Rated Supply" label depending on VAT type
  - Payment terms, notes
  - Paid stamp if status = paid
  - FTA-mandated Arabic label (Tax Invoice = فاتورة ضريبية)
- [ ] `GenerateInvoicePdfJob` — queued, stores to MinIO/S3
- [ ] `GET /invoices/{id}/pdf` → returns signed URL or streams file

### Tests
- [ ] `tests/Feature/Invoices/InvoicePdfTest.php` — generation queued, file exists

---

## Step 3.4 — Overdue Scheduler

### Tasks
- [ ] `app/Console/Commands/MarkOverdueInvoices.php`
- [ ] Runs daily via `Schedule::command(...)->daily()`
- [ ] Finds all `pending`/`partial` invoices where `due_date < today`
- [ ] Updates status to `overdue`
- [ ] Creates notification for assigned user / accountant role

### Tests
- [ ] `tests/Feature/Commands/MarkOverdueInvoicesTest.php`

---

## Step 3.5 — Frontend: Customers & Suppliers

### Tasks
- [ ] Pages: `/dashboard/customers` list, `/customers/new`, `/customers/[id]`
- [ ] Customer detail: info + sales history + account statement + balance
- [ ] **Quick-add modal** (used from invoice form dropdown):
  - `<CustomerQuickAddModal>` component
  - Triggered from searchable dropdown when "Add new customer" selected
  - Saves and returns new customer to dropdown without page navigation
- [ ] Same pattern for Suppliers
- [ ] Credit limit indicator on customer card

### Tests (Playwright)
- [ ] `tests/e2e/customers/customer-crud.spec.ts`
- [ ] `tests/e2e/customers/quick-add-modal.spec.ts`

---

## Step 3.6 — Frontend: Invoicing Module

### Tasks
- [ ] Page: `/dashboard/invoicing` — tabbed (Sales Orders | Purchase Orders | Payments)
- [ ] List with: status filter pills, date range picker, search, export button
- [ ] Page: `/dashboard/invoicing/sales/new`
  - Customer dropdown (searchable + quick-add modal)
  - Date pickers: issue date, due date, delivery date
  - Line items section:
    - Search/add items by SKU, barcode, or text search
    - Items show: SKU, description, season/gender/type, unit price
    - Quantity, discount per line
    - Computed line total
  - Discount section (flat or %)
  - VAT auto-calculated and displayed (grayed if 0%)
  - Total section
  - Notes / terms textarea
  - Save as draft / Confirm buttons
- [ ] Page: `/dashboard/invoicing/sales/[id]` — view with:
  - Status badge + action buttons (Confirm / Cancel / Record Payment / Download PDF / Send Email)
  - Lines table
  - Payments history table
  - Add payment form (inline)
  - Credit note button (if applicable)
- [ ] Same pages for Purchase Orders
- [ ] Status badge component (color per status from DECISIONS.md D-004)
- [ ] `useInvoices()`, `useInvoice(id)`, `usePayments(invoiceId)` hooks

### Tests (Playwright)
- [ ] `tests/e2e/invoicing/sales-order-create.spec.ts`
- [ ] `tests/e2e/invoicing/sales-order-full-flow.spec.ts` — create→confirm→pay
- [ ] `tests/e2e/invoicing/quick-add-customer-from-invoice.spec.ts`
- [ ] `tests/e2e/invoicing/purchase-order-create.spec.ts`
- [ ] `tests/e2e/invoicing/payment-recording.spec.ts`

---

## Phase 3 CI/CD Gate

- [ ] All PHPUnit tests pass (Unit + Feature)
- [ ] All Playwright tests pass
- [ ] `php artisan migrate:fresh --seed` — clean
- [ ] PDF generation tested (queued job processes)
- [ ] No N+1 on invoice list (eager loads customer, payment counts)
