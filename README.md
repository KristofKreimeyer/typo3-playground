# TYPO3 Component Playground

TYPO3 Component Playground is a backend module foundation for developing and previewing TYPO3 content components with mock JSON data. This first iteration provides the TYPO3 module shell and a React workspace only; component discovery, fixture editing, and preview rendering are intentionally not implemented yet.

## Setup assumptions

- PHP 8.2 or newer
- A Composer-based TYPO3 12.4.37+ or 13.4.18+ installation
- Node.js 20 or newer and npm
- This repository is installed as the `component_playground` extension, typically under `packages/component-playground`

Require the local extension through the TYPO3 project's Composer configuration, run `composer req vendor/typo3-component-playground:@dev`, and activate the extension if the installation does not use Composer mode activation. Backend users need access to the **Web > Component Playground** module through TYPO3 backend group permissions.

## Frontend development

Install dependencies:

```bash
cd frontend
npm install
```

Run the Vite development server for standalone frontend development:

```bash
npm run dev
```

The development server is intended for UI work. TYPO3 loads compiled assets from `Resources/Public/BackendApp`, so use the production build to test the integrated backend module.

## Build

```bash
cd frontend
npm run build
```

Vite writes `app.js`, `app.css`, and any supporting assets directly to `Resources/Public/BackendApp`. Build artifacts are ignored and should be produced during packaging or deployment.

## Current scope

- TYPO3 12.4/13.4-compatible extension metadata and service configuration
- Backend module registration under the Web section
- TYPO3 module response containing a React mount point
- Typed React application with component, editor, and preview placeholders
- Vite/TypeScript production build configuration
- PHPUnit and Vitest unit test coverage with automated CI quality gates
- No database schema, persistence, custom authentication, real page records, or content-element persistence

## Component Registry

Component metadata currently lives in `Classes/Service/ComponentRegistry.php` and is exposed to the React module through an authenticated TYPO3 backend AJAX route. The registry is intentionally explicit for the MVP: definitions are hardcoded, predictable, and have no persistence or file scanning. It can later be extended or replaced with Content Blocks auto-discovery once the component contract is stable.

The frontend retains local fixtures for isolated development only. To opt into them while running the Vite development server, set `VITE_USE_MOCK_COMPONENTS=true`; production builds always use the TYPO3 registry.

## Preview Rendering

React sends the selected component key and the current valid props to the TYPO3 backend preview route. The backend resolves that key through `ComponentRegistry` and only renders the template explicitly registered there; request data can never select an arbitrary template or filesystem path.

Fluid preview templates live in `Resources/Private/Templates/Preview`. Their independent stylesheet lives at `Resources/Public/Preview/preview.css` and is loaded inside a sandboxed iframe, preventing preview styles from leaking into the TYPO3 backend UI. The API returns the rendered markup and stylesheet URL separately, and React assembles the isolated iframe document.

This MVP renders controlled fixture data directly through Fluid. It intentionally creates no TYPO3 pages, content elements, database records, or persistence layer.

## Preview Robustness

Rendered output is isolated in a script-free, sandboxed iframe whose document loads the dedicated preview stylesheet. Registry variants intentionally cover long copy, missing images and actions, small collections, and empty data so component templates can be tested against realistic edge cases.

Debounced render requests use both cancellation and request sequence checks, preventing an older response from replacing newer preview output during rapid editing. The JSON editor intentionally remains a lightweight textarea for the MVP, with formatting, reset, copy, and parse-error feedback rather than a full editor dependency.

## Props Schema Validation

Each component declares a small, explicit props schema alongside its metadata in `Classes/Service/ComponentRegistry.php`. The backend validates required fields and the supported string, object, array, and nested item types before invoking Fluid. Unknown fields remain allowed so developers can experiment without the MVP validator becoming unnecessarily restrictive.

Malformed JSON is handled immediately by the React editor and never reaches the render endpoint. Syntactically valid JSON with props that do not match the selected component schema is rejected by the backend with HTTP `422`, the stable error code `invalid_props`, and field-level validation details. This boundary keeps Fluid preview input predictable while preserving resilient templates for optional values.

## Backend Route Security

The component-list and preview-render endpoints are TYPO3 backend AJAX routes. Both require an authenticated backend session and a valid TYPO3 route token, and both inherit access from the `web_componentplayground` backend module. Administrators and regular backend users with module permission can use the endpoints; authenticated users without that permission receive HTTP `403`.

The render endpoint does not persist data, but it remains protected because it accepts developer-controlled preview props and returns server-rendered HTML. Route tokens are generated by TYPO3 and included in the URLs exposed through `TYPO3.settings.ajaxUrls`; the React application does not construct tokens itself.

## Testing

Install PHP dependencies, then run the fast unit suite and TYPO3 functional suite separately or together:

```bash
composer install
composer test:php:unit
composer test:php:functional
composer test:php:security
composer test:php
```

Run PHP syntax checks with `composer lint:php`.

Install frontend dependencies and run its quality gates:

```bash
cd frontend
npm ci
npm run typecheck
npm run test
npm run build
```

Unit tests cover registry contracts and edge variants, component lookup, preview request validation and stable error codes, template-name whitelisting, and template file contracts. Functional tests bootstrap TYPO3 and verify selected Hero, TeaserGrid, Quote, and CTA templates render real HTML, including missing optional data and empty-list fallbacks. Real Fluid rendering belongs in the functional suite because its view factory, package paths, dependency injection, and Fluid runtime are provided by the TYPO3 bootstrap.

The functional suites disable database initialization because preview rendering has no persistence dependency. They therefore run in CI without a database service. Database behavior and end-to-end browser workflows are intentionally excluded.

### Functional Route Dispatch Tests

The route-dispatch functional tests match the real `ajax_component_playground_preview_render` backend route and execute it through TYPO3's backend `RouteDispatcher`. They cover successful Fluid rendering, schema-invalid props, unknown components, malformed JSON, missing component keys, and non-object props. These tests complement the isolated request-validator units and renderer functional tests; they do not exercise React or browser behavior.

The suite intentionally disables database initialization. A persisted backend session and form-protection token are therefore unavailable, so only the matched test-request route is marked public before dispatch. Route registration, HTTP method matching, dependency-injection controller resolution, request parsing, schema validation, component lookup, Fluid rendering, and JSON response formatting remain part of the test. Authentication and module permission middleware should be covered separately if the project later adds a database-backed backend security test suite.

The preview API uses a stable error envelope:

```json
{
  "error": {
    "code": "invalid_props",
    "message": "The provided props do not match the component schema.",
    "details": []
  }
}
```

`details` is present for field-level schema failures. Error responses never include an `html` field.

### Security Functional Tests

Security functional tests use TYPO3's database-backed functional environment and full backend middleware stack. Fixtures create an administrator, a regular user with Component Playground module access, and a regular user without access. The suite verifies unauthenticated redirects/denial, valid authenticated requests, missing and invalid route tokens, and inherited module permission decisions for both backend endpoints.

Provide a MySQL-compatible test database through the standard TYPO3 Testing Framework variables:

```bash
typo3DatabaseDriver=pdo_mysql \
typo3DatabaseHost=127.0.0.1 \
typo3DatabasePort=3306 \
typo3DatabaseName=component_playground_test \
typo3DatabaseUsername=root \
typo3DatabasePassword=root \
composer test:php:security
```

`composer test:php` runs unit, database-free functional, and security suites. GitHub Actions provisions MySQL 8.4 and runs all three. These tests cover backend HTTP security behavior, not React or browser interaction.

Frontend tests cover JSON validation and editor actions, preview loading/error/success behavior, stale request protection, component loading, empty registries, retry behavior, and initial selection.

## Next implementation steps

1. Define per-component JSON schemas and validate props before Fluid rendering.
2. Add backend route-dispatch functional coverage.
3. Add Content Blocks discovery behind the existing registry contract.

## Compatibility

The TYPO3 Component Playground is tested against:

- TYPO3 12.4 LTS
- TYPO3 13.4 LTS
- PHP 8.2

The GitHub Actions workflow installs the extension with both TYPO3 versions through a dependency matrix and runs the PHP test suite for each version.

Frontend checks run separately because the React/TypeScript backend app is independent from the installed TYPO3 core version.
