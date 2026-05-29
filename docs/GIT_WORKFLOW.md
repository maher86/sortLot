# GIT WORKFLOW & GITHUB SETUP

---

## One-Time GitHub Setup

### 1. Create the repository on GitHub
Go to https://github.com/new
- Name: `sortlot`
- Visibility: Private
- **Do NOT** initialize with README (you'll push from local)

### 2. Create the two base branches locally then push
```bash
cd sortlot

# Init git
git init
git add .
git commit -m "chore: initial project scaffold and docs"

# Push main
git branch -M main
git remote add origin git@github.com:YOUR_USERNAME/sortlot.git
git push -u origin main

# Create develop from main
git checkout -b develop
git push -u origin develop
```

### 3. Set branch protection rules on GitHub
Go to: **Settings → Branches → Add branch protection rule**

For `main`:
- [x] Require a pull request before merging
- [x] Require status checks to pass: `Backend Tests / PHPUnit`, `Frontend Tests / Playwright`
- [x] Require branches to be up to date before merging
- [x] Do not allow bypassing the above settings

For `develop`:
- [x] Require a pull request before merging
- [x] Require status checks to pass: `Backend Tests / PHPUnit`, `Frontend Tests / Playwright`

### 4. Add GitHub Secrets
Go to: **Settings → Secrets and variables → Actions → New repository secret**

| Secret Name | Value | Used by |
|-------------|-------|---------|
| `DEPLOY_HOST` | your server IP | deploy.yml |
| `DEPLOY_USER` | ssh username | deploy.yml |
| `DEPLOY_SSH_KEY` | private SSH key | deploy.yml |
| `APP_URL` | https://yourdomain.com | deploy.yml |
| `SLACK_WEBHOOK_URL` | Slack webhook (optional) | deploy.yml |

---

## Daily Developer Workflow

### Starting a new task
```bash
# Always start from latest develop
git checkout develop
git pull origin develop

# Create task branch (follow naming convention exactly)
git checkout -b task/phase-1-step-1-1-docker-setup
```

### Working and committing
```bash
# Make changes...

# Run tests before every commit
cd backend && php artisan test --parallel
cd ../frontend && npm run type-check && npm run lint

# Commit (use conventional commits)
git add -A
git commit -m "feat(docker): add php-fpm dockerfile with redis extension"
```

### Finishing a task
```bash
# 1. Run full test suite one final time
cd backend && php artisan test --parallel
cd ../frontend && npm run type-check && npm run lint && npx playwright test

# 2. Mark task done in PROGRESS.md, update CURRENT STATE table
# 3. Commit the progress update
git add docs/context/PROGRESS.md
git commit -m "chore: mark task phase-1/step-1.1 complete"

# 4. Push → auto-pr.yml fires → PR auto-opened to develop
git push origin task/phase-1-step-1-1-docker-setup

# 5. GitHub will show the auto-created PR link in the push output
```

### After PR is reviewed and merged to develop
```bash
# Clean up local branch
git checkout develop
git pull origin develop
git branch -d task/phase-1-step-1-1-docker-setup
```

### After a full phase is done (develop → main)
```bash
git checkout main
git pull origin main
git merge --no-ff develop -m "release: phase-1 complete — foundation"
git push origin main
# → deploy.yml triggers → production deploy begins
```

---

## Codex Agent Workflow

When using OpenAI Codex or Claude as the coding agent:

1. **Give Codex access to the repo** via its GitHub integration
2. **Start each session with:** "Read `docs/context/PROGRESS.md` and continue from where you left off"
3. Codex reads PROGRESS.md → finds first unchecked task → creates the branch → implements → runs tests → pushes
4. You review the auto-created PR on GitHub
5. Merge the PR → move to next task

### Codex task prompt template
```
Read docs/context/PROGRESS.md.
Find the first unchecked [ ] task in the current phase/step.
Create the correct branch (follow the naming convention in PROGRESS.md).
Implement the task fully as described in the corresponding phase file.
Run all tests from the TEST COMMANDS section.
Fix any failures before pushing.
Update PROGRESS.md to check off completed tasks.
Push the branch — the PR will be created automatically.
```

---

## Local Machine Setup (one-time)

```bash
# 1. Clone
git clone git@github.com:YOUR_USERNAME/sortlot.git
cd sortlot

# 2. Copy env files
cp backend/.env.example backend/.env
cp frontend/.env.local.example frontend/.env.local

# 3. Start Docker
docker-compose up -d

# 4. Wait for containers to be healthy (~30s first time)
docker-compose ps

# 5. Install backend deps + setup
docker-compose exec php composer install
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan migrate --seed

# 6. Create MinIO bucket
# Visit http://localhost:9001 → login minioadmin/minioadmin123
# Create bucket named "sortlot"

# 7. Visit frontend
# http://localhost → SortLot app
# http://localhost:8025 → Mailhog (email testing)
# http://localhost:9001 → MinIO console

# 8. Default login
# Email: admin@sortlot.local
# Password: password
```
