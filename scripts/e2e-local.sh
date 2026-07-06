#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FRONTEND_DIR="${ROOT_DIR}/frontend"

step() {
  printf '\n==> %s\n' "$1"
}

step "Bootstrapping local TYPO3 E2E state"
npm --prefix "${FRONTEND_DIR}" run e2e:bootstrap

step "Running authorization test"
npm --prefix "${FRONTEND_DIR}" run test:e2e:auth

step "Running smoke test"
npm --prefix "${FRONTEND_DIR}" run test:e2e:smoke

printf '\nLocal TYPO3 E2E quality gate passed.\n'
