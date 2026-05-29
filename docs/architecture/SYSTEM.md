# SYSTEM ARCHITECTURE

---

## Overview

SortLot is a monolithic-API + SPA architecture. Laravel serves a pure JSON API. Next.js is the frontend. They are separate Docker services that communicate over HTTP.

```
┌─────────────────────────────────────────────────────────────┐
│  Browser / Client                                           │
│  Next.js 14 (App Router, SSR + RSC)                        │
│  Port 3000                                                  │
└───────────────────────┬─────────────────────────────────────┘
                        │ HTTPS / Cookie (Sanctum SPA)
┌───────────────────────▼─────────────────────────────────────┐
│  Nginx Reverse Proxy                                        │
│  Port 80/443                                                │
│  /api/* → Laravel   /* → Next.js                           │
└───────────┬───────────────────────────────────────────────  ┘
            │
┌───────────▼───────────────────────────────────────────────  ┐
│  Laravel 11 API                                             │
│  PHP 8.3, php-fpm, Port 9000 (internal)                    │
│  Sanctum · Spatie Permission · Horizon                      │
└───────┬───────────┬───────────┬───────────────────────────  ┘
        │           │           │
   ┌────▼───┐  ┌────▼───┐  ┌───▼────┐
   │ MySQL  │  │ Redis  │  │ S3/    │
   │  8.0   │  │        │  │ MinIO  │
   │ Port   │  │ Cache  │  │ Files  │
   │  3306  │  │ Queue  │  │ (PDFs) │
   └────────┘  │ Session│  └────────┘
               └────────┘
```

---

## Non-Functional Requirements

### Performance Targets
| Metric | Target |
|--------|--------|
| API p95 response time | < 200ms |
| Dashboard load (cold) | < 1.5s |
| Dashboard load (warm cache) | < 300ms |
| Concurrent users | 50+ without degradation |
| PDF invoice generation | < 3s (async queued) |

### Performance Implementation
- **Indexes:** Every foreign key, every `status` column, every `created_at` used in range queries. Composite indexes on (status, created_at), (package_id, status).
- **N+1 prevention:** All Eloquent relationships eager-loaded via `with()`. API Resource classes enforce this.
- **Dashboard caching:** All chart aggregation queries cached in Redis with 5-minute TTL, tagged cache keys for targeted invalidation.
- **Cursor pagination:** `items` and `audit_logs` use cursor-based pagination (not offset) for large datasets.
- **Database connection pooling:** PgBouncer-style via PHP-FPM process management. Max 20 connections.
- **Queue workers:** Heavy jobs (PDF, email, reports) offloaded to Redis queue via Laravel Horizon.
- **Frontend:** TanStack Query for client cache. SWR pattern. Skeleton loaders, not spinners.

### Security
- CSRF protection via Sanctum cookies
- Rate limiting: Login 5/min, API 60/min per user, 1000/min global
- SQL injection: Eloquent parameterized queries only. Raw queries forbidden.
- XSS: Response headers `Content-Security-Policy`, `X-Frame-Options`
- Audit log on all financial record mutations
- Passwords: bcrypt, min 8 chars, complexity enforced
- 2FA: TOTP (Google Authenticator) — Phase 5

### Reliability
- DB: Daily automated backups to S3, 30-day retention
- Health check endpoint: `GET /api/v1/health` (checks DB, Redis, queue)
- Error tracking: Sentry integration (Phase 5)
- Zero-downtime deploy: Rolling restart via Docker

---

## Service Ports (Docker)

| Service | Internal | External |
|---------|----------|----------|
| nginx | 80 | 80 |
| php-fpm | 9000 | — |
| next.js | 3000 | 3000 |
| mysql | 3306 | 3306 |
| redis | 6379 | 6379 |
| mailhog | 1025/8025 | 8025 |
| minio | 9000/9001 | 9001 |
| horizon (laravel) | — | — |

---

## Module Dependency Map

```
Preferences  ←── used by ─── Packages, Items, Invoicing
Users/RBAC   ←── used by ─── All modules
Suppliers    ←── used by ─── Purchase Orders
Customers    ←── used by ─── Sales Orders
Packages     ←── parent of ─ Items
Items        ←── referenced by ─ Invoice Lines
Invoices     ←── has ───────── Payments
Payments     ─── triggers ──── Invoice status update
```

---

## API Design Principles

- All routes under `/api/v1/`
- JSON:API-inspired response envelope:
  ```json
  { "data": {...}, "meta": {...}, "links": {...} }
  { "errors": [{"field": "name", "message": "Required"}] }
  ```
- HTTP verbs strictly: GET=read, POST=create, PUT=replace, PATCH=partial update, DELETE=soft delete
- All list endpoints support: `?page=`, `?per_page=`, `?sort=`, `?filter[status]=`, `?search=`
- All timestamps in ISO 8601 UTC
- All amounts in fils (integer), labeled as `amount_fils`, display layer converts

---

## File Storage

- Invoice PDFs → MinIO (local) / S3 (production)
- Company logo → same
- Import spreadsheets (future) → temp storage, processed by queue, then deleted
- Path pattern: `invoices/{year}/{month}/{invoice_number}.pdf`
