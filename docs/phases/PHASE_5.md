# PHASE 5 — Polish, Non-Functional Features & Production Deploy

> Prerequisites: Phases 1–4 complete and tested.

---

## Step 5.1 — Notifications & Real-Time

### Tasks
- [ ] Install Laravel Reverb (WebSocket server): `composer require laravel/reverb`
- [ ] `php artisan reverb:install`
- [ ] Frontend: `npm install laravel-echo pusher-js`
- [ ] Notification types (Laravel Notification classes):
  - `InvoiceOverdueNotification` — to accountant + manager
  - `PackageArrivedNotification` — to warehouse_staff + manager
  - `PaymentReceivedNotification` — to accountant
  - `LowStockAlertNotification` — when `available` items in a category drop below threshold
  - `NewLoginNotification` — on login from new IP
  - `UserCreatedNotification` — to new user (welcome email)
- [ ] In-app notification bell (Topbar) — shows unread count badge
- [ ] Notification list dropdown — mark as read
- [ ] Email delivery via Mailgun (production) / Mailhog (local)
- [ ] Threshold preferences: "Low stock alert when available items < N" per season/gender

### Tests
- [ ] `tests/Feature/Notifications/InvoiceOverdueNotificationTest.php`
- [ ] `tests/Feature/Notifications/PackageArrivedNotificationTest.php`

---

## Step 5.2 — Two-Factor Authentication

### Tasks
- [ ] Install `pragmarx/google2fa-laravel`
- [ ] `POST /auth/2fa/enable` — generates QR code URI
- [ ] `POST /auth/2fa/confirm` — validates TOTP code, enables 2FA
- [ ] `POST /auth/2fa/disable`
- [ ] `POST /auth/2fa/verify` — called after login if 2FA enabled
- [ ] Login flow: login → if 2FA enabled → redirect to `/2fa-verify` page
- [ ] Frontend: 2FA setup page in profile settings

### Tests
- [ ] `tests/Feature/Auth/TwoFactorAuthTest.php`

---

## Step 5.3 — Audit Log UI

### Tasks
- [ ] Page: `/dashboard/audit-logs`
- [ ] Filterable by: user, action, model type, date range
- [ ] Shows: who, what, when, old → new values (JSON diff display)
- [ ] Only visible to `super_admin` and `manager`

---

## Step 5.4 — Performance Hardening

### Tasks
- [ ] Laravel Telescope (dev only): `composer require laravel/telescope --dev`
- [ ] Run all list endpoints through Debugbar — confirm no N+1
- [ ] Add missing composite indexes (review slow query log)
- [ ] `php artisan optimize` in Dockerfile CMD for production
- [ ] Implement `ResponseCache` on public GET endpoints
- [ ] Frontend bundle analysis: `next build --analyze` — eliminate large chunks
- [ ] Image optimization: lazy loading, next/image for logo/uploads
- [ ] Add `loading.tsx` skeleton files for all dashboard routes

---

## Step 5.5 — User Profile & Settings

### Tasks
- [ ] Page: `/dashboard/profile`
  - Update name, email, phone
  - Change password
  - 2FA toggle
  - Active sessions list (Sanctum tokens) + "Sign out all devices"
- [ ] User management page (super_admin): `/dashboard/users`
  - List, invite new user, assign roles, activate/deactivate

---

## Step 5.6 — Error Handling & Monitoring

### Tasks
- [ ] Install Sentry: `composer require sentry/sentry-laravel` + `npm install @sentry/nextjs`
- [ ] Custom Laravel exception handler — structured JSON errors
- [ ] Frontend error boundary components (React)
- [ ] 404 page, 500 page (Next.js `not-found.tsx`, `error.tsx`)
- [ ] Health check extended: disk space, queue backlog size

---

## Step 5.7 — Production Docker & Deploy

### Tasks
- [ ] `docker/php/Dockerfile.prod`:
  - Multi-stage build (composer install in builder stage)
  - `php artisan config:cache && route:cache && view:cache`
  - Non-root user
- [ ] `docker/node/Dockerfile.prod`:
  - Multi-stage: `npm run build` → serve static output
  - `NEXT_OUTPUT=standalone`
- [ ] `docker-compose.prod.yml`:
  - No Mailhog, no port exposure except 80/443
  - MySQL with strong passwords from env
  - Redis with password
  - Add `watchtower` for auto-updates (optional)
- [ ] `nginx/ssl.conf` — Let's Encrypt / Certbot config
- [ ] `.github/workflows/deploy.yml`:
  ```yaml
  on:
    push:
      branches: [main]
  jobs:
    deploy:
      # SSH to server, pull latest, docker-compose up --build -d, migrate
  ```
- [ ] Backup script: `cron` job, `mysqldump` → compress → upload to S3, keep 30 days

### Tests
- [ ] Production build smoke test: `docker-compose -f docker-compose.prod.yml up -d`
- [ ] `curl https://yourdomain.com/api/v1/health` returns 200

---

## Step 5.8 — Documentation

### Tasks
- [ ] `docs/DEPLOYMENT.md` — step-by-step production deployment guide
- [ ] `docs/LOCAL_SETUP.md` — onboarding a new developer
- [ ] `docs/USER_GUIDE.md` — basic user manual (how to sort a package, create an invoice)
- [ ] API auto-documentation: `php artisan scribe:generate` (install `knuckleswtf/scribe`)
- [ ] `README.md` in repo root: project overview, quick start, links to docs

---

## Phase 5 Final QA Checklist

### Functional
- [ ] Full package lifecycle: in_transit → shipped ✓
- [ ] Full invoice lifecycle: draft → paid ✓
- [ ] VAT calculated correctly for all 3 customer types ✓
- [ ] PDF invoice generated with all FTA fields ✓
- [ ] All roles tested — no privilege escalation ✓
- [ ] All chart date range filters work ✓
- [ ] Notifications delivered (in-app + email) ✓

### Non-Functional
- [ ] API p95 < 200ms (k6 load test: 50 concurrent users, 5 minutes)
- [ ] Dashboard cold load < 1.5s (Lighthouse)
- [ ] `php artisan test --parallel` — 0 failures
- [ ] Playwright full suite — 0 failures
- [ ] No critical/high Sentry errors in staging
- [ ] `npm audit` — 0 critical vulnerabilities
- [ ] `composer audit` — 0 critical vulnerabilities
- [ ] OWASP basic checklist: CSRF ✓, XSS headers ✓, SQL injection ✓, rate limiting ✓

### Compliance
- [ ] VAT report matches manual calculation ✓
- [ ] Audit log captures all financial mutations ✓
- [ ] Soft deletes — historical invoices readable after customer delete ✓
- [ ] Data retained for 5 years (backup policy confirmed) ✓
