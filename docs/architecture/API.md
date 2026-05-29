# API ENDPOINTS

> Base URL: `/api/v1/`  
> Auth: Sanctum cookie. All endpoints require authentication except `/auth/login`.  
> Standard query params on all lists: `?page=&per_page=&sort=field|-field&search=&filter[status]=`

---

## Auth
```
POST   /auth/login              body: {email, password}
POST   /auth/logout
GET    /auth/me                 returns user + roles + permissions
PATCH  /auth/password           body: {current_password, password, password_confirmation}
GET    /auth/activity           recent login history
```

## Users (super_admin only)
```
GET    /users
POST   /users
GET    /users/{id}
PATCH  /users/{id}
DELETE /users/{id}
POST   /users/{id}/restore
PATCH  /users/{id}/roles        body: {roles: ['manager']}
```

## Preferences
```
GET    /preferences             returns all as key-value object
PATCH  /preferences             body: {key: value, ...}
GET    /preferences/pricing-tiers
POST   /preferences/pricing-tiers
PATCH  /preferences/pricing-tiers/{id}
DELETE /preferences/pricing-tiers/{id}
GET    /preferences/item-types
POST   /preferences/item-types
PATCH  /preferences/item-types/{id}
DELETE /preferences/item-types/{id}
```

## Suppliers
```
GET    /suppliers               ?filter[is_active]=true&search=
POST   /suppliers
GET    /suppliers/{id}
PATCH  /suppliers/{id}
DELETE /suppliers/{id}
POST   /suppliers/{id}/restore
GET    /suppliers/{id}/purchase-orders
GET    /suppliers/{id}/packages
```

## Customers
```
GET    /customers               ?filter[vat_type]=mainland&search=
POST   /customers
GET    /customers/{id}
PATCH  /customers/{id}
DELETE /customers/{id}
POST   /customers/{id}/restore
GET    /customers/{id}/sales-orders
GET    /customers/{id}/statement  ?from=&to=   (account statement)
```

## Packages
```
GET    /packages                ?filter[status]=&filter[supplier_id]=&filter[season]=
POST   /packages
GET    /packages/{id}           includes: supplier, items summary, purchase_order
PATCH  /packages/{id}
DELETE /packages/{id}
PATCH  /packages/{id}/status    body: {status: 'sorting'}
GET    /packages/{id}/items     full items list for this package
POST   /packages/{id}/items/bulk   body: {items: [{season,gender,...},...]}  (batch create during sorting)
```

## Items
```
GET    /items                   ?filter[status]=&filter[season]=&filter[gender]=&filter[item_type_id]=&filter[pricing_tier_id]=&filter[package_id]=
POST   /items
GET    /items/{id}
PATCH  /items/{id}
DELETE /items/{id}
PATCH  /items/{id}/status       body: {status: 'damaged', notes: '...'}
GET    /items/sku/{sku}         barcode/SKU lookup
GET    /items/barcode/{barcode}
```

## Invoices (Sales Orders)
```
GET    /sales-orders            ?filter[status]=&filter[customer_id]=&from=&to=
POST   /sales-orders            body: {customer_id, issue_date, due_date, lines: [...]}
GET    /sales-orders/{id}       includes: customer, lines (with items), payments
PATCH  /sales-orders/{id}
DELETE /sales-orders/{id}       (only draft/cancelled)
PATCH  /sales-orders/{id}/confirm   draft → pending
PATCH  /sales-orders/{id}/cancel
POST   /sales-orders/{id}/credit-note
GET    /sales-orders/{id}/pdf
POST   /sales-orders/{id}/send-email
```

## Invoices (Purchase Orders)
```
GET    /purchase-orders         ?filter[status]=&filter[supplier_id]=&from=&to=
POST   /purchase-orders
GET    /purchase-orders/{id}
PATCH  /purchase-orders/{id}
DELETE /purchase-orders/{id}
PATCH  /purchase-orders/{id}/confirm
PATCH  /purchase-orders/{id}/cancel
GET    /purchase-orders/{id}/pdf
```

## Payments
```
GET    /payments                ?filter[invoice_id]=&filter[method]=&from=&to=
POST   /payments                body: {invoice_id, amount_fils, payment_method, payment_date, reference}
GET    /payments/{id}
DELETE /payments/{id}           (super_admin only, adds audit entry)
```

## Dashboard
```
GET    /dashboard/summary       ?from=&to=    KPI cards
GET    /dashboard/packages-by-status
GET    /dashboard/items-by-season
GET    /dashboard/items-by-gender
GET    /dashboard/items-by-type
GET    /dashboard/items-by-pricing-tier
GET    /dashboard/revenue-over-time         ?from=&to=&group_by=day|week|month
GET    /dashboard/top-customers             ?from=&to=&limit=10
GET    /dashboard/top-suppliers             ?from=&to=&limit=10
GET    /dashboard/packages-by-origin-country
GET    /dashboard/invoice-status-breakdown  ?from=&to=
GET    /dashboard/payment-methods
GET    /dashboard/vat-summary               ?from=&to=
GET    /dashboard/overdue-invoices
GET    /dashboard/activity-feed             recent system events
```

## Reports
```
GET    /reports/vat             ?from=&to=&format=json|pdf|csv
GET    /reports/inventory       ?as_of=date&format=json|pdf|csv
GET    /reports/sales           ?from=&to=&format=json|pdf|csv
GET    /reports/purchases       ?from=&to=&format=json|pdf|csv
GET    /reports/customer-ledger ?customer_id=&from=&to=
GET    /reports/supplier-ledger ?supplier_id=&from=&to=
GET    /reports/aging           ?type=receivables|payables&as_of=date
```

## Audit
```
GET    /audit-logs              ?filter[user_id]=&filter[model_type]=&filter[action]=&from=&to=
```

## Notifications
```
GET    /notifications           ?filter[read]=false
PATCH  /notifications/{id}/read
POST   /notifications/read-all
```

## Health
```
GET    /health                  returns {status: 'ok', db: 'ok', redis: 'ok', queue: 'ok'}
```

---

## Standard Response Envelopes

### List
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 142,
    "last_page": 6
  },
  "links": {
    "first": "...", "last": "...", "prev": null, "next": "..."
  }
}
```

### Single Resource
```json
{
  "data": { "id": "...", ... }
}
```

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "customer_id": ["Customer not found."]
  }
}
```

### Business Logic Error (409/422)
```json
{
  "message": "Cannot confirm invoice: all items must be available.",
  "code": "ITEMS_NOT_AVAILABLE"
}
```
