# PHASE 1 — Foundation

> Start here. Every other phase depends on this being solid.

---

## Step 1.1 — Repository & Docker Setup

### Tasks
- [ ] Create GitHub repo `sortlot` (private)
- [ ] Create root `.gitignore`
- [ ] Create `docker-compose.yml`:
  - Services: `nginx`, `php`, `mysql`, `redis`, `node`, `mailhog`, `minio`
  - Volumes: `mysql_data`, `redis_data`, `minio_data`
  - Network: `sortlot_network`
- [ ] Create `docker-compose.test.yml` (overrides: test DB, no minio)
- [ ] `docker/nginx/default.conf`:
  - `location /api/ → php:9000 (fastcgi)`
  - `location / → node:3000 (proxy_pass)`
- [ ] `docker/php/Dockerfile`:
  - Base: `php:8.3-fpm-alpine`
  - Extensions: pdo_mysql, redis, gd, zip, bcmath, pcntl
  - Composer install
- [ ] `docker/mysql/init.sql`: create `sortlot` and `sortlot_test` databases
- [ ] Laravel 11 install: `composer create-project laravel/laravel backend`
- [ ] Next.js install: `npx create-next-app@14 frontend --typescript --tailwind --app`
- [ ] `backend/.env.example` and `frontend/.env.local.example`

### Docker Compose skeleton
```yaml
services:
  nginx:
    image: nginx:alpine
    ports: ["80:80"]
    volumes: [./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf]
    depends_on: [php, node]

  php:
    build: ./docker/php
    volumes: [./backend:/var/www/html]
    environment:
      - APP_ENV=local
    depends_on: [mysql, redis]

  node:
    image: node:20-alpine
    working_dir: /app
    volumes: [./frontend:/app]
    command: sh -c "npm install && npm run dev"
    environment:
      - NEXT_PUBLIC_API_URL=http://localhost/api/v1

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: sortlot
    volumes: [mysql_data:/var/lib/mysql, ./docker/mysql/init.sql:/docker-entrypoint-initdb.d/init.sql]
    ports: ["3306:3306"]

  redis:
    image: redis:7-alpine
    ports: ["6379:6379"]

  mailhog:
    image: mailhog/mailhog
    ports: ["8025:8025"]

  minio:
    image: minio/minio
    command: server /data --console-address ":9001"
    environment:
      MINIO_ROOT_USER: minioadmin
      MINIO_ROOT_PASSWORD: minioadmin
    ports: ["9001:9001"]
    volumes: [minio_data:/data]
```

### Verification Test
- [ ] `docker-compose up -d` — all containers healthy
- [ ] `curl http://localhost/api/v1/health` returns 200
- [ ] `curl http://localhost` returns Next.js page

---

## Step 1.2 — Laravel Base Configuration

### Tasks
- [ ] `composer require laravel/sanctum spatie/laravel-permission`
- [ ] `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- [ ] `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- [ ] Configure `config/sanctum.php`: `stateful_domains = [localhost:3000]`
- [ ] Configure `config/cors.php`: `allowed_origins = [http://localhost:3000]`, `supports_credentials = true`
- [ ] Set `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis` in `.env`
- [ ] Install Laravel Horizon: `composer require laravel/horizon`
- [ ] `php artisan horizon:install`
- [ ] Add `APP_URL=http://localhost` to `.env`
- [ ] Register `EnsureFrontendRequestsAreStateful` middleware on `api` middleware group
- [ ] Health check route: `Route::get('/health', fn() => response()->json([...]))`
- [ ] `php artisan config:cache && php artisan route:cache`

### Tests (PHPUnit)
```php
// tests/Feature/HealthCheckTest.php
public function test_health_endpoint_returns_ok(): void
{
    $response = $this->getJson('/api/v1/health');
    $response->assertOk()->assertJsonStructure(['status','db','redis','queue']);
}
```

---

## Step 1.3 — Database: Auth + RBAC

### Tasks
- [ ] Migration: `users` table (extend default Laravel users with `phone`, `is_active`, `last_login_at`, `last_login_ip`, `deleted_at`)
- [ ] Run Spatie permission migrations
- [ ] `database/seeders/RoleSeeder.php`:
  ```php
  $roles = ['super_admin', 'manager', 'warehouse_staff', 'accountant', 'viewer'];
  ```
- [ ] `database/seeders/PermissionSeeder.php` — all permissions from ROLES.md
- [ ] `database/seeders/UserSeeder.php`:
  ```php
  User::create([
      'name' => 'Super Admin',
      'email' => 'admin@sortlot.local',
      'password' => bcrypt('password'),
  ])->assignRole('super_admin');
  ```
- [ ] `DatabaseSeeder` calls: `PermissionSeeder`, `RoleSeeder`, `UserSeeder`

### Tests
```php
// tests/Feature/Auth/RoleSeederTest.php
public function test_roles_and_permissions_seeded(): void
{
    $this->seed();
    $this->assertDatabaseHas('roles', ['name' => 'super_admin']);
    $this->assertDatabaseHas('permissions', ['name' => 'packages.create']);
    $admin = User::where('email', 'admin@sortlot.local')->first();
    $this->assertTrue($admin->hasRole('super_admin'));
}
```

---

## Step 1.4 — Auth API Endpoints

### Tasks
- [ ] `app/Http/Controllers/Api/AuthController.php`:
  - `login()`: validate, attempt, return Sanctum token + user + roles + permissions
  - `logout()`: revoke current token
  - `me()`: return authenticated user with roles and permissions array
  - `updatePassword()`: validate + update
- [ ] `app/Http/Resources/UserResource.php`
- [ ] Route group: `Route::prefix('auth')->group(...)`
- [ ] Rate limit: 5 login attempts/minute per IP

### Tests
```php
// tests/Feature/Auth/AuthTest.php
public function test_user_can_login(): void { ... }
public function test_wrong_password_returns_422(): void { ... }
public function test_rate_limit_on_login(): void { ... }
public function test_authenticated_user_can_get_me(): void { ... }
public function test_unauthenticated_request_returns_401(): void { ... }
public function test_user_can_logout(): void { ... }
```

---

## Step 1.5 — Next.js Auth Setup

### Tasks
- [ ] `npm install @tanstack/react-query axios zustand react-hook-form zod @hookform/resolvers`
- [ ] `npx shadcn@latest init` + add components: button, input, card, dialog, dropdown-menu, select, table, badge, skeleton, toast
- [ ] `lib/api.ts` — Axios instance with baseURL, withCredentials, CSRF handling:
  ```ts
  // Auto-fetch CSRF cookie from /sanctum/csrf-cookie before POST/PUT/PATCH/DELETE
  const api = axios.create({ baseURL: '/api/v1', withCredentials: true });
  ```
- [ ] `lib/auth.ts` — `useAuthStore` (Zustand): `{ user, permissions, login, logout, isLoading }`
- [ ] Middleware `middleware.ts`:
  ```ts
  // Redirect to /login if no auth cookie on protected routes
  // Redirect to /dashboard if already authed and hitting /login
  ```
- [ ] Page: `app/(auth)/login/page.tsx` — email/password form, error display
- [ ] Layout: `app/(dashboard)/layout.tsx` — sidebar + topbar
- [ ] Sidebar component: nav links with permission gating (`<Gate permission="packages.view">`)
- [ ] `<Gate>` component: renders children only if user has permission
- [ ] `app/(dashboard)/dashboard/page.tsx` — placeholder "Dashboard coming in Phase 4"

### Tests (Playwright)
```ts
// tests/e2e/auth/login.spec.ts
test('user can log in and see dashboard', async ({ page }) => { ... });
test('wrong password shows error', async ({ page }) => { ... });
test('unauthenticated redirect to login', async ({ page }) => { ... });
test('user can log out', async ({ page }) => { ... });
```

---

## Step 1.6 — CI/CD Pipeline

### `.github/workflows/backend-tests.yml`
```yaml
name: Backend Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env: { MYSQL_DATABASE: sortlot_test, MYSQL_ROOT_PASSWORD: secret }
        options: --health-cmd="mysqladmin ping" --health-interval=10s
      redis:
        image: redis:7-alpine
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3', extensions: pdo_mysql, redis, gd }
      - run: cd backend && composer install --no-interaction
      - run: cd backend && cp .env.example .env && php artisan key:generate
      - run: cd backend && php artisan migrate --env=testing --force
      - run: cd backend && php artisan test --parallel
```

### `.github/workflows/frontend-tests.yml`
```yaml
name: Frontend Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: '20' }
      - run: cd frontend && npm ci
      - run: cd frontend && npx playwright install --with-deps
      - run: cd frontend && npm run build
      - run: cd frontend && npx playwright test
```

### Branch Strategy
- `main` — production-ready, protected. Requires PR + both CI checks passing.
- `develop` — integration branch
- `feature/*` — feature branches, merge to develop
- `hotfix/*` — merge directly to main + back to develop

---

## Phase 1 CI/CD Gate

Before moving to Phase 2:
- [ ] `docker-compose up` — all services healthy
- [ ] `php artisan test` — 0 failures (HealthCheck, RoleSeeder, Auth tests)
- [ ] Playwright tests — login flow passes
- [ ] GitHub Actions — both workflows green on `develop` branch
- [ ] `php artisan migrate:fresh --seed` — completes without error
- [ ] Super admin user can log in on `http://localhost:3000`
