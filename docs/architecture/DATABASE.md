# DATABASE SCHEMA

> All amounts stored as BIGINT (fils = AED × 100). All tables use `id` ULID primary key for sortability + privacy.  
> All tables have `created_at`, `updated_at`. Business entities also have `deleted_at` (soft delete).

---

## Core Tables

### `users`
```sql
id              CHAR(26) PK (ULID)
name            VARCHAR(100)
email           VARCHAR(150) UNIQUE
password        VARCHAR(255)
phone           VARCHAR(30)
is_active       BOOLEAN DEFAULT true
last_login_at   TIMESTAMP NULL
last_login_ip   VARCHAR(45) NULL
created_at, updated_at, deleted_at
```

### `roles` / `permissions` / `model_has_roles` / `model_has_permissions` / `role_has_permissions`
_(Spatie standard schema — see spatie/laravel-permission docs)_

---

## Preferences / Configuration

### `preferences`
Single-row config table (key-value store for system settings).
```sql
id              INT PK
key             VARCHAR(100) UNIQUE
value           TEXT
group           VARCHAR(50)   -- 'invoice', 'vat', 'company', 'pricing'
label           VARCHAR(150)
created_at, updated_at
```
Key examples: `company_name`, `company_trn` (Tax Registration Number), `vat_rate_mainland`, `invoice_prefix_sales`, `invoice_prefix_purchase`, `invoice_next_seq_sales`, `invoice_next_seq_purchase`, `default_currency`, `company_logo_path`, `payment_terms_days`.

### `pricing_tiers`
```sql
id              INT PK
code            VARCHAR(10) UNIQUE  -- '0', 'K1', 'K2', 'K3', 'K4', 'K5'
label           VARCHAR(50)         -- 'Scrap', 'Grade 1', 'Grade 2', ...
price_per_kg_fils   BIGINT UNSIGNED NULL
price_flat_fils     BIGINT UNSIGNED NULL  -- alt: flat price per item
description     TEXT NULL
is_active       BOOLEAN DEFAULT true
sort_order      TINYINT
created_at, updated_at
```

### `item_types`
```sql
id              INT PK
name            VARCHAR(100)   -- 'Skirt', 'Pants', 'Shirt', 'Short', ...
slug            VARCHAR(100) UNIQUE
is_active       BOOLEAN DEFAULT true
sort_order      SMALLINT
created_at, updated_at
```

---

## Suppliers & Customers

### `suppliers`
```sql
id              CHAR(26) PK
name            VARCHAR(150)
contact_name    VARCHAR(100) NULL
email           VARCHAR(150) NULL
phone           VARCHAR(30) NULL
country         VARCHAR(100)
address         TEXT NULL
vat_type        ENUM('mainland','free_zone','international') DEFAULT 'international'
trn             VARCHAR(50) NULL    -- Tax Registration Number
bank_name       VARCHAR(100) NULL
bank_iban       VARCHAR(50) NULL
bank_swift      VARCHAR(20) NULL
notes           TEXT NULL
is_active       BOOLEAN DEFAULT true
created_at, updated_at, deleted_at
```

### `customers`
```sql
id              CHAR(26) PK
name            VARCHAR(150)
contact_name    VARCHAR(100) NULL
email           VARCHAR(150) NULL
phone           VARCHAR(30) NULL
country         VARCHAR(100) DEFAULT 'AE'
emirate         VARCHAR(50) NULL    -- Dubai, Sharjah, Abu Dhabi, ...
address         TEXT NULL
vat_type        ENUM('mainland','free_zone','international') DEFAULT 'mainland'
trn             VARCHAR(50) NULL
credit_limit_fils   BIGINT UNSIGNED DEFAULT 0
payment_terms_days  SMALLINT DEFAULT 0
notes           TEXT NULL
is_active       BOOLEAN DEFAULT true
created_at, updated_at, deleted_at
```

---

## Packages & Items

### `packages`
```sql
id              CHAR(26) PK
reference       VARCHAR(50) UNIQUE  -- e.g. PKG-2024-00042
supplier_id     CHAR(26) FK → suppliers
purchase_order_id  CHAR(26) FK → invoices NULL  -- linked PO
origin_country  VARCHAR(100)
destination_country VARCHAR(100) DEFAULT 'AE'
status          ENUM(
                  'in_transit','at_port','in_customs',
                  'in_warehouse','sorting','sorted',
                  'partially_shipped','shipped','closed'
                ) DEFAULT 'in_transit'
weight_kg       DECIMAL(8,2) NULL
number_of_bags  SMALLINT NULL        -- physical bags in package
notes           TEXT NULL
arrived_at      TIMESTAMP NULL
sorting_started_at  TIMESTAMP NULL
sorting_completed_at TIMESTAMP NULL
sorted_by       CHAR(26) FK → users NULL
created_by      CHAR(26) FK → users
created_at, updated_at, deleted_at

INDEXES: status, supplier_id, created_at, (status, created_at)
```

### `items`
```sql
id              CHAR(26) PK
package_id      CHAR(26) FK → packages
sku             VARCHAR(50) UNIQUE    -- auto-generated
barcode         VARCHAR(100) NULL
season          ENUM('summer','winter','spring','general')
gender          ENUM('man','woman','girl','boy')
item_type_id    INT FK → item_types
pricing_tier_id INT FK → pricing_tiers
condition_notes TEXT NULL
status          ENUM('available','reserved','sold','returned','damaged','missing') DEFAULT 'available'
quantity        SMALLINT DEFAULT 1   -- usually 1, but bulk lots possible
weight_kg       DECIMAL(6,2) NULL
unit_price_fils BIGINT UNSIGNED      -- price at time of listing
sales_order_id  CHAR(26) FK → invoices NULL   -- set when reserved/sold
sorted_by       CHAR(26) FK → users NULL
created_at, updated_at, deleted_at

INDEXES: package_id, status, season, gender, item_type_id, pricing_tier_id, sku, barcode
COMPOSITE: (package_id, status), (season, gender, status)
```

---

## Invoicing

### `invoices`
```sql
id                  CHAR(26) PK
type                ENUM('sales_order','purchase_order','credit_note')
number              VARCHAR(50) UNIQUE   -- e.g. SO-2024-00001
reference           VARCHAR(100) NULL     -- customer/supplier PO ref
status              ENUM(
                      'draft','pending','partial','paid',
                      'overdue','cancelled','refunded','disputed','write_off'
                    ) DEFAULT 'draft'
customer_id         CHAR(26) FK → customers NULL   -- set for sales_order
supplier_id         CHAR(26) FK → suppliers NULL   -- set for purchase_order
related_invoice_id  CHAR(26) FK → invoices NULL    -- for credit notes
issue_date          DATE
due_date            DATE NULL
delivery_date       DATE NULL
subtotal_fils       BIGINT UNSIGNED DEFAULT 0
discount_fils       BIGINT UNSIGNED DEFAULT 0
discount_pct        DECIMAL(5,2) DEFAULT 0
vat_rate            DECIMAL(5,2) DEFAULT 0          -- 0 or 5.00
vat_amount_fils     BIGINT UNSIGNED DEFAULT 0
total_fils          BIGINT UNSIGNED DEFAULT 0
paid_amount_fils    BIGINT UNSIGNED DEFAULT 0
balance_fils        BIGINT UNSIGNED DEFAULT 0
currency            CHAR(3) DEFAULT 'AED'
exchange_rate       DECIMAL(10,6) DEFAULT 1.000000
notes               TEXT NULL
internal_notes      TEXT NULL
terms               TEXT NULL
pdf_path            VARCHAR(255) NULL
pdf_generated_at    TIMESTAMP NULL
sent_at             TIMESTAMP NULL
paid_at             TIMESTAMP NULL
created_by          CHAR(26) FK → users
updated_by          CHAR(26) FK → users NULL
created_at, updated_at, deleted_at

INDEXES: type, status, customer_id, supplier_id, issue_date, due_date, number
COMPOSITE: (type, status), (customer_id, status), (type, issue_date)
```

### `invoice_lines`
```sql
id              CHAR(26) PK
invoice_id      CHAR(26) FK → invoices
item_id         CHAR(26) FK → items NULL    -- NULL for manual/non-item lines
description     VARCHAR(255)
quantity        DECIMAL(10,3) DEFAULT 1
unit_price_fils BIGINT UNSIGNED
discount_pct    DECIMAL(5,2) DEFAULT 0
line_total_fils BIGINT UNSIGNED
sort_order      SMALLINT DEFAULT 0
created_at, updated_at

INDEXES: invoice_id, item_id
```

### `payments`
```sql
id              CHAR(26) PK
invoice_id      CHAR(26) FK → invoices
amount_fils     BIGINT UNSIGNED
payment_method  ENUM('cash','bank_transfer','cheque','card','other')
payment_date    DATE
reference       VARCHAR(100) NULL    -- cheque no, transfer ref, etc.
bank_name       VARCHAR(100) NULL
notes           TEXT NULL
created_by      CHAR(26) FK → users
created_at, updated_at, deleted_at

INDEXES: invoice_id, payment_date
```

---

## Supporting Tables

### `audit_logs`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
user_id         CHAR(26) FK → users NULL
action          VARCHAR(50)      -- 'created','updated','deleted','status_changed','login'
model_type      VARCHAR(100)     -- 'App\Models\Invoice'
model_id        CHAR(26)
old_values      JSON NULL
new_values      JSON NULL
ip_address      VARCHAR(45) NULL
user_agent      VARCHAR(255) NULL
created_at      TIMESTAMP

INDEXES: user_id, model_type, model_id, action, created_at
COMPOSITE: (model_type, model_id)
```

### `notifications`
_(Laravel standard notifications table)_

### `activity_feed`
```sql
id              CHAR(26) PK
user_id         CHAR(26) FK → users
type            VARCHAR(100)
subject_type    VARCHAR(100)
subject_id      CHAR(26)
description     VARCHAR(255)
meta            JSON NULL
created_at      TIMESTAMP

INDEXES: user_id, type, created_at
```

---

## Key Relationships Summary

```
suppliers     1──∞  packages
suppliers     1──∞  invoices (purchase_orders)
customers     1──∞  invoices (sales_orders)
packages      1──∞  items
invoices      1──∞  invoice_lines
invoice_lines ∞──1  items       (item sold)
invoices      1──∞  payments
invoices      1──∞  invoices    (credit note → original)
users         ∞──∞  roles
roles         ∞──∞  permissions
```

---

## Migration Order (respects FK constraints)

1. users
2. roles/permissions (Spatie)
3. preferences
4. pricing_tiers
5. item_types
6. suppliers
7. customers
8. packages  (→ suppliers, users)
9. invoices  (→ customers, suppliers, users)
10. items    (→ packages, item_types, pricing_tiers, invoices, users)
11. invoice_lines (→ invoices, items)
12. payments (→ invoices, users)
13. audit_logs (→ users)
14. notifications
15. activity_feed (→ users)
