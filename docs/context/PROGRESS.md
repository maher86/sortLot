# PROGRESS — Agent State File

<!--
  ╔══════════════════════════════════════════════════════════════╗
  ║  AGENT: READ THIS FIRST. EVERY SESSION. NO EXCEPTIONS.      ║
  ║  This file is your memory. It tells you exactly what to do. ║
  ╚══════════════════════════════════════════════════════════════╝

  AUTONOMOUS WORKFLOW — every session:
  1. Read this file completely
  2. Find the first [ ] task in CURRENT PHASE
  3. Read the full phase file in docs/phases/PHASE_N.md
  4. git checkout -b task/phase-{N}-step-{X}-{Y}-{slug}
  5. Implement the task
  6. Run ALL test commands listed in TEST COMMANDS section
  7. If ANY test fails → fix before proceeding. Never push red.
  8. git add -A && git commit -m "task(phase-N/step-X.Y): description"
  9. git push origin task/phase-N-step-X-Y-slug
     → auto-pr.yml fires → PR opened to develop automatically
  10. Mark task [x] with today's date in this file
  11. Update CURRENT STATE table below
  12. git add docs/context/PROGRESS.md && git commit -m "chore: update progress"
  13. git push (updates the already-open PR)
  14. Move to next task on a NEW branch
-->

---

## ▶ CURRENT STATE

| Field | Value |
|-------|-------|
| **Phase** | 1 |
| **Step** | 1.5 |
| **Task** | Next.js Auth |
| **Branch pattern** | `task/phase-1-step-1-5-nextjs-auth` |
| **PR target** | `develop` |
| **Status** | ✅ Step 1.5 complete |
| **Last updated** | 2026-05-30 |
| **Blocked?** | No |

---

## 📋 PHASE 1 — Foundation

> Full detail: `docs/phases/PHASE_1.md`

### Step 1.1 — Repository & Docker Setup
Branch: `task/phase-1-step-1-1-docker-setup`
- [x] Root `.gitignore` (2026-05-30)
- [x] `docker-compose.yml` (2026-05-30)
- [x] `docker-compose.test.yml` (2026-05-30)
- [x] `docker-compose.prod.yml` (2026-05-30)
- [x] `docker/nginx/default.conf` (2026-05-30)
- [x] `docker/php/Dockerfile` (2026-05-30)
- [x] `docker/php/Dockerfile.prod` (2026-05-30)
- [x] `docker/mysql/init.sql` (2026-05-30)
- [x] Laravel 11: `composer create-project laravel/laravel backend` (2026-05-30)
- [x] Next.js 14: `npx create-next-app@14 frontend --typescript --tailwind --app` (2026-05-30)
- [x] `backend/.env.example` (2026-05-30)
- [x] `frontend/.env.local.example` (2026-05-30)
- [x] `backend/phpunit.xml` (2026-05-30)
- [x] **✅ TEST:** `docker-compose up -d` → all containers healthy (2026-05-30)
- [x] **✅ TEST:** `curl http://localhost/api/v1/health` → 200 (2026-05-30)

### Step 1.2 — Laravel Base Configuration
Branch: `task/phase-1-step-1-2-laravel-base-config`
- [x] `composer require laravel/sanctum spatie/laravel-permission laravel/horizon` (2026-05-30)
- [x] Publish Sanctum, Permission, Horizon configs (2026-05-30)
- [x] `config/cors.php` — allow `http://localhost:3000`, credentials: true (2026-05-30)
- [x] `config/sanctum.php` — stateful: `localhost:3000` (2026-05-30)
- [x] `.env.example` — Redis for cache/session/queue (2026-05-30)
- [x] Register `EnsureFrontendRequestsAreStateful` on `api` middleware group (2026-05-30)
- [x] `routes/api.php` — `GET /api/v1/health` endpoint (2026-05-30)
- [x] **✅ TEST:** `php artisan test --filter=HealthCheckTest` (2026-05-30)

### Step 1.3 — Auth + RBAC Migrations & Seeders
Branch: `task/phase-1-step-1-3-auth-rbac-migrations`
- [x] Migration: extend `users` (phone, is_active, last_login_at, last_login_ip, deleted_at) (2026-05-30)
- [x] Run Spatie permission migrations (2026-05-30)
- [x] `database/seeders/PermissionSeeder.php` — all permissions from `docs/architecture/ROLES.md` (2026-05-30)
- [x] `database/seeders/RoleSeeder.php` — 5 roles, assign permissions (2026-05-30)
- [x] `database/seeders/UserSeeder.php` — default super_admin (2026-05-30)
- [x] `DatabaseSeeder.php` calls all three (2026-05-30)
- [x] **✅ TEST:** `php artisan test --filter=RoleSeederTest` (2026-05-30)

### Step 1.4 — Auth API Endpoints
Branch: `task/phase-1-step-1-4-auth-api`
- [x] `app/Http/Controllers/Api/AuthController.php` (login, logout, me, updatePassword) (2026-05-30)
- [x] `app/Http/Resources/UserResource.php` (includes roles + permissions array) (2026-05-30)
- [x] `routes/api.php` — auth route group under `/api/v1/auth/` (2026-05-30)
- [x] Rate limit: 5 login attempts/min per IP (2026-05-30)
- [x] **✅ TEST:** `php artisan test --filter=AuthTest` — all scenarios green (2026-05-30)

### Step 1.5 — Next.js Auth
Branch: `task/phase-1-step-1-5-nextjs-auth`
- [x] `npm install @tanstack/react-query axios zustand react-hook-form zod @hookform/resolvers` (2026-05-30)
- [x] `npx shadcn@latest init` + add: button, input, card, dialog, dropdown-menu, select, table, badge, skeleton, sonner (2026-05-30)
- [x] `lib/api.ts` — Axios instance, withCredentials, auto CSRF fetch (2026-05-30)
- [x] `lib/stores/auth.ts` — Zustand: user, permissions, login(), logout() (2026-05-30)
- [x] `middleware.ts` — protect `/(dashboard)/*`, redirect `/login` if authed (2026-05-30)
- [x] `app/(auth)/login/page.tsx` (2026-05-30)
- [x] `app/(dashboard)/layout.tsx` — sidebar + topbar shell (2026-05-30)
- [x] `components/sidebar/Sidebar.tsx` — nav items with permission gating (2026-05-30)
- [x] `components/auth/Gate.tsx` — renders children only if user has permission (2026-05-30)
- [x] `app/(dashboard)/dashboard/page.tsx` — placeholder (2026-05-30)
- [x] **✅ TEST:** `npx playwright test --grep="auth"` (2026-05-30)

### Step 1.6 — CI/CD Pipeline Setup
Branch: `task/phase-1-step-1-6-cicd-pipeline`
- [ ] `.github/workflows/backend-tests.yml`
- [ ] `.github/workflows/frontend-tests.yml`
- [ ] `.github/workflows/deploy.yml`
- [ ] `.github/workflows/auto-pr.yml`
- [ ] `docs/GIT_WORKFLOW.md` — branch rules, PR checklist, secrets needed
- [ ] **✅ TEST:** push this branch → verify auto-pr.yml fires on GitHub → PR created → backend-tests.yml runs green

---

## 📋 PHASE 2 — Packages & Items
> Full detail: `docs/phases/PHASE_2.md`  
> Status: 🔒 Locked until Phase 1 CI gate passes

- [ ] Step 2.1 — Migrations & Models
- [ ] Step 2.2 — Package API + Tests
- [ ] Step 2.3 — Items API + Tests
- [ ] Step 2.4 — Preferences API (Pricing Tiers, Item Types)
- [ ] Step 2.5 — Frontend: Packages Module
- [ ] Step 2.6 — Frontend: Items Module
- [ ] Step 2.7 — Frontend: Preferences Module

---

## 📋 PHASE 3 — Customers, Suppliers & Invoicing
> Full detail: `docs/phases/PHASE_3.md`  
> Status: 🔒 Locked until Phase 2 CI gate passes

- [ ] Step 3.1 — Customers & Suppliers
- [ ] Step 3.2 — Invoice Core (Service, Models, Number generation)
- [ ] Step 3.3 — Invoice PDF Generation
- [ ] Step 3.4 — Overdue Scheduler
- [ ] Step 3.5 — Frontend: Customers & Suppliers
- [ ] Step 3.6 — Frontend: Invoicing Module

---

## 📋 PHASE 4 — Dashboard & Charts
> Full detail: `docs/phases/PHASE_4.md`  
> Status: 🔒 Locked until Phase 3 CI gate passes

- [ ] Step 4.1 — Backend: All Dashboard Endpoints (cached)
- [ ] Step 4.2 — Backend: Reports
- [ ] Step 4.3 — Frontend: Dashboard Page + Charts
- [ ] Step 4.4 — Frontend: Reports Page

---

## 📋 PHASE 5 — Polish & Production Deploy
> Full detail: `docs/phases/PHASE_5.md`  
> Status: 🔒 Locked until Phase 4 CI gate passes

- [ ] Step 5.1 — Notifications & Real-Time (Reverb)
- [ ] Step 5.2 — Two-Factor Authentication
- [ ] Step 5.3 — Audit Log UI
- [ ] Step 5.4 — Performance Hardening
- [ ] Step 5.5 — User Profile & Settings
- [ ] Step 5.6 — Error Handling & Monitoring (Sentry)
- [ ] Step 5.7 — Production Docker & Deploy
- [ ] Step 5.8 — Documentation

---

## ✅ COMPLETED

- Phase 1 / Step 1.1 — Repository & Docker Setup (2026-05-30)
- Phase 1 / Step 1.2 — Laravel Base Configuration (2026-05-30)
- Phase 1 / Step 1.3 — Auth + RBAC Migrations & Seeders (2026-05-30)
- Phase 1 / Step 1.4 — Auth API Endpoints (2026-05-30)
- Phase 1 / Step 1.5 — Next.js Auth (2026-05-30)

---

## 🧪 TEST COMMANDS

> Agent runs ALL of these after every task. Zero failures required before pushing.

### Backend
```bash
cd backend

# Full parallel suite (always run this)
php artisan test --parallel

# Specific filter (run during development)
php artisan test --filter=ClassName

# After migrations change
php artisan migrate:fresh --seed --env=testing

# Code style
./vendor/bin/pint --test
```

### Frontend
```bash
cd frontend

# Type check (must be 0 errors)
npm run type-check

# Linter (must be 0 errors)
npm run lint

# Unit tests
npm run test

# E2E (requires backend on :8000 and frontend on :3000)
npx playwright test

# Single spec
npx playwright test tests/e2e/auth/
```

### Docker integration check
```bash
# From repo root
docker-compose down -v
docker-compose up -d
sleep 15
docker-compose ps  # all "healthy" or "running"
curl -s http://localhost/api/v1/health | grep '"status":"ok"'
```

---

## 🌿 GIT WORKFLOW

```
main      ← production. Merge from develop after phase completes.
develop   ← integration. Accepts PRs from task/* branches.
task/*    ← one per task. Auto-PR → develop on push.
```

### Branch naming
```
task/phase-{N}-step-{X}-{Y}-{short-description}

task/phase-1-step-1-1-docker-setup
task/phase-1-step-1-4-auth-api
task/phase-2-step-2-2-package-api
task/phase-3-step-3-3-invoice-pdf
task/phase-4-step-4-3-dashboard-charts
```

### Commit message format
```
type(scope): description

feat(packages): add status transition validation
fix(auth): correct CSRF cookie handling for Sanctum
test(invoices): add VAT calculation edge cases
chore(progress): mark step 1.4 complete
```

### After each phase: develop → main
```bash
git checkout main
git merge --no-ff develop -m "release: phase-{N} complete"
git push origin main
# → deploy.yml fires → production deploy
```

---

## 📅 SESSION LOG

| Date | Session work | Branch pushed | PR # |
|------|-------------|---------------|------|
| — | Planning complete, all docs generated | — | — |
| 2026-05-30 | Phase 1 Step 1.1 Docker setup complete; Laravel 11, Next.js 14, Docker stack, health checks, and required tests green | task/phase-1-step-1-1-docker-setup | auto-pr pending |
| 2026-05-30 | Phase 1 Step 1.2 Laravel base config complete; Sanctum, Permission, Horizon, CORS, health checks, and required tests green | task/phase-1-step-1-2-laravel-base-config | auto-pr pending |
| 2026-05-30 | Phase 1 Step 1.3 auth/RBAC migrations and seeders complete; RoleSeederTest and required suites green | task/phase-1-step-1-3-auth-rbac-migrations | auto-pr pending |
| 2026-05-30 | Phase 1 Step 1.4 Auth API complete; login, logout, me, password update, rate limit, and required suites green | task/phase-1-step-1-4-auth-api | auto-pr pending |
| 2026-05-30 | Phase 1 Step 1.5 Next.js auth complete; login shell, auth store, protected routes, sidebar gating, and required suites green | task/phase-1-step-1-5-nextjs-auth | auto-pr pending |

> Agent: add a row here at the end of every session.
