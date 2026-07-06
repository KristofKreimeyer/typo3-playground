# Fresh Clone E2E Verification

## Purpose

This procedure checks whether a clean clone can create an isolated DDEV project, install TYPO3, provision the local E2E users, and pass the complete browser quality gate without relying on another working copy, database, cache, session, or generated configuration.

The verification recorded here used commit `1a17250631f1bcc3f7b556b27fbb0218a7e0293d` from `https://github.com/KristofKreimeyer/typo3-playground.git` in `/tmp/typo3-component-playground-fresh`.

## Preconditions

- Git
- DDEV with Docker access
- Node.js 20 or newer and npm
- network access for Composer, npm, and the Playwright browser download
- an unused DDEV project name

No existing `.env.e2e`, TYPO3 database, `vendor/`, `node_modules/`, `config/`, `public/`, `var/`, browser session, or route token may be reused.

## Fresh clone commands

```bash
git clone https://github.com/KristofKreimeyer/typo3-playground.git /tmp/typo3-component-playground-fresh
cd /tmp/typo3-component-playground-fresh
git status --short
git rev-parse HEAD
```

Immediately after cloning the tested commit:

| Path | State |
| --- | --- |
| `.ddev/` | missing |
| `config/` | missing |
| `public/` | missing |
| `composer.json` | present |
| `composer.lock` | present |
| root `package.json` | missing by design |
| `frontend/package.json` | present |
| `frontend/playwright.config.ts` | missing |
| `scripts/` | missing |
| `.env.e2e.example` | missing |
| `README.md` | present |

This means the tested remote commit does not yet contain the local E2E implementation from the development working tree.

## DDEV setup

Use a unique project name so the clean environment cannot reuse or collide with another project:

```bash
ddev config \
  --project-name=typo3-component-playground-fresh \
  --project-type=typo3 \
  --docroot=public \
  --php-version=8.2 \
  --webserver-type=apache-fpm
ddev start
```

The isolated DDEV project started successfully. DDEV warned that `public/index.php` did not exist before dependency installation, which is expected at this stage.

## PHP dependency installation

```bash
ddev composer install --no-interaction
```

The locked Composer installation passed and generated `vendor/`, `public/index.php`, and `public/_assets`.

At the tested commit, Composer does not install `typo3/cms-install`. Consequently:

- `vendor/bin/typo3 setup` is not registered;
- the TYPO3 `root-htaccess` template is unavailable;
- `config/system/settings.php` cannot be generated through the documented CLI setup;
- a new TYPO3 database cannot be initialized through the repository procedure.

This blocks fresh-clone E2E execution.

## TYPO3 setup

Once `typo3/cms-install` and the E2E files are committed, run:

```bash
ddev exec vendor/bin/typo3 setup
```

The command is interactive in the current TYPO3 setup. Use these DDEV values:

- web server: already configured by DDEV as `apache-fpm`
- database driver: MySQL-compatible / `mysqli`
- database host: `db`
- database port: `3306`
- database name: `db`
- database username: `db`
- database password: `db`
- initial administrator: a temporary local setup administrator; do not commit its password
- project name: `TYPO3 Component Playground`
- site setup: no frontend site is required for the backend-module E2E flow

Interactive setup is currently a CI-readiness gap. The local E2E bootstrap intentionally provisions its deterministic users only after the base TYPO3 installation exists.

## Apache rewrite setup

The clean Composer installation did not create `public/.htaccess`; `/typo3/login` returned HTTP `404`.

After `typo3/cms-install` is present, use:

```bash
cp vendor/typo3/cms-install/Resources/Private/FolderStructureTemplateFiles/root-htaccess public/.htaccess
ddev restart
```

The current E2E bootstrap now copies this file automatically when the template exists and fails clearly when the package or template is missing.

## E2E environment setup

After the E2E files are committed:

```bash
cp .env.e2e.example .env.e2e
# Replace every example password in .env.e2e.
git check-ignore .env.e2e
```

The bootstrap and Playwright configuration load `.env.e2e` automatically. Do not commit this file.

## Frontend and Playwright setup

Install frontend dependencies and the Chromium binary:

```bash
cd frontend
npm ci
npx playwright install chromium
```

In the verification environment, host-side `npm ci` under `/tmp` was blocked by an execution-sandbox `EPERM` for the esbuild binary. The repository dependencies themselves were valid: the equivalent DDEV command passed.

```bash
cd ..
ddev exec -- npm --prefix frontend ci
```

The tested remote commit does not contain `@playwright/test`, Playwright configuration, E2E specs, or the browser scripts, so a Playwright browser installation for that commit is not applicable.

## Running `e2e:local`

When the E2E implementation and TYPO3 setup dependencies are present:

```bash
cd frontend
npm run e2e:local
```

Expected order:

1. deterministic E2E bootstrap
2. authorization spec
3. full smoke spec

Expected final message:

```text
Local TYPO3 E2E quality gate passed.
```

At the tested remote commit, this command fails immediately with `Missing script: "e2e:local"`. No authorization or smoke tests can run from that clone.

## Hidden-state findings

| Assumption | Finding | Classification |
| --- | --- | --- |
| Existing database | A clean database is not initialized and setup cannot run at the tested commit. | Gap: blocks CI readiness |
| TYPO3 setup package | `typo3/cms-install` is absent from the committed lock. | Gap: blocks CI readiness |
| `.htaccess` | Not generated; `/typo3/login` returns `404`; template is also absent. | Gap: blocks CI readiness |
| E2E implementation | Config, specs, scripts, environment example, and npm scripts are not in the remote commit. | Gap: blocks verification |
| DDEV configuration | Not committed; creating it from the documented command works. | Gap: needs documentation or committed config |
| DDEV project name | A unique `--project-name` prevents collision successfully. | OK: deterministic when supplied |
| Composer lock | Installs successfully with PHP 8.2 in DDEV. | OK: deterministic |
| Public assets | Composer generates TYPO3 public assets from zero state. | OK: deterministic |
| Frontend dependencies | `npm ci` succeeds inside DDEV. | OK: deterministic |
| Playwright browser | Not installable as project tooling until Playwright is committed. | Gap: blocks verification |
| E2E environment file | Example and ignore rule are absent from the tested commit. | Gap: blocks verification |
| Backend users/groups | Provisioning scripts are absent from the tested commit. | Gap: blocks verification |
| Frontend build | The committed frontend can build after dependency installation, but the E2E bootstrap is absent. | Gap: needs committed scripts |
| Existing config/cache/session | None were reused. | OK: verification isolation |
| Route tokens/cookies | None were reused. | OK: verification isolation |

## CI-readiness notes

Do not add E2E to CI yet. First:

1. commit and push the DDEV/E2E implementation and its locked dependencies;
2. repeat this procedure against that new remote commit;
3. decide how TYPO3 base installation will become non-interactive and deterministic;
4. verify `/typo3/login`, user provisioning, authorization, and smoke tests from the clean clone;
5. only then design a database-backed browser job.
