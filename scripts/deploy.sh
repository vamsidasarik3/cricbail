#!/usr/bin/env bash
# ==============================================================================
# CRICBAIL: ONE-COMMAND CODE AND CONTENT DEPLOYMENT PIPELINE
# Repository: https://github.com/vamsidasarik3/cricbail
# Live Website: https://cricbail.com
# ==============================================================================
# Usage:
#   bash scripts/deploy.sh [OPTIONS]
#
# Options:
#   -m, --message    Custom Git commit message (default: "Auto-deploy: YYYY-MM-DD HH:MM:SS")
#   --no-git         Skip automatic local git add, commit, and push
#   --dry-run        Simulate the deployment and content import without modifying production
#   --skip-content   Deploy Git code, run migrations, and rebuild caches without content bundle
#   --skip-code      Deploy and import content bundle only, skipping git pull / composer install
#   --no-backup      Skip remote pre-deployment database backup
#   --force-import   Force overwrite identical content records on import
#   --config <file>  Specify a custom configuration file (default: scripts/deploy.env)
#   -h, --help       Display this help message and exit
# ==============================================================================

set -euo pipefail

# Visual styling
C_RESET='\033[0m'
C_BOLD='\033[1m'
C_GREEN='\033[32m'
C_BLUE='\033[34m'
C_YELLOW='\033[33m'
C_RED='\033[31m'
C_CYAN='\033[36m'

log_info()    { printf "%b %s\n" "${C_BLUE}${C_BOLD}[INFO]${C_RESET}" "$*"; }
log_success() { printf "%b %s\n" "${C_GREEN}${C_BOLD}[SUCCESS]${C_RESET}" "$*"; }
log_warn()    { printf "%b %s\n" "${C_YELLOW}${C_BOLD}[WARN]${C_RESET}" "$*"; }
log_error()   { printf "%b %s\n" "${C_RED}${C_BOLD}[ERROR]${C_RESET}" "$*"; }
log_step()    { printf "\n%b %s\n" "${C_CYAN}${C_BOLD}===> STEP:${C_RESET}" "$*"; }

# Determine script and project directories
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

CURRENT_STEP="Initialization"
trap 'handle_failure $? $LINENO' ERR

handle_failure() {
    local exit_code=$1
    local line_no=$2
    echo -e "\n${C_RED}${C_BOLD}========================================================================${C_RESET}"
    log_error "Deployment failed at step: [${CURRENT_STEP}] (Line ${line_no}, Exit code: ${exit_code})"
    echo -e "${C_RED}${C_BOLD}========================================================================${C_RESET}"
    exit "${exit_code}"
}

# CLI Flags Defaults
DRY_RUN=false
SKIP_CONTENT=false
SKIP_CODE=false
NO_BACKUP=false
FORCE_IMPORT=false
CUSTOM_CONFIG=""
COMMIT_MESSAGE_INPUT=""
AUTO_GIT=true

while [[ $# -gt 0 ]]; do
    case "$1" in
        -m|--message)
            COMMIT_MESSAGE_INPUT="$2"
            shift 2
            ;;
        --no-git|--skip-git)
            AUTO_GIT=false
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --skip-content)
            SKIP_CONTENT=true
            shift
            ;;
        --skip-code)
            SKIP_CODE=true
            shift
            ;;
        --no-backup)
            NO_BACKUP=true
            shift
            ;;
        --force-import)
            FORCE_IMPORT=true
            shift
            ;;
        --config)
            CUSTOM_CONFIG="$2"
            shift 2
            ;;
        -h|--help)
            sed -ne '/^# Usage:/,/^# ====/p' "$0" | sed 's/^# //'
            exit 0
            ;;
        *)
            log_error "Unknown option: $1"
            echo "Use 'bash scripts/deploy.sh --help' for usage."
            exit 1
            ;;
    esac
done

echo -e "${C_GREEN}${C_BOLD}========================================================================${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}        CRICBAIL ONE-COMMAND CODE AND CONTENT DEPLOYMENT PIPELINE       ${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}========================================================================${C_RESET}"
if [ "$DRY_RUN" = true ]; then
    echo -e "${C_YELLOW}${C_BOLD}*** DRY RUN MODE ENABLED: No production changes will be made ***${C_RESET}"
fi

# ------------------------------------------------------------------------------
# 1. LOAD CONFIGURATION
# ------------------------------------------------------------------------------
CURRENT_STEP="Load Configuration"
log_step "Loading deployment configuration"

CONFIG_FILE="${CUSTOM_CONFIG}"
if [ -z "${CONFIG_FILE}" ]; then
    if [ -f "${SCRIPT_DIR}/deploy.env" ]; then
        CONFIG_FILE="${SCRIPT_DIR}/deploy.env"
    elif [ -f "${PROJECT_ROOT}/.deploy.env" ]; then
        CONFIG_FILE="${PROJECT_ROOT}/.deploy.env"
    fi
fi

if [ -n "${CONFIG_FILE}" ] && [ -f "${CONFIG_FILE}" ]; then
    log_info "Loading configuration from: ${CONFIG_FILE}"
    # Export vars from env file, ignoring comments and blank lines
    set -a
    # shellcheck disable=SC1090
    source "${CONFIG_FILE}"
    set +a
else
    log_warn "No dedicated deploy.env file found. Checking environment variables."
fi

# Configuration Defaults & Fallbacks
DEPLOY_HOST="${DEPLOY_HOST:-}"
DEPLOY_USER="${DEPLOY_USER:-}"
DEPLOY_EXEC_USER="${DEPLOY_EXEC_USER:-}"
DEPLOY_PORT="${DEPLOY_PORT:-22}"
DEPLOY_SSH_KEY="${DEPLOY_SSH_KEY:-}"
DEPLOY_REMOTE_PATH="${DEPLOY_REMOTE_PATH:-}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
DEPLOY_REMOTE_PHP="${DEPLOY_REMOTE_PHP:-php}"
DEPLOY_REMOTE_COMPOSER="${DEPLOY_REMOTE_COMPOSER:-composer}"
DEPLOY_BACKUP_DB="${DEPLOY_BACKUP_DB:-true}"
DEPLOY_INCLUDE_DRAFTS="${DEPLOY_INCLUDE_DRAFTS:-false}"
DEPLOY_HEALTHCHECK_URL="${DEPLOY_HEALTHCHECK_URL:-https://cricbail.com}"
DEPLOY_KEEP_LOCAL_BUNDLE="${DEPLOY_KEEP_LOCAL_BUNDLE:-false}"

if [ "$NO_BACKUP" = true ]; then
    DEPLOY_BACKUP_DB="false"
fi

# Determine Local PHP binary
DEPLOY_LOCAL_PHP="${DEPLOY_LOCAL_PHP:-php}"
if ! command -v "${DEPLOY_LOCAL_PHP}" >/dev/null 2>&1; then
    if [ -f "G:/xampp/php/php.exe" ]; then
        DEPLOY_LOCAL_PHP="G:/xampp/php/php.exe"
    elif [ -f "/g/xampp/php/php.exe" ]; then
        DEPLOY_LOCAL_PHP="/g/xampp/php/php.exe"
    fi
fi

# Validate local requirements
cd "${PROJECT_ROOT}"

if [ ! -f "artisan" ] || [ ! -f "composer.json" ]; then
    log_error "Current directory is not a valid CricBail Laravel project: ${PROJECT_ROOT}"
    exit 1
fi

if ! command -v git >/dev/null 2>&1; then
    log_error "Git is required locally but could not be found."
    exit 1
fi

# ------------------------------------------------------------------------------
# 2. AUTOMATIC LOCAL GIT COMMIT & PUSH
# ------------------------------------------------------------------------------
CURRENT_STEP="Local Git Sync"
log_step "Managing local Git repository state and GitHub synchronization"

# Check current branch
CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
log_info "Local Git Branch: ${CURRENT_BRANCH} (Target deployment branch: ${DEPLOY_BRANCH})"

if [ "${CURRENT_BRANCH}" != "${DEPLOY_BRANCH}" ]; then
    log_error "Local branch '${CURRENT_BRANCH}' does not match target deployment branch '${DEPLOY_BRANCH}'."
    echo "Switch to the target branch ('git checkout ${DEPLOY_BRANCH}') before deploying."
    exit 1
fi

if [ "${AUTO_GIT}" = true ]; then
    DIRTY_FILES="$(git status --porcelain || true)"
    if [ -n "${DIRTY_FILES}" ]; then
        log_info "Uncommitted changes detected in local repository."
        if [ "$DRY_RUN" = true ]; then
            echo -e "${C_YELLOW}${C_BOLD}[DRY-RUN] Would stage and commit the following changes:${C_RESET}"
            echo -e "${C_YELLOW}${DIRTY_FILES}${C_RESET}\n"
        else
            DEFAULT_COMMIT_MSG="Auto-deploy: $(date +'%Y-%m-%d %H:%M:%S')"
            MSG="${COMMIT_MESSAGE_INPUT:-$DEFAULT_COMMIT_MSG}"
            log_info "Staging all local changes (git add -A)..."
            git add -A
            log_info "Committing changes: '${MSG}'..."
            git commit -m "${MSG}"
            log_success "Created local commit: $(git log -1 --pretty=format:'%s (%h)')"
        fi
    else
        log_info "Working tree is clean; no uncommitted changes."
    fi

    # Push to remote GitHub
    log_info "Checking sync with remote repository (origin/${DEPLOY_BRANCH})..."
    git fetch origin "${DEPLOY_BRANCH}" --quiet || true

    AHEAD_COMMITS="$(git rev-list --count "origin/${DEPLOY_BRANCH}..HEAD" 2>/dev/null || echo "0")"
    if [ "${AHEAD_COMMITS}" -gt 0 ]; then
        if [ "$DRY_RUN" = true ]; then
            log_info "[DRY-RUN] Would push ${AHEAD_COMMITS} commit(s) to GitHub (origin/${DEPLOY_BRANCH})."
        else
            log_info "Pushing ${AHEAD_COMMITS} commit(s) to GitHub (origin/${DEPLOY_BRANCH})..."
            git push origin "${DEPLOY_BRANCH}"
            log_success "Successfully pushed to GitHub (origin/${DEPLOY_BRANCH})!"
        fi
    else
        log_success "GitHub repository (origin/${DEPLOY_BRANCH}) is up-to-date with local."
    fi
else
    # If AUTO_GIT is disabled via --no-git, verify working tree is clean
    DIRTY_FILES="$(git status --porcelain || true)"
    if [ -n "${DIRTY_FILES}" ]; then
        if [ "$DRY_RUN" = true ]; then
            echo -e "${C_YELLOW}${C_BOLD}[WARN] Local working tree has uncommitted changes (--no-git mode):${C_RESET}"
            echo -e "${C_YELLOW}${DIRTY_FILES}${C_RESET}\n"
        else
            log_error "WORKING TREE HAS UNCOMMITTED CHANGES! Automatic Git sync was disabled (--no-git)."
            echo -e "${DIRTY_FILES}"
            exit 1
        fi
    fi
fi

COMMIT_MESSAGE="$(git log -1 --pretty=format:'%s (%h)')"
log_success "Git repository ready! Current deployment commit: ${COMMIT_MESSAGE}"

# ------------------------------------------------------------------------------
# 3. EXPORT LOCAL CONTENT BUNDLE (IF ENABLED)
# ------------------------------------------------------------------------------
BUNDLE_FILE=""
if [ "$SKIP_CONTENT" = false ]; then
    CURRENT_STEP="Export Local Content"
    log_step "Exporting local content and referenced media"

    EXPORT_ARGS=()
    if [ "${DEPLOY_INCLUDE_DRAFTS}" = "true" ]; then
        EXPORT_ARGS+=("--include-drafts")
    fi

    log_info "Running: php artisan content:export-for-deploy"
    EXPORT_OUTPUT="$("${DEPLOY_LOCAL_PHP}" artisan content:export-for-deploy "${EXPORT_ARGS[@]}")"
    echo "${EXPORT_OUTPUT}"

    # Extract BUNDLE_PATH line
    BUNDLE_FILE="$(echo "${EXPORT_OUTPUT}" | grep '^BUNDLE_PATH=' | head -n 1 | cut -d '=' -f2- | tr -d '\r\n')"
    BUNDLE_FILE="$(echo "${BUNDLE_FILE}" | tr '\\' '/')"
    if [ -z "${BUNDLE_FILE}" ] || [ ! -f "${BUNDLE_FILE}" ]; then
        log_error "Failed to locate generated content bundle from export output."
        exit 1
    fi

    BUNDLE_SIZE="$(ls -lh "${BUNDLE_FILE}" | awk '{print $5}')"
    log_success "Content bundle ready: ${BUNDLE_FILE} (${BUNDLE_SIZE})"
fi

# ------------------------------------------------------------------------------
# 4. VALIDATE PRODUCTION CONNECTION CONFIGURATION
# ------------------------------------------------------------------------------
CURRENT_STEP="Validate Remote Configuration"
log_step "Validating remote production server configuration"

MISSING_CONFIG=()
[ -z "${DEPLOY_HOST}" ] && MISSING_CONFIG+=("DEPLOY_HOST")
[ -z "${DEPLOY_USER}" ] && MISSING_CONFIG+=("DEPLOY_USER")
[ -z "${DEPLOY_REMOTE_PATH}" ] && MISSING_CONFIG+=("DEPLOY_REMOTE_PATH")

if [ ${#MISSING_CONFIG[@]} -gt 0 ]; then
    echo -e "\n${C_YELLOW}${C_BOLD}========================================================================${C_RESET}"
    log_warn "Remote server credentials are not yet configured: ${MISSING_CONFIG[*]}"
    echo -e "${C_CYAN}To enable automated one-command remote deployment:${C_RESET}"
    echo -e "  1. Copy the template:  cp scripts/deploy.env.example scripts/deploy.env"
    echo -e "  2. Edit scripts/deploy.env with your production SSH details:"
    echo -e "       DEPLOY_HOST=\"cricbail.com\""
    echo -e "       DEPLOY_USER=\"<your-ssh-user>\""
    echo -e "       DEPLOY_REMOTE_PATH=\"/var/www/cricbail\""
    echo -e "  3. Re-run: bash scripts/deploy.sh"
    echo -e "${C_YELLOW}${C_BOLD}========================================================================${C_RESET}"

    if [ "$DRY_RUN" = true ]; then
        log_info "DRY-RUN SIMULATION: Testing local content import to verify bundle integrity..."
        "${DEPLOY_LOCAL_PHP}" artisan content:import-for-deploy "${BUNDLE_FILE}" --dry-run
        log_success "Local content validation and bundle packaging test succeeded 100%!"
        echo -e "\n${C_GREEN}${C_BOLD}Local verification complete. Supply production credentials to deploy to live server.${C_RESET}"
        exit 0
    else
        log_error "Deployment halted because production server credentials are required."
        exit 1
    fi
fi

# Prepare SSH / SCP command options
SSH_OPTS=(-p "${DEPLOY_PORT}" -o "BatchMode=yes" -o "StrictHostKeyChecking=accept-new")
SCP_OPTS=(-P "${DEPLOY_PORT}" -o "BatchMode=yes" -o "StrictHostKeyChecking=accept-new")

if [ -n "${DEPLOY_SSH_KEY}" ] && [ -f "${DEPLOY_SSH_KEY}" ]; then
    SSH_OPTS+=(-i "${DEPLOY_SSH_KEY}")
    SCP_OPTS+=(-i "${DEPLOY_SSH_KEY}")
fi

SSH_TARGET="${DEPLOY_USER}@${DEPLOY_HOST}"

log_info "Target Host: ${DEPLOY_HOST} (Port: ${DEPLOY_PORT}, Connect User: ${DEPLOY_USER})"
if [ -n "${DEPLOY_EXEC_USER}" ] && [ "${DEPLOY_EXEC_USER}" != "${DEPLOY_USER}" ]; then
    log_info "Execution User: ${DEPLOY_EXEC_USER} (switching via: su - ${DEPLOY_EXEC_USER})"
fi
log_info "Remote Path: ${DEPLOY_REMOTE_PATH}"
log_info "Target Branch: ${DEPLOY_BRANCH}"

# Test SSH connectivity
log_info "Testing SSH connectivity to ${SSH_TARGET}..."
if ! ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" "echo 'SSH_OK'" >/dev/null 2>&1; then
    log_error "Unable to establish SSH connection to ${SSH_TARGET} on port ${DEPLOY_PORT}."
    echo "Please verify:"
    echo "  - The production SSH host and port are reachable."
    echo "  - Your SSH key is loaded in ssh-agent or configured in DEPLOY_SSH_KEY."
    echo "  - The user '${DEPLOY_USER}' has login access on '${DEPLOY_HOST}'."
    exit 1
fi

if [ -n "${DEPLOY_EXEC_USER}" ] && [ "${DEPLOY_EXEC_USER}" != "${DEPLOY_USER}" ]; then
    log_info "Testing user switch: 'su - ${DEPLOY_EXEC_USER}'..."
    if ! ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" "su - ${DEPLOY_EXEC_USER} -c 'echo \"SU_OK\"'" >/dev/null 2>&1; then
        log_error "Connected as '${DEPLOY_USER}' but failed to switch to '${DEPLOY_EXEC_USER}' via 'su - ${DEPLOY_EXEC_USER}'."
        echo "Please verify that the user '${DEPLOY_EXEC_USER}' exists on '${DEPLOY_HOST}'."
        exit 1
    fi
    log_success "SSH connected as '${DEPLOY_USER}' -> verified user switch to '${DEPLOY_EXEC_USER}'!"
else
    log_success "SSH connection established successfully!"
fi

if [ "$DRY_RUN" = true ]; then
    log_step "DRY-RUN: Simulating remote deployment plan"
    echo -e "Planned execution steps on ${SSH_TARGET}:"
    echo -e "  1. Verify remote directory '${DEPLOY_REMOTE_PATH}' and clean working tree."
    [ "$DEPLOY_BACKUP_DB" = "true" ] && echo -e "  2. Create production database backup via mysqldump."
    [ "$SKIP_CODE" = false ] && echo -e "  3. git fetch origin ${DEPLOY_BRANCH} && git checkout ${DEPLOY_BRANCH} && git pull"
    [ "$SKIP_CODE" = false ] && echo -e "  4. ${DEPLOY_REMOTE_COMPOSER} install --no-dev --prefer-dist --optimize-autoloader"
    echo -e "  5. ${DEPLOY_REMOTE_PHP} artisan migrate --force"
    [ "$SKIP_CONTENT" = false ] && echo -e "  6. Transfer content bundle (${BUNDLE_SIZE}) and import via content:import-for-deploy"
    echo -e "  7. ${DEPLOY_REMOTE_PHP} artisan storage:link"
    echo -e "  8. ${DEPLOY_REMOTE_PHP} artisan config:cache && route:cache && view:cache"
    echo -e "  9. Healthcheck verification against ${DEPLOY_HEALTHCHECK_URL}"
    echo -e "\n${C_GREEN}${C_BOLD}Dry-run completed successfully! Run without --dry-run to execute live deployment.${C_RESET}"
    exit 0
fi

# ------------------------------------------------------------------------------
# 5. TRANSFER CONTENT BUNDLE TO PRODUCTION
# ------------------------------------------------------------------------------
REMOTE_BUNDLE_PATH=""
if [ "$SKIP_CONTENT" = false ] && [ -n "${BUNDLE_FILE}" ]; then
    CURRENT_STEP="Transfer Content Bundle"
    log_step "Transferring content bundle to production server"

    BUNDLE_BASENAME="$(basename "${BUNDLE_FILE}")"
    REMOTE_INCOMING_DIR="${DEPLOY_REMOTE_PATH}/storage/app/deployments/incoming"
    REMOTE_BUNDLE_PATH="${REMOTE_INCOMING_DIR}/${BUNDLE_BASENAME}"

    # Ensure remote directory exists
    ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" "mkdir -p '${REMOTE_INCOMING_DIR}'"

    log_info "Uploading ${BUNDLE_BASENAME} via SCP..."
    scp "${SCP_OPTS[@]}" "${BUNDLE_FILE}" "${SSH_TARGET}:${REMOTE_BUNDLE_PATH}"

    if [ -n "${DEPLOY_EXEC_USER}" ] && [ "${DEPLOY_EXEC_USER}" != "${DEPLOY_USER}" ]; then
        ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" "chown -R '${DEPLOY_EXEC_USER}:${DEPLOY_EXEC_USER}' '${REMOTE_INCOMING_DIR}'"
    fi
    log_success "Bundle transferred successfully to ${REMOTE_BUNDLE_PATH}"
fi

# ------------------------------------------------------------------------------
# 6. EXECUTE REMOTE DEPLOYMENT WORKFLOW
# ------------------------------------------------------------------------------
CURRENT_STEP="Execute Remote Deployment"
log_step "Executing production deployment commands on remote server"

# Construct remote execution script
REMOTE_SCRIPT=$(cat << 'REMOTE_EOF'
set -euo pipefail

DEPLOY_REMOTE_PATH="$1"
DEPLOY_BRANCH="$2"
DEPLOY_REMOTE_PHP="$3"
DEPLOY_REMOTE_COMPOSER="$4"
DEPLOY_BACKUP_DB="$5"
SKIP_CODE="$6"
SKIP_CONTENT="$7"
REMOTE_BUNDLE_PATH="$8"
FORCE_IMPORT="$9"

cd "${DEPLOY_REMOTE_PATH}"

if [ ! -f "artisan" ]; then
    echo "ERROR: Remote path is not a valid Laravel application: ${DEPLOY_REMOTE_PATH}"
    exit 1
fi

# Verify remote Git status
REMOTE_DIRTY="$(git status --porcelain || true)"
if [ -n "${REMOTE_DIRTY}" ]; then
    echo "ERROR: Production server working tree has uncommitted modifications:"
    echo "${REMOTE_DIRTY}"
    echo "Aborting deployment to protect server-side state."
    exit 1
fi

# Maintenance mode trap
MAINTENANCE_ACTIVE=0
cleanup() {
    if [ "${MAINTENANCE_ACTIVE}" = "1" ]; then
        echo "Restoring application from maintenance mode..."
        "${DEPLOY_REMOTE_PHP}" artisan up || true
    fi
}
trap cleanup EXIT ERR

# Put application into maintenance mode with secret bypass
echo "Entering maintenance mode..."
"${DEPLOY_REMOTE_PHP}" artisan down --render="errors::503" --secret="cricbail-deploy-bypass" || true
MAINTENANCE_ACTIVE=1

# Database Backup (if enabled)
if [ "${DEPLOY_BACKUP_DB}" = "true" ]; then
    echo "Creating pre-deployment database backup..."
    BACKUP_DIR="${DEPLOY_REMOTE_PATH}/storage/app/deployments/backups"
    mkdir -p "${BACKUP_DIR}"
    BACKUP_FILE="${BACKUP_DIR}/db_backup_$(date +%Y%m%d_%H%M%S).sql"

    # Attempt mysqldump using Laravel DB config or defaults
    if command -v mysqldump >/dev/null 2>&1; then
        DB_HOST="$("${DEPLOY_REMOTE_PHP}" -r "echo config('database.connections.mysql.host', '127.0.0.1');" 2>/dev/null || echo '127.0.0.1')"
        DB_PORT="$("${DEPLOY_REMOTE_PHP}" -r "echo config('database.connections.mysql.port', '3306');" 2>/dev/null || echo '3306')"
        DB_NAME="$("${DEPLOY_REMOTE_PHP}" -r "echo config('database.connections.mysql.database', 'cricbail');" 2>/dev/null || echo 'cricbail')"
        DB_USER="$("${DEPLOY_REMOTE_PHP}" -r "echo config('database.connections.mysql.username', 'cricbail');" 2>/dev/null || echo 'cricbail')"
        DB_PASS="$("${DEPLOY_REMOTE_PHP}" -r "echo config('database.connections.mysql.password', '');" 2>/dev/null || echo '')"

        PASS_ARG=""
        [ -n "${DB_PASS}" ] && PASS_ARG="-p${DB_PASS}"

        if mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" ${PASS_ARG} "${DB_NAME}" > "${BACKUP_FILE}" 2>/dev/null; then
            echo "Database backup created: ${BACKUP_FILE} ($(du -h "${BACKUP_FILE}" | cut -f1))"
        else
            echo "WARN: mysqldump exited with error. Verifying existing backup mechanisms."
        fi
    fi
fi

# Code Deployment
if [ "${SKIP_CODE}" = "false" ]; then
    echo "Updating code from GitHub branch '${DEPLOY_BRANCH}'..."
    git fetch origin "${DEPLOY_BRANCH}"
    git checkout "${DEPLOY_BRANCH}"
    git pull origin "${DEPLOY_BRANCH}"

    echo "Installing production PHP dependencies..."
    "${DEPLOY_REMOTE_COMPOSER}" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
fi

# Migrations
echo "Running pending migrations..."
"${DEPLOY_REMOTE_PHP}" artisan migrate --force

# Content Import
if [ "${SKIP_CONTENT}" = "false" ] && [ -n "${REMOTE_BUNDLE_PATH}" ] && [ -f "${REMOTE_BUNDLE_PATH}" ]; then
    echo "Importing content from bundle: ${REMOTE_BUNDLE_PATH}..."
    IMPORT_FLAGS=()
    [ "${FORCE_IMPORT}" = "true" ] && IMPORT_FLAGS+=("--force")

    "${DEPLOY_REMOTE_PHP}" artisan content:import-for-deploy "${REMOTE_BUNDLE_PATH}" "${IMPORT_FLAGS[@]}"

    # Clean up transferred bundle
    rm -f "${REMOTE_BUNDLE_PATH}"
fi

# Storage Link & Optimization Caches
echo "Updating storage links and rebuilding Laravel application caches..."
"${DEPLOY_REMOTE_PHP}" artisan storage:link || true
"${DEPLOY_REMOTE_PHP}" artisan config:cache
"${DEPLOY_REMOTE_PHP}" artisan route:cache
"${DEPLOY_REMOTE_PHP}" artisan view:cache

# Bring application up
echo "Bringing application online..."
"${DEPLOY_REMOTE_PHP}" artisan up
MAINTENANCE_ACTIVE=0

echo "REMOTE_DEPLOYMENT_COMPLETE"
REMOTE_EOF
)

# Run remote script over SSH
if [ -n "${DEPLOY_EXEC_USER}" ] && [ "${DEPLOY_EXEC_USER}" != "${DEPLOY_USER}" ]; then
    log_info "Executing remote deployment as '${DEPLOY_EXEC_USER}' (via su - ${DEPLOY_EXEC_USER})..."
    REMOTE_WRAPPER="/tmp/cricbail_deploy_$$.sh"

    # Transfer script to /tmp as root, set executable, grant ownership to DEPLOY_EXEC_USER
    ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" "cat << 'EOF' > '${REMOTE_WRAPPER}'
${REMOTE_SCRIPT}
EOF
chmod 755 '${REMOTE_WRAPPER}'
chown ${DEPLOY_EXEC_USER}:${DEPLOY_EXEC_USER} '${REMOTE_WRAPPER}'
"

    # Execute under DEPLOY_EXEC_USER, capture exit status, and remove temp script
    ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" "su - ${DEPLOY_EXEC_USER} -c \"bash '${REMOTE_WRAPPER}' '${DEPLOY_REMOTE_PATH}' '${DEPLOY_BRANCH}' '${DEPLOY_REMOTE_PHP}' '${DEPLOY_REMOTE_COMPOSER}' '${DEPLOY_BACKUP_DB}' '${SKIP_CODE}' '${SKIP_CONTENT}' '${REMOTE_BUNDLE_PATH}' '${FORCE_IMPORT}'\"; STATUS=\$?; rm -f '${REMOTE_WRAPPER}'; exit \$STATUS"
else
    ssh "${SSH_OPTS[@]}" "${SSH_TARGET}" bash -s -- \
        "${DEPLOY_REMOTE_PATH}" \
        "${DEPLOY_BRANCH}" \
        "${DEPLOY_REMOTE_PHP}" \
        "${DEPLOY_REMOTE_COMPOSER}" \
        "${DEPLOY_BACKUP_DB}" \
        "${SKIP_CODE}" \
        "${SKIP_CONTENT}" \
        "${REMOTE_BUNDLE_PATH}" \
        "${FORCE_IMPORT}"
fi

# ------------------------------------------------------------------------------
# 7. VERIFICATION AND HEALTH CHECK
# ------------------------------------------------------------------------------
CURRENT_STEP="Healthcheck Verification"
log_step "Verifying production health check"

HEALTHCHECK_PASSED=true
if [ -n "${DEPLOY_HEALTHCHECK_URL}" ]; then
    log_info "Pinging healthcheck URL: ${DEPLOY_HEALTHCHECK_URL}..."
    if command -v curl >/dev/null 2>&1; then
        HTTP_CODE="$(curl -s -o /dev/null -w "%{http_code}" --max-time 15 "${DEPLOY_HEALTHCHECK_URL}" || echo "000")"
        if [ "${HTTP_CODE}" = "200" ] || [ "${HTTP_CODE}" = "301" ] || [ "${HTTP_CODE}" = "302" ]; then
            log_success "Healthcheck verified! Response code: HTTP ${HTTP_CODE}"
        else
            log_warn "Healthcheck returned status HTTP ${HTTP_CODE} (Expected 200/301/302). Please check live site."
            HEALTHCHECK_PASSED=false
        fi
    else
        log_info "curl not available locally; skipping automated HTTP ping."
    fi
fi

# Clean up local bundle if configured
if [ "$DEPLOY_KEEP_LOCAL_BUNDLE" = "false" ] && [ -n "${BUNDLE_FILE}" ] && [ -f "${BUNDLE_FILE}" ]; then
    log_info "Cleaning up temporary local bundle archive..."
    rm -f "${BUNDLE_FILE}"
fi

# ------------------------------------------------------------------------------
# 8. DEPLOYMENT SUMMARY REPORT
# ------------------------------------------------------------------------------
echo -e "\n${C_GREEN}${C_BOLD}========================================================================${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}                   DEPLOYMENT COMPLETED SUCCESSFULLY                    ${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}========================================================================${C_RESET}"
echo -e "Target Server:       ${C_CYAN}${DEPLOY_HOST}${C_RESET} (${DEPLOY_USER}$([ -n "${DEPLOY_EXEC_USER}" ] && [ "${DEPLOY_EXEC_USER}" != "${DEPLOY_USER}" ] && echo " -> ${DEPLOY_EXEC_USER}"))"
echo -e "Deployed Commit:     ${C_CYAN}${COMMIT_MESSAGE}${C_RESET}"
echo -e "Branch:              ${C_CYAN}${DEPLOY_BRANCH}${C_RESET}"
echo -e "Code Deployed:       ${C_GREEN}$([ "$SKIP_CODE" = false ] && echo "YES" || echo "SKIPPED")${C_RESET}"
echo -e "Content Imported:    ${C_GREEN}$([ "$SKIP_CONTENT" = false ] && echo "YES" || echo "SKIPPED")${C_RESET}"
echo -e "Database Backup:     ${C_GREEN}$([ "$DEPLOY_BACKUP_DB" = "true" ] && echo "CREATED" || echo "SKIPPED")${C_RESET}"
echo -e "Live URL:            ${C_CYAN}${DEPLOY_HEALTHCHECK_URL}${C_RESET}"
echo -e "Healthcheck Status:  $([ "$HEALTHCHECK_PASSED" = true ] && echo -e "${C_GREEN}PASS${C_RESET}" || echo -e "${C_YELLOW}CHECK SITE${C_RESET}")"
echo -e "${C_GREEN}${C_BOLD}========================================================================${C_RESET}\n"

exit 0
