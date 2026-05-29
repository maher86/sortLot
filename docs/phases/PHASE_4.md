# PHASE 4 — Dashboard & Charts

> Prerequisites: Phase 3 complete (invoicing data exists)

---

## Dashboard Architecture

All dashboard data served from dedicated `/dashboard/*` endpoints.  
Each endpoint:
- Accepts `?from=YYYY-MM-DD&to=YYYY-MM-DD` (default: last 30 days)
- Results cached in Redis with tag `dashboard:{user_id}` or global tags
- Cache TTL: 5 minutes (configurable in preferences)
- Cache invalidated on relevant model changes via Observers

---

## Step 4.1 — Backend: Dashboard Endpoints

### 4.1a — KPI Summary Cards
`GET /dashboard/summary`
```json
{
  "packages": {
    "total": 142,
    "unsorted": 12,
    "sorting": 3,
    "sorted": 87,
    "shipped": 40
  },
  "items": {
    "total": 8420,
    "available": 5100,
    "reserved": 340,
    "sold": 2980
  },
  "revenue": {
    "period_fils": 184500000,
    "period_invoices": 34,
    "outstanding_fils": 22000000
  },
  "purchases": {
    "period_fils": 95000000,
    "unpaid_fils": 15000000
  },
  "overdue_invoices": 5
}
```

### 4.1b — Chart Endpoints (each returns `{labels: [], datasets: []}`)

```
GET /dashboard/packages-by-status          pie chart data
GET /dashboard/packages-by-origin-country  map/bar chart
GET /dashboard/items-by-season             bar chart
GET /dashboard/items-by-gender             donut chart
GET /dashboard/items-by-type               horizontal bar (top 10 types)
GET /dashboard/items-by-pricing-tier       bar chart (0,K1..K5)
GET /dashboard/items-by-season-and-gender  grouped bar (matrix)
GET /dashboard/revenue-over-time           ?group_by=day|week|month  line chart
GET /dashboard/purchases-over-time         line chart
GET /dashboard/gross-profit-over-time      line chart (revenue - purchases)
GET /dashboard/top-customers               ?limit=10  horizontal bar
GET /dashboard/top-suppliers               ?limit=10  horizontal bar
GET /dashboard/invoice-status-breakdown    donut (paid/pending/partial/overdue/...)
GET /dashboard/payment-methods             donut
GET /dashboard/vat-summary                 stacked bar (output vs input)
GET /dashboard/overdue-invoices            list endpoint (not chart)
GET /dashboard/sorting-throughput          items sorted per day (line chart)
GET /dashboard/package-turnaround          avg days in_warehouse→sorted (stat card)
GET /dashboard/activity-feed              recent 20 events
```

### Tasks
- [ ] `DashboardController` with all endpoints
- [ ] `DashboardService` — all query methods, cached
- [ ] Cache tag invalidation:
  - Package changes → invalidate `dashboard.packages.*`
  - Item changes → invalidate `dashboard.items.*`
  - Invoice changes → invalidate `dashboard.revenue.*`, `dashboard.invoices.*`
- [ ] Date range validation (max 1 year range)

### Tests
- [ ] `tests/Feature/Dashboard/DashboardSummaryTest.php`
- [ ] `tests/Feature/Dashboard/DashboardChartsTest.php` — each endpoint returns correct shape
- [ ] `tests/Feature/Dashboard/DashboardCacheTest.php` — cached response, invalidation

---

## Step 4.2 — Backend: Reports

### Tasks
- [ ] `ReportController`
- [ ] `VAT Report` — quarterly, output/input tax breakdown (see VAT.md)
- [ ] `Inventory Report` — as-of-date snapshot (items by status, season, gender, type)
- [ ] `Sales Report` — revenue, invoice count, avg order value, by customer
- [ ] `Purchases Report` — spend by supplier
- [ ] `Aging Report` — receivables: how old are unpaid invoices (0-30, 31-60, 61-90, 90+ days)
- [ ] `Customer Ledger` — all transactions for a customer, running balance
- [ ] `Supplier Ledger` — all transactions for a supplier
- [ ] Export to PDF: queued job, `GenerateReportPdfJob`
- [ ] Export to CSV: synchronous for small, queued for large

### Tests
- [ ] `tests/Feature/Reports/VatReportTest.php`
- [ ] `tests/Feature/Reports/AgingReportTest.php`
- [ ] `tests/Feature/Reports/InventoryReportTest.php`

---

## Step 4.3 — Frontend: Dashboard Page

### Tech Stack
- Chart library: **Recharts** (React-native, no canvas, SSR-compatible)
- Date range: custom `DateRangePicker` component (shadcn/ui Popover + react-day-picker)
- Stats: shadcn/ui Card
- Real-time: Polling every 60s OR Laravel Reverb WebSocket push

### Layout
```
┌──────────────────────────────────────────────────────────┐
│  Dashboard           [Date Range: Jun 1 – Jun 30 ▼]     │
├──────────┬──────────┬──────────┬──────────┬─────────────┤
│ Packages │  Items   │ Revenue  │Purchases │  Overdue    │
│   142    │  8,420   │ AED 184k │ AED 95k  │    5 inv.   │
├──────────┴──────────┴──────────┴──────────┴─────────────┤
│  Revenue over time (line)    │  Invoice status (donut)  │
│  [full width 60%]            │  [40%]                   │
├──────────────────────────────┼─────────────────────────┤
│  Items by season (bar)       │  Items by gender (donut)│
├──────────────────────────────┼─────────────────────────┤
│  Items by pricing tier (bar) │  Top customers (h-bar)  │
├──────────────────────────────┼─────────────────────────┤
│  Packages by origin (bar)    │  Items by type (h-bar)  │
├──────────────────────────────┴─────────────────────────┤
│  Overdue invoices (table — customer, amount, days late) │
├────────────────────────────────────────────────────────┤
│  Activity feed (timeline)                              │
└────────────────────────────────────────────────────────┘
```

### Tasks
- [ ] `DashboardPage` — server component, passes initial data from RSC fetch
- [ ] `KpiCard` component — number, label, trend indicator (↑ vs last period), color
- [ ] `ChartCard` component — wrapper with title, date range filter, loading skeleton, error state
- [ ] Each chart as its own component (Recharts):
  - `RevenueLineChart`
  - `InvoiceStatusDonut`
  - `ItemsBySeasonBar`
  - `ItemsByGenderDonut`
  - `ItemsByTypeBar` (horizontal)
  - `ItemsByPricingTierBar`
  - `PackagesByOriginBar`
  - `TopCustomersBar` (horizontal)
- [ ] `OverdueInvoicesTable` — sortable, links to invoice detail
- [ ] `ActivityFeed` — timestamped events list
- [ ] Global date range filter at top — updates all charts simultaneously via Zustand `dashboardDateRange` store
- [ ] Each chart also has its own override date range (click gear icon → "Custom range for this chart")
- [ ] Loading skeletons for every chart (not spinners)
- [ ] Empty state illustrations for new accounts

### Tests (Playwright)
- [ ] `tests/e2e/dashboard/dashboard-loads.spec.ts`
- [ ] `tests/e2e/dashboard/date-range-filter.spec.ts` — change range, charts re-fetch
- [ ] `tests/e2e/dashboard/kpi-cards.spec.ts`

---

## Step 4.4 — Frontend: Reports Page

### Tasks
- [ ] Page: `/dashboard/reports`
- [ ] Report selector (VAT, Inventory, Sales, Purchases, Aging, Ledger)
- [ ] Date range and parameter form per report type
- [ ] Preview table (first 50 rows)
- [ ] Export buttons: PDF (queued, shows download link) and CSV (immediate)
- [ ] VAT report shows: output tax table, input tax table, net payable summary

### Tests (Playwright)
- [ ] `tests/e2e/reports/vat-report.spec.ts`
- [ ] `tests/e2e/reports/export-csv.spec.ts`

---

## Phase 4 CI/CD Gate

- [ ] All PHPUnit tests pass
- [ ] All Playwright tests pass
- [ ] Dashboard loads in < 1.5s cold (Lighthouse check)
- [ ] All chart endpoints respond in < 200ms (with warm cache)
- [ ] Redis cache confirmed working (`php artisan cache:clear` + re-check)
