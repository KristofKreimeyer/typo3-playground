#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT_DIR}/.env.e2e"

step() {
  printf '\n==> %s\n' "$1"
}

fail() {
  printf 'Error: %s\n' "$1" >&2
  exit 1
}

if [[ -f "${ENV_FILE}" ]]; then
  step "Loading local E2E settings from .env.e2e"
  set -a
  # shellcheck disable=SC1090
  source "${ENV_FILE}"
  set +a
fi

TYPO3_E2E_BASE_URL="${TYPO3_E2E_BASE_URL:-https://typo3-project.ddev.site}"
TYPO3_E2E_ADMIN_USER="${TYPO3_E2E_ADMIN_USER:-${TYPO3_E2E_USER:-e2e-admin}}"
TYPO3_E2E_ADMIN_PASSWORD="${TYPO3_E2E_ADMIN_PASSWORD:-${TYPO3_E2E_PASSWORD:-}}"
TYPO3_E2E_ALLOWED_USER="${TYPO3_E2E_ALLOWED_USER:-e2e-playground}"
TYPO3_E2E_RESTRICTED_USER="${TYPO3_E2E_RESTRICTED_USER:-e2e-restricted}"

[[ -n "${TYPO3_E2E_ADMIN_PASSWORD}" ]] || fail "TYPO3_E2E_ADMIN_PASSWORD is required. Copy .env.e2e.example to .env.e2e and set local passwords."
[[ -n "${TYPO3_E2E_ALLOWED_PASSWORD:-}" ]] || fail "TYPO3_E2E_ALLOWED_PASSWORD is required."
[[ -n "${TYPO3_E2E_RESTRICTED_PASSWORD:-}" ]] || fail "TYPO3_E2E_RESTRICTED_PASSWORD is required."
for username in "${TYPO3_E2E_ADMIN_USER}" "${TYPO3_E2E_ALLOWED_USER}" "${TYPO3_E2E_RESTRICTED_USER}"; do
  [[ "${username}" =~ ^[a-zA-Z0-9._-]+$ ]] || fail "E2E usernames may contain only letters, numbers, dots, underscores, and hyphens."
done
[[ "${TYPO3_E2E_ADMIN_USER}" != "${TYPO3_E2E_ALLOWED_USER}" && "${TYPO3_E2E_ADMIN_USER}" != "${TYPO3_E2E_RESTRICTED_USER}" && "${TYPO3_E2E_ALLOWED_USER}" != "${TYPO3_E2E_RESTRICTED_USER}" ]] || fail "E2E usernames must be distinct."
command -v ddev >/dev/null 2>&1 || fail "DDEV is not installed or is not available on PATH."
[[ -f "${ROOT_DIR}/.ddev/config.yaml" ]] || fail "No DDEV project configuration was found at ${ROOT_DIR}/.ddev/config.yaml."

cd "${ROOT_DIR}"

printf '%s\n' \
  "This local-only bootstrap ensures TYPO3 schema state and compiled backend assets." \
  "It recreates only the three configured E2E users and two deterministic E2E groups." \
  "It does not drop the database or remove other backend users or content."

step "Starting or verifying the DDEV project"
ddev start

if [[ ! -x "${ROOT_DIR}/vendor/bin/typo3" ]]; then
  step "Installing Composer dependencies because vendor/bin/typo3 is missing"
  ddev composer install --no-interaction
fi

htaccess_template="${ROOT_DIR}/vendor/typo3/cms-install/Resources/Private/FolderStructureTemplateFiles/root-htaccess"
if [[ ! -f "${ROOT_DIR}/public/.htaccess" ]]; then
  [[ -f "${htaccess_template}" ]] || fail "public/.htaccess is missing and the TYPO3 root-htaccess template is unavailable. Install typo3/cms-install first."
  step "Installing TYPO3 Apache rewrite rules in public/.htaccess"
  cp "${htaccess_template}" "${ROOT_DIR}/public/.htaccess"
fi

[[ -f "${ROOT_DIR}/config/system/settings.php" ]] || fail "TYPO3 is not installed. Run 'ddev exec vendor/bin/typo3 setup' once, then rerun the E2E bootstrap."

step "Applying TYPO3 extension and database schema setup"
ddev exec vendor/bin/typo3 extension:setup --no-interaction

step "Removing stale sessions for configured local E2E users"
ddev mysql -e "DELETE s FROM be_sessions s INNER JOIN be_users u ON u.uid = s.ses_userid WHERE u.username IN ('${TYPO3_E2E_ADMIN_USER}', '${TYPO3_E2E_ALLOWED_USER}', '${TYPO3_E2E_RESTRICTED_USER}');"

step "Ensuring admin user '${TYPO3_E2E_ADMIN_USER}'"
ddev mysql -e "DELETE FROM be_users WHERE username = '${TYPO3_E2E_ADMIN_USER}';"
ddev exec -- env "TYPO3_BE_USER_NAME=${TYPO3_E2E_ADMIN_USER}" "TYPO3_BE_USER_PASSWORD=${TYPO3_E2E_ADMIN_PASSWORD}" "TYPO3_BE_USER_ADMIN=1" "TYPO3_BE_USER_MAINTAINER=0" vendor/bin/typo3 backend:user:create --no-interaction

step "Ensuring allowed group 'e2e-component-playground-users' with module web_componentplayground"
ddev mysql -e "DELETE FROM be_users WHERE username = '${TYPO3_E2E_ALLOWED_USER}'; DELETE FROM be_groups WHERE title = 'e2e-component-playground-users'; INSERT INTO be_groups (pid, tstamp, crdate, deleted, hidden, title, groupMods) VALUES (0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 'e2e-component-playground-users', 'web,web_componentplayground');"
allowed_group_uid="$(ddev mysql -N -B -e "SELECT uid FROM be_groups WHERE title = 'e2e-component-playground-users' AND deleted = 0 ORDER BY uid DESC LIMIT 1;")"
[[ -n "${allowed_group_uid}" ]] || fail "Could not provision the allowed E2E backend group."

step "Ensuring allowed regular user '${TYPO3_E2E_ALLOWED_USER}'"
ddev exec -- env "TYPO3_BE_USER_NAME=${TYPO3_E2E_ALLOWED_USER}" "TYPO3_BE_USER_PASSWORD=${TYPO3_E2E_ALLOWED_PASSWORD}" "TYPO3_BE_USER_GROUPS=${allowed_group_uid}" "TYPO3_BE_USER_ADMIN=0" "TYPO3_BE_USER_MAINTAINER=0" vendor/bin/typo3 backend:user:create --no-interaction

step "Ensuring restricted group 'e2e-restricted-users' without Component Playground permission"
ddev mysql -e "DELETE FROM be_users WHERE username = '${TYPO3_E2E_RESTRICTED_USER}'; DELETE FROM be_groups WHERE title = 'e2e-restricted-users'; INSERT INTO be_groups (pid, tstamp, crdate, deleted, hidden, title, groupMods) VALUES (0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 0, 'e2e-restricted-users', 'web');"
restricted_group_uid="$(ddev mysql -N -B -e "SELECT uid FROM be_groups WHERE title = 'e2e-restricted-users' AND deleted = 0 ORDER BY uid DESC LIMIT 1;")"
[[ -n "${restricted_group_uid}" ]] || fail "Could not provision the restricted E2E backend group."

step "Ensuring restricted regular user '${TYPO3_E2E_RESTRICTED_USER}'"
ddev exec -- env "TYPO3_BE_USER_NAME=${TYPO3_E2E_RESTRICTED_USER}" "TYPO3_BE_USER_PASSWORD=${TYPO3_E2E_RESTRICTED_PASSWORD}" "TYPO3_BE_USER_GROUPS=${restricted_group_uid}" "TYPO3_BE_USER_ADMIN=0" "TYPO3_BE_USER_MAINTAINER=0" vendor/bin/typo3 backend:user:create --no-interaction

step "Installing frontend dependencies and building TYPO3 backend assets"
ddev exec -- npm --prefix frontend ci
ddev exec -- npm --prefix frontend run build

step "Flushing TYPO3 caches"
ddev exec vendor/bin/typo3 cache:flush

printf '\nBootstrap complete for %s.\n' "${TYPO3_E2E_BASE_URL}"
printf '%s\n' \
  "Run the local tests from the frontend directory:" \
  "  npm run test:e2e:smoke" \
  "  npm run test:e2e:auth"
