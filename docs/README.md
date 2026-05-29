# SortLot — Used Clothing Package Management SaaS

> Company: Hamriyah Free Zone, Sharjah, UAE  
> Stack: Laravel 11 · MySQL 8 · Next.js 14 (App Router) · Docker · PHPUnit · Playwright · GitHub Actions

---

## 📁 Project Context Files (read these every session)

| File | Purpose |
|------|---------|
| `README.md` | This file — master index |
| `context/PROGRESS.md` | **Current phase, step, task — read first every session** |
| `context/DECISIONS.md` | Architecture decisions and rationale |
| `context/BLOCKERS.md` | Known blockers, questions, deferred items |
| `architecture/SYSTEM.md` | Full system architecture overview |
| `architecture/DATABASE.md` | Full database schema with relationships |
| `architecture/API.md` | API endpoint contracts |
| `architecture/VAT.md` | UAE VAT rules for this business |
| `architecture/ROLES.md` | Roles, permissions matrix |
| `phases/PHASE_1.md` | Phase 1: Foundation (Docker, Auth, RBAC) |
| `phases/PHASE_2.md` | Phase 2: Packages & Items |
| `phases/PHASE_3.md` | Phase 3: Customers, Suppliers, Invoicing |
| `phases/PHASE_4.md` | Phase 4: Dashboard & Charts |
| `phases/PHASE_5.md` | Phase 5: Preferences, Polish, CI/CD |

---

## 🏗️ Project Name

**SortLot** — internal working name. Rename in `.env` as needed.

---

## 📦 Repository Structure

```
sortlot/
├── backend/                 # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/
│   │   ├── Policies/
│   │   ├── Services/
│   │   └── Enums/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── tests/
│   │   ├── Feature/
│   │   └── Unit/
│   └── ...
├── frontend/                # Next.js 14 App Router
│   ├── app/
│   │   ├── (auth)/          # login, register
│   │   ├── (dashboard)/     # protected routes
│   │   │   ├── dashboard/
│   │   │   ├── packages/
│   │   │   ├── items/
│   │   │   ├── customers/
│   │   │   ├── suppliers/
│   │   │   ├── invoicing/
│   │   │   └── preferences/
│   │   └── ...
│   ├── components/
│   ├── lib/
│   └── ...
├── docker/
│   ├── nginx/
│   ├── php/
│   └── mysql/
├── docker-compose.yml
├── docker-compose.test.yml
└── .github/
    └── workflows/
        ├── backend-tests.yml
        ├── frontend-tests.yml
        └── deploy.yml
```

---

## 🔑 How to Resume Work Each Session

1. Open `context/PROGRESS.md` — find the current phase/step/task
2. Open the relevant `phases/PHASE_N.md` for detail
3. Read `context/BLOCKERS.md` for any pending decisions
4. Do the work, run the tests, check off the task
5. Update `context/PROGRESS.md` before ending the session

---

## 🐳 Local Dev Quick Start (once Phase 1 is done)

```bash
git clone <repo>
cd sortlot
cp backend/.env.example backend/.env
cp frontend/.env.local.example frontend/.env.local
docker-compose up -d
docker-compose exec backend php artisan migrate --seed
# Backend API: http://localhost:8000
# Frontend:    http://localhost:3000
# MySQL:       localhost:3306
```
