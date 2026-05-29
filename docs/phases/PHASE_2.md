# PHASE 2 — Packages & Items

> Prerequisites: Phase 1 complete (Docker running, auth working, RBAC seeded)

---

## Step 2.1 — Migrations & Models

### Tasks
- [ ] Migration: `pricing_tiers`
- [ ] Migration: `item_types`
- [ ] Migration: `packages` (all indexes)
- [ ] Migration: `items` (all indexes, composite indexes)
- [ ] Model: `PricingTier` (fillable, casts)
- [ ] Model: `ItemType` (fillable)
- [ ] Model: `Package` with:
  - Relationships: `belongsTo(Supplier)`, `belongsTo(User, 'sorted_by')`, `hasMany(Item)`, `belongsTo(Invoice, 'purchase_order_id')`
  - Enum casts for `status`
  - Accessor: `items_count`, `available_items_count`
  - Scope: `scopeByStatus()`, `scopeUnsorted()`, `scopeSorted()`
  - `SoftDeletes`, `Auditable` traits
- [ ] Model: `Item` with:
  - Relationships: `belongsTo(Package)`, `belongsTo(ItemType)`, `belongsTo(PricingTier)`, `belongsTo(User, 'sorted_by')`
  - Enum casts for `season`, `gender`, `status`
  - Auto SKU generation on creating: `PKG-{package_ref}-{sequence}`
  - `SoftDeletes`, `Auditable` traits
- [ ] Seeders: 10 pricing tiers (0, K1–K5 + variants), 15 item types

### Tests (PHPUnit)
- [ ] `tests/Unit/Models/PackageTest.php` — status transitions, relationships
- [ ] `tests/Unit/Models/ItemTest.php` — SKU generation, status, relationships

---

## Step 2.2 — Package API

### Tasks
- [ ] `PackageController` (resourceful) with Policy `PackagePolicy`
- [ ] `PackageRequest` (store + update validation)
- [ ] `PackageResource` (API resource with items summary counts)
- [ ] `PackageService::changeStatus()` — validates allowed transitions, fires event
- [ ] Status transitions allowed:
  ```
  in_transit → at_port → in_customs → in_warehouse → sorting → sorted → partially_shipped → shipped → closed
  ```
  Only forward transitions allowed (except: sorted → sorting for re-sort)
- [ ] Bulk items creation endpoint: `POST /packages/{id}/items/bulk`
- [ ] Register routes in `api.php`

### Tests (PHPUnit Feature)
- [ ] `tests/Feature/Packages/PackageCrudTest.php`
- [ ] `tests/Feature/Packages/PackageStatusTest.php` — valid/invalid transitions
- [ ] `tests/Feature/Packages/PackagePermissionTest.php` — each role

---

## Step 2.3 — Items API

### Tasks
- [ ] `ItemController` with Policy `ItemPolicy`
- [ ] `ItemRequest` validation
- [ ] `ItemResource`
- [ ] `GET /items/sku/{sku}` and `GET /items/barcode/{barcode}` lookup endpoints
- [ ] Item status change with reason logging to `audit_logs`
- [ ] Filtering: season, gender, type, pricing tier, package, status (all combinable)
- [ ] Cursor pagination for large datasets

### Tests
- [ ] `tests/Feature/Items/ItemCrudTest.php`
- [ ] `tests/Feature/Items/ItemFilterTest.php` — complex filter combinations
- [ ] `tests/Feature/Items/ItemStatusTest.php`
- [ ] `tests/Feature/Items/ItemPermissionTest.php`

---

## Step 2.4 — Preferences API (Pricing Tiers & Item Types)

### Tasks
- [ ] `PreferenceController` with `preferences.edit` permission gate
- [ ] CRUD for `pricing_tiers`
- [ ] CRUD for `item_types`
- [ ] GET all preferences as flat key-value
- [ ] PATCH preferences (multi-key update)
- [ ] Cache preferences with Redis tag `preferences`, invalidate on update

### Tests
- [ ] `tests/Feature/Preferences/PricingTierTest.php`
- [ ] `tests/Feature/Preferences/ItemTypeTest.php`
- [ ] `tests/Feature/Preferences/PreferenceCacheTest.php`

---

## Step 2.5 — Frontend: Packages Module

### Tasks
- [ ] Page: `/dashboard/packages` — list with filters (status, supplier, date range), search, pagination
- [ ] Page: `/dashboard/packages/new` — create package form
- [ ] Page: `/dashboard/packages/[id]` — detail view with:
  - Package info card
  - Status timeline/stepper
  - Items sub-table with filters
  - "Start Sorting" button → changes status
  - "Add Items" modal (bulk create form)
- [ ] Status badge component (color-coded per status)
- [ ] `usePackages()` hook with TanStack Query
- [ ] Optimistic updates on status change

### Tests (Playwright)
- [ ] `tests/e2e/packages/package-list.spec.ts`
- [ ] `tests/e2e/packages/package-create.spec.ts`
- [ ] `tests/e2e/packages/package-sorting-flow.spec.ts` — full sorting workflow

---

## Step 2.6 — Frontend: Items Module

### Tasks
- [ ] Page: `/dashboard/items` — master list (all items across all packages)
- [ ] Advanced filter sidebar: season, gender, type, pricing tier, status, package
- [ ] Item row with quick-status change (dropdown)
- [ ] Page: `/dashboard/items/[id]` — item detail
- [ ] Barcode/SKU display + print button (future hook)
- [ ] `useItems()` hook

### Tests (Playwright)
- [ ] `tests/e2e/items/item-list.spec.ts`
- [ ] `tests/e2e/items/item-filter.spec.ts`

---

## Step 2.7 — Frontend: Preferences Module

### Tasks
- [ ] Page: `/dashboard/preferences` — tabbed layout:
  - Tab 1: Company Info (name, TRN, address, logo upload)
  - Tab 2: Pricing Tiers (table with inline edit, add/delete)
  - Tab 3: Item Types (same)
  - Tab 4: Invoice Settings (prefixes, payment terms, default notes)
  - Tab 5: VAT Settings (rates, registration number)

### Tests (Playwright)
- [ ] `tests/e2e/preferences/pricing-tiers.spec.ts`

---

## Phase 2 CI/CD Gate

All of the following must pass before Phase 3 begins:
- [ ] `php artisan test --testsuite=Feature` — 0 failures
- [ ] `php artisan test --testsuite=Unit` — 0 failures
- [ ] Playwright e2e suite — 0 failures
- [ ] `php artisan migrate:fresh --seed` completes without error
- [ ] No N+1 queries (Laravel Debugbar check on list endpoints)
