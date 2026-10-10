# CricBail: One-Command Code and Content Deployment

This directory contains the production deployment automation for the CricBail Laravel application ([https://cricbail.com](https://cricbail.com)).

The workflow allows you to push code changes to GitHub, create new articles, categories, and quizzes locally, and deploy everything to the live production server with a single command.

---

## Architecture Overview

```
 Local Machine (Dev)                          Production Server (Live)
┌───────────────────────────────┐            ┌────────────────────────────────┐
│ 1. Verify Git working tree    │            │ 5. Pull latest Git commit      │
│    (Must be clean & pushed)   │            │ 6. composer install --no-dev   │
│ 2. artisan content:export     │            │ 7. php artisan migrate --force │
│    - Categories, Articles     │            │ 8. php artisan content:import  │
│    - Quizzes, Questions       │   SSH/SCP  │    - Idempotent update         │
│    - Referenced Media Files   │ ─────────> │    - Protects visitor comments │
│ 3. Package .tar.gz bundle     │            │    - Protects quiz scores      │
│ 4. Pre-deploy validation      │            │ 9. php artisan storage:link    │
└───────────────────────────────┘            │ 10. Rebuild config/route caches│
                                             │ 11. Healthcheck (HTTP 200)     │
                                             └────────────────────────────────┘
```

---

## 1. First-Time Setup

### Step A: Configure Production SSH Credentials
1. Copy the example configuration template:
   ```bash
   cp scripts/deploy.env.example scripts/deploy.env
   ```
2. Open `scripts/deploy.env` and supply your live server connection details:
   ```ini
   # Production Server Host (IP or domain)
   DEPLOY_HOST="cricbail.com"

   # SSH Username to connect with (e.g., root, ubuntu, forge)
   DEPLOY_USER="root"

   # Switch to this application user on the server (via su - vamsidasarik)
   DEPLOY_EXEC_USER="vamsidasarik"

   # SSH Port (default: 22)
   DEPLOY_PORT="22"

   # Optional: Path to private SSH key (if not using default ~/.ssh/id_rsa or ssh-agent)
   DEPLOY_SSH_KEY="~/.ssh/id_ed25519"

   # Remote application path where CricBail is installed
   DEPLOY_REMOTE_PATH="/var/www/cricbail"

   # Git branch to pull on production
   DEPLOY_BRANCH="main"

   # Safety: Automatically create mysqldump before migrations & content import
   DEPLOY_BACKUP_DB="true"

   # Healthcheck URL to verify application availability
   DEPLOY_HEALTHCHECK_URL="https://cricbail.com"
   ```
> **Security Note:** `scripts/deploy.env` is automatically added to `.gitignore`. Never commit SSH private keys or server credentials to version control.

### Step B: Configure SSH Key Authentication
Ensure your local machine can connect to your server without an interactive password prompt:
```bash
ssh-copy-id -p 22 <DEPLOY_USER>@<DEPLOY_HOST>
# Or verify with:
ssh -p 22 <DEPLOY_USER>@<DEPLOY_HOST> "echo 'SSH Connection OK'"
```

---

## 2. Normal Everyday Deployment Workflow

You do NOT need to run any manual `git add`, `git commit`, or `git push` commands. The deployment script handles everything automatically:

```bash
bash scripts/deploy.sh
```

*(Optional: You can supply a custom commit message with `-m "Your message"`)*
```bash
bash scripts/deploy.sh -m "Added new IPL articles and updated layout"
```

The script will automatically:
1. **Detect all modified or new files** in your local project.
2. **Stage and commit** them automatically (`git add -A && git commit -m "..."`).
3. **Push to GitHub** (`git push origin main`).

The script will automatically:
- Verify that your working tree is clean and local commits are pushed to GitHub.
- Export all published articles, categories, quizzes, questions, and referenced images.
- Create a timestamped bundle archive (`storage/app/deployments/exports/cricbail_content_*.tar.gz`).
- Transfer the bundle to the production server via secure SCP.
- Put production into maintenance mode (`php artisan down`) with emergency auto-recovery.
- Create a pre-deployment database backup (`storage/app/deployments/backups/`).
- Pull the latest code (`git pull origin main`) and update Composer dependencies (`--no-dev`).
- Safely run any pending database migrations (`php artisan migrate --force`).
- Import the new content idempotently via `php artisan content:import-for-deploy`.
- Update storage links and rebuild configuration, route, and view caches.
- Bring the site online (`php artisan up`) and run an automated healthcheck against `https://cricbail.com`.

---

## 3. Command-Line Options & Flags

| Flag | Purpose |
|---|---|
| `--dry-run` | Simulates the export and import without making any changes on the production server. Ideal for testing content bundles locally. |
| `--skip-content` | Deploys code changes, runs migrations, and rebuilds caches without exporting or importing content. |
| `--skip-code` | Deploys and imports content and media only, skipping `git pull` and `composer install`. |
| `--force-import` | Forces re-updating of content records even if their attributes match the current database state. |
| `--no-backup` | Skips creating the pre-deployment database backup before migrating/importing. |
| `--config <path>` | Loads a specific configuration file instead of `scripts/deploy.env`. |
| `-h`, `--help` | Displays usage instructions and available options. |

### Examples:

- **Run a dry-run test**:
  ```bash
  bash scripts/deploy.sh --dry-run
  ```
- **Deploy only content (no code changes)**:
  ```bash
  bash scripts/deploy.sh --skip-code
  ```
- **Deploy only code and run migrations (no content bundle)**:
  ```bash
  bash scripts/deploy.sh --skip-content
  ```

---

## 4. Protection of Live Production Data

The import pipeline strictly enforces safety rules to protect operational data:
1. **Visitor Comments**: Existing comments submitted by live readers are never deleted or detached. Content updates match existing articles by `uuid` or `slug` and update records in place without altering primary keys.
2. **Quiz Attempts & Scores**: Live quiz completions and scoring records are completely protected.
3. **User Accounts**: Production passwords, authentication sessions, and admin credentials are never overwritten or copied from the local environment.
4. **No Destructive Database Commands**: The deployment never executes `migrate:fresh`, `db:wipe`, or `TRUNCATE`.
5. **Directory Traversal Prevention**: All media paths in the manifest are strictly validated to prevent relative path traversal (`..`).

---

## 5. Artisan Commands Reference

### Exporting Content Locally
```bash
# Export all published content and referenced media to default archive:
php artisan content:export-for-deploy

# Export to a custom path or format (.zip / .tar):
php artisan content:export-for-deploy --output=my_bundle.zip --format=zip

# Export including draft articles:
php artisan content:export-for-deploy --include-drafts
```

### Importing Content on Production
```bash
# Import a bundle archive:
php artisan content:import-for-deploy /path/to/cricbail_content_YYYYMMDD_HHMMSS.tar.gz

# Dry-run inspection (shows what would be created/updated without changing DB):
php artisan content:import-for-deploy /path/to/cricbail_content_*.tar.gz --dry-run

# Force update:
php artisan content:import-for-deploy /path/to/cricbail_content_*.tar.gz --force
```
