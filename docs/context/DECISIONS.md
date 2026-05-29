# DECISIONS — Architecture & Design Choices

> Append new decisions as they are made. Never delete old ones — mark as superseded if changed.

---

## D-001 — Frontend: Next.js 14 App Router
**Decision:** Next.js 14 with App Router (not Pages Router), not Vite SPA.  
**Rationale:** Server Components reduce client JS bundle. Route-level auth middleware is cleaner. API route handlers can proxy sensitive requests. Better SEO if needed later. SaaS dashboard benefits from RSC data-fetching patterns.  
**Impact:** Frontend lives in `frontend/app/`. Use `"use client"` directive only on interactive components.

---

## D-002 — Auth: Laravel Sanctum (SPA mode)
**Decision:** Sanctum SPA cookie auth (not token-based API tokens).  
**Rationale:** Cookie-based is more secure for same-origin SPA. CSRF protection built-in. Simpler refresh logic. Works well with Next.js middleware.  
**Impact:** Frontend and backend must share domain in production. In Docker dev: `localhost` shared. Set `SANCTUM_STATEFUL_DOMAINS=localhost:3000`.

---

## D-003 — RBAC: Spatie Laravel Permission
**Decision:** Use `spatie/laravel-permission` v6.  
**Rationale:** Industry standard, battle-tested. Direct/role permissions. Caching built-in. Pairs well with Laravel Policies for model-level control.  
**Impact:** Every API controller checks `$user->can('permission-name')`. Frontend checks role from `/auth/me` response and hides UI accordingly.

---

## D-004 — Invoice Statuses (Full Set)
**Decision:** The following statuses cover the full lifecycle:

| Status | Meaning |
|--------|---------|
| `draft` | Created, not yet sent/confirmed |
| `pending` | Issued, awaiting payment |
| `partial` | Part-paid, balance outstanding |
| `paid` | Fully settled |
| `overdue` | Past due date, unpaid |
| `cancelled` | Voided, no financial effect |
| `refunded` | Payment returned to customer |
| `disputed` | Customer raised issue, on hold |
| `write_off` | Bad debt, written off |

**Rationale:** `draft → pending → partial/paid/overdue → cancelled/refunded/disputed/write_off`. Covers all real-world scenarios including disputes and bad debt which are common in B2B used-goods trade.

---

## D-005 — Package & Item Status Lifecycle

**Package statuses:**
| Status | Meaning |
|--------|---------|
| `in_transit` | Purchased, not yet arrived at port |
| `at_port` | Arrived, awaiting clearance |
| `in_customs` | Under customs inspection |
| `in_warehouse` | Cleared, in warehouse, unsorted |
| `sorting` | Being actively sorted by staff |
| `sorted` | Fully sorted, items catalogued |
| `partially_shipped` | Some items sold, some remain |
| `shipped` | All items sold/dispatched |
| `closed` | Archived |

**Item (piece) statuses:**
| Status | Meaning |
|--------|---------|
| `available` | In stock, ready to sell |
| `reserved` | On a draft/pending sales order |
| `sold` | On a confirmed invoice |
| `returned` | Returned by customer |
| `damaged` | Written off, unsellable |
| `missing` | Lost in audit |

---

## D-006 — UAE VAT Treatment
**Decision:** Implement dual-zone VAT logic.  
**Rules:**
- Company is in Hamriyah Free Zone (Designated Zone under UAE VAT law)
- Sales to UAE mainland customers: **5% VAT applies**
- Sales to other Designated Zones or international export: **0% (zero-rated)**
- Purchases from mainland suppliers: **Input VAT recoverable**
- Purchases from FZ/overseas suppliers: **No VAT**
- VAT registration number stored in Preferences
- Each customer/supplier has a `vat_type` field: `mainland` | `free_zone` | `international`
- Invoice auto-calculates VAT based on customer type
- VAT reports generated per quarter (FTA requirement)

**Reference:** UAE VAT Public Clarification VATP013 on Designated Zones.

---

## D-007 — Performance Strategy
**Decision:** Multi-layer caching and query optimization from day one.

| Layer | Tool | Usage |
|-------|------|-------|
| DB query cache | Redis | Dashboard aggregations, TTL 5min |
| HTTP response cache | Laravel ResponseCache | Public-safe GET endpoints |
| ORM eager loading | Eloquent `with()` | All list endpoints — no N+1 |
| DB indexes | Migration-defined | All FK columns + status + date columns |
| Pagination | Cursor-based | Large lists (items, invoices) |
| Queue | Laravel Horizon + Redis | PDF generation, email, report exports |
| Frontend | TanStack Query | Client-side cache + stale-while-revalidate |
| Frontend | Next.js RSC | Server-rendered initial page data |

---

## D-008 — Soft Deletes Policy
**Decision:** All business entities use `SoftDeletes`. Hard delete is prohibited on records that appear on invoices.  
**Rationale:** Audit trail. UAE law requires financial records kept 5 years. A deleted customer must still be visible on historical invoices.

---

## D-009 — Audit Log
**Decision:** All create/update/delete on business records logged to `audit_logs` table via a `Auditable` trait.  
**Fields:** `user_id`, `action`, `model_type`, `model_id`, `old_values`, `new_values`, `ip_address`, `created_at`.  
**Rationale:** Compliance + fraud detection + debugging.

---

## D-010 — Multi-currency (deferred)
**Decision:** System defaults to AED. USD support added in Phase 5. All amounts stored as integers (fils/cents) to avoid float precision bugs.  
**Impact:** All `amount` columns are `BIGINT UNSIGNED`. Display layer divides by 100.

---

## D-011 — Notification System
**Decision:** In-app notifications (database driver) + Email (Mailgun/SMTP).  
**Triggers:** Invoice overdue, package arrived, low stock alert, user login from new IP.  
**Implementation:** Laravel Notifications + Broadcast via Reverb (WebSocket) for real-time bell icon.
