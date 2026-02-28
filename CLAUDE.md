<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.6
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v2
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `wayfinder-development` — Activates whenever referencing backend routes in frontend components. Use when importing from @/actions or @/routes, calling Laravel routes from TypeScript, or working with Wayfinder route functions.
- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `inertia-vue-development` — Develops Inertia.js v2 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using <Link>, <Form>, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).
- **FOAMS uses Laravel Fortify for authentication scaffolding** — do not bypass or replace Fortify's auth pipeline.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.
- **Use Wayfinder** when referencing routes from Vue/TypeScript frontend files.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== wayfinder/core rules ===

# Laravel Wayfinder

Wayfinder generates TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

- IMPORTANT: Activate `wayfinder-development` skill whenever referencing backend routes in frontend components.
- Invokable Controllers: `import StorePost from '@/actions/.../StorePostController'; StorePost()`.
- Parameter Binding: Detects route keys (`{post:slug}`) — `show({ slug: "my-post" })`.
- Query Merging: `show(1, { mergeQuery: { page: 2, sort: null } })` merges with current URL, `null` removes params.
- Inertia: Use `.form()` with `<Form>` component or `form.submit(store())` with useForm.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

</laravel-boost-guidelines>

---

# FOAMS — Field Operations & Asset Management System
## ICT Authority of Kenya · eGovernment Department
### Project Memory · Last updated: February 2026
### Documents: URD/2026/001 · SRS/2026/001 · ADD/2026/001

---

## What This Project Is

FOAMS manages ICTA field teams (RICTOs, ICTOs, AICTOs) across Kenya's 47 counties, tracking three types of government ICT infrastructure:

- **Public WiFi Hotspots** — public-facing hotspots in towns and public spaces
- **NOFBI Nodes** — National Optic Fibre Backbone Infrastructure nodes
- **OGN Equipment Sites** — Official Government Network equipment

Key capabilities: asset inventory, daily status monitoring, issue/complaint management, SLA tracking, real-time notifications, analytics dashboards, public complaint portal, offline-capable field tools.

---

## Stack Notes (FOAMS-specific additions to Boost baseline)

The Boost baseline above defines the core stack. These are the additional packages specific to FOAMS:

| Package | Purpose |
|---|---|
| `spatie/laravel-permission` v6 | Role-based access control (8 roles) |
| `pragmarx/google2fa-laravel` | TOTP two-factor authentication |
| `laravel/horizon` | Redis queue worker dashboard |
| `laravel/reverb` | Self-hosted WebSocket server |
| `barryvdh/laravel-dompdf` | Server-side PDF report generation |
| `maatwebsite/excel` | Excel/CSV report export |
| Africa's Talking SDK | SMS gateway (Kenyan provider) |
| `vite-plugin-pwa` + `idb` | Offline PWA + IndexedDB for field use |

**Web server:** Apache 2.4 with PHP 8.3 FPM (on-premises at ICTA — not Sail in production)

**Do not suggest** switching to a cloud deployment, using API tokens for browser auth, or adding a separate REST API layer — Inertia handles all frontend data via server-side rendering.

---

## Stack Version Corrections (ADD vs actual installed)

The ADD was drafted before Boost was installed. Where they differ, **Boost wins**:

| Layer | ADD said | Actual (Boost) |
|---|---|---|
| Laravel | 11 | **12** |
| Inertia | v1 | **v2** |
| Tailwind | v3 | **v4** |
| Pest | v2 | **v4** |
| Auth scaffolding | Manual Sanctum | **Laravel Fortify** |
| Route typing | None | **Laravel Wayfinder** |

Middleware registration: **bootstrap/app.php** (Laravel 12 style) — not Kernel.php.
The `RegionScopeMiddleware` and `TwoFactorMiddleware` must be registered in `bootstrap/app.php`.

---

## The Eight User Roles

Managed via `spatie/laravel-permission`. Seeded in `RoleSeeder`.

| Display Name | Spatie slug | Scope |
|---|---|---|
| System Administrator | `admin` | Unrestricted — all data, all config |
| Director | `director` | National read + escalation receipt |
| NOC Officer | `noc` | National issue management |
| RICTO | `ricto` | Region-scoped — issues, officers, assets |
| ICTO | `icto` | Region-scoped — assets, logs, issues |
| AICTO | `aicto` | Region-scoped — assets, logs, issues |
| Public Servant | `public_servant` | Own submitted issues only |
| General Public | `public` | Unauthenticated submit + track by reference |

**Critical rule:** RICTO, ICTO, and AICTO are region-scoped at the **data layer** via `RegionScopeMiddleware` + Eloquent global scopes. Never rely on frontend filtering alone.

---

## Database — 13 Core Tables

### Table Schemas (quick reference)

**users** — id, name, username (UNIQUE), email (UNIQUE), phone, password (bcrypt≥12), region_id (FK nullable), is_active, two_factor_secret, two_factor_confirmed_at, failed_login_attempts, locked_until, deleted_at, timestamps

**regions** — id, name (UNIQUE), code (UNIQUE), is_active, deleted_at, timestamps

**counties** — id, name (UNIQUE), code (UNIQUE), region_id (FK→regions), timestamps

**assets** — id, asset_code (UNIQUE, system-generated e.g. WIFI-MSA-001), name, type ENUM('wifi_hotspot','nofbi_node','ogn_equipment'), county_id (FK), location_name, latitude DECIMAL(10,7), longitude DECIMAL(10,7), assigned_to (FK→users nullable), installation_date, manufacturer, model, serial_number (UNIQUE nullable), status ENUM('operational','degraded','down','maintenance','decommissioned'), deleted_at, timestamps

**asset_status_logs** — id, asset_id (FK), user_id (FK), logged_date DATE, observed_at TIME, status ENUM('operational','degraded','down','maintenance'), throughput_mbps DECIMAL(8,2), remarks TEXT, latitude, longitude, is_amendment BOOL, amendment_reason TEXT, synced_at (NULL = created offline), timestamps
→ UNIQUE(asset_id, user_id, logged_date)

**issues** — id, reference_number (UNIQUE, e.g. ISS-0847), asset_id (FK nullable), county_id (FK, denormalised for region scoping), issue_type VARCHAR, severity ENUM('low','medium','high','critical'), status ENUM('new','acknowledged','in_progress','pending_third_party','escalated','resolved','closed','duplicate'), reporter_category ENUM('general_public','public_servant','field_officer'), reporter_name/email/phone (nullable), created_by_user_id (FK nullable), assigned_to_user_id (FK nullable), description TEXT (FULLTEXT indexed), workaround_applied BOOL, duplicate_of_id (self-FK nullable), acknowledged_at, resolved_at, closed_at, sla_due_at, sla_breached BOOL, is_escalated BOOL, escalated_at, escalated_by_user_id (FK nullable), timestamps

**issue_activities** (**APPEND-ONLY** — no UPDATE/DELETE ever) — id, issue_id (FK), user_id (FK nullable), action_type VARCHAR('status_change','comment','field_note','escalation','assignment'), previous_status, new_status, comment TEXT, is_internal BOOL (hides from public tracker), created_at only (no updated_at)

**resolutions** — id, issue_id (UNIQUE FK), root_cause TEXT (FULLTEXT indexed), steps_taken JSON (array ≤10 items), resolution_type ENUM('temporary','permanent'), resolved_by_user_id (FK), resolved_at, timestamps

**attachments** (polymorphic) — id, attachable_type, attachable_id, original_name, stored_name (UUID-based), mime_type, size_bytes, uploaded_by (FK→users), timestamps

**audit_logs** (**IMMUTABLE** — no UPDATE/DELETE ever) — id, user_id (FK nullable), event VARCHAR, auditable_type, auditable_id, old_values JSON, new_values JSON, ip_address, user_agent, created_at only

**sla_configurations** — id, severity ENUM, acknowledge_within_hrs SMALLINT, resolve_within_hrs SMALLINT, effective_from TIMESTAMP, created_by_user_id (FK), timestamps

**notifications** — standard Laravel notifications table

**model_has_roles / role_has_permissions / etc.** — standard Spatie tables (auto-created by Spatie migration)

### Required Indexes
```sql
-- issues
INDEX idx_issues_county_status (county_id, status)
INDEX idx_issues_severity_status (severity, status)
INDEX idx_issues_sla_due (sla_due_at)
FULLTEXT INDEX ft_issues_description (description)

-- asset_status_logs
INDEX idx_asl_asset_date (asset_id, logged_date)
INDEX idx_asl_user_date (user_id, logged_date)

-- resolutions
FULLTEXT INDEX ft_resolutions_root_cause (root_cause)
```

---

## Issue Status Workflow

```
New → Acknowledged → In Progress → Resolved → Closed
                            ↘ Pending Third Party ↗
                            ↘ Escalated ↗
              (terminal) → Duplicate
```

Every transition is logged in `issue_activities`. Enforced by `IssuePolicy` — role-based transition permissions apply.

---

## SLA Targets (stored in sla_configurations, not hardcoded)

| Severity | Acknowledge Within | Resolve Within |
|---|---|---|
| Critical | 1 hour | 4 hours |
| High | 4 hours | 8 hours |
| Medium | 8 hours | 24 hours |
| Low | 24 hours | 72 hours |

`sla_due_at` computed on issue creation from `sla_configurations`. `foams:check-sla` runs every 5 minutes via scheduler. **Never hardcode these values.**

---

## Key Business Rules (from SRS — enforce these always)

1. **One status log per asset per officer per day** — UNIQUE(asset_id, user_id, logged_date). Amendments: new row with is_amendment=true.
2. **Login lockout** — 5 failures → locked_until = now() + 30 min. Fortify handles this; ensure config aligns.
3. **Password reset tokens expire in 15 minutes** and invalidate ALL sessions for that user on use.
4. **Public issue submission is unauthenticated** — rate-limited 10/min per IP.
5. **Acknowledgement email within 5 minutes** of public issue creation — queued job, `notifications` queue.
6. **Critical issues → immediate SMS** to RICTO's phone — `critical` queue, Africa's Talking.
7. **SLA 50% elapsed** → warning to NOC + RICTO. **Breach (100%)** → flag issue, auto-escalate to Director, notify NOC + RICTO + Director.
8. **Daily reminders** — 16:00 EAT (officers), 18:00 EAT (RICTO, escalation if officers haven't logged).
9. **issue_activities and audit_logs are append-only** — REVOKE UPDATE/DELETE from `foams_app` DB user at MySQL level.
10. **Attachments** — stored as UUID filenames under `storage/app/private`. Never web-accessible. Serve via `Storage::temporaryUrl()` (15-min signed URLs).
11. **Public tracker** — shows reference number, status, location, non-internal comments only. Never exposes officer identities or internal notes (`is_internal = true` rows are hidden).
12. **Reporter personal data purged 12 months** after issue closure — Kenya Data Protection Act 2019.
13. **Session timeout: 30 minutes** idle — configured via Fortify/Sanctum session lifetime.
14. **Uptime threshold: 95%** — assets below this are highlighted amber/red in dashboard.

---

## Service Layer

Controllers are thin — validate via FormRequest, call a Service, return `Inertia::render()`.

| Service | Key Methods |
|---|---|
| `IssueService` | `createIssue()`, `transitionStatus()`, `escalate()`, `resolve()`, `close()`, `generateReferenceNumber()` |
| `SlaService` | `computeDueAt(Issue): Carbon`, `runSlaCheck()` |
| `UptimeService` | `computeUptime(Asset, Carbon $from, Carbon $to): float`, `getAvailabilityCalendar()` |
| `NotificationService` | `notifyRicto()`, `dispatchStatusChangeEmail()`, `queueSms()` |
| `ReportService` | `buildIssueReport(array $filters)`, `buildAssetReport(array $filters)` |
| `OfflineSyncService` | `reconcileOfflineLog(array $payload, User $user)` |

---

## Background Jobs & Queues

Queue driver: **Redis** via **Laravel Horizon**. Workers managed by Supervisor.

| Job | Queue | Trigger |
|---|---|---|
| `SendIssueAcknowledgementEmail` | `notifications` | Issue created (public/public_servant) |
| `SendStatusChangeEmail` | `notifications` | Any status transition |
| `SendSmsJob` | `critical` | Critical issue raised, RICTO has phone |
| `SendEscalationNotification` | `critical` | Issue escalated |
| `SendSlaBreachNotification` | `critical` | SLA check detects breach |
| `SendDailyStatusReminder` | `notifications` | Scheduler 16:00 + 18:00 EAT |
| `GenerateReportJob` | `reports` | User requests PDF/Excel export |
| `ProcessOfflineSyncJob` | `default` | Field device reconnects and syncs |

---

## Real-Time Broadcasting (Laravel Reverb + Laravel Echo)

Reverb: `127.0.0.1:8080`, reverse-proxied through Apache (`wss://`).

| Event | Channel | Triggers |
|---|---|---|
| `IssueStatusChanged` | `private-region.{region_id}` | Real-time panel row update |
| `IssueRaised` | `private-region.{region_id}` | Notification bell badge |
| `IssueEscalated` | `private-user.{director_id}` | Director direct alert |
| `SlaBreached` | `private-region.{region_id}` | Row turns red in issues panel |
| `StatusLogSubmitted` | `private-region.{region_id}` | RICTO compliance table update |

---

## Offline Field Support (PWA)

- Service Worker via `vite-plugin-pwa`
- Offline data capture uses **IndexedDB** via `idb` library
- Composable: `useOfflineSync.js` — queues to IndexedDB offline, auto-syncs on `window.online`
- Sync endpoint: `POST /offline-sync` → dispatches `ProcessOfflineSyncJob`
- Yellow "Offline" banner displayed when `navigator.onLine === false`

---

## ICTA Brand (apply in all Vue pages via Tailwind)

| Token | Hex | Use |
|---|---|---|
| Primary dark blue | `#1F3864` | Sidebar, headings, primary buttons |
| Accent mid blue | `#2E5FA3` | Links, active states, secondary buttons |
| Light blue | `#D6E4F7` | Table header backgrounds |
| Pale blue | `#EEF4FB` | Alternating table rows, card backgrounds |

Font: **Arial** system font — no Google Fonts CDN dependency in production.

---

## Deployment (On-Premises at ICTA)

- Apache 2.4 + PHP 8.3 FPM (not Sail in production — Sail for local dev only)
- Supervisor manages: `foams-horizon` + `foams-reverb`
- Environments: `local` → `staging (foams-uat.ict.go.ke)` → `production (foams.ict.go.ke)`
- All data stored on ICTA premises in Kenya (data sovereignty requirement)
- Daily MySQL backups; RPO ≤ 24h; RTO ≤ 4h

---

## Development Phase Plan

**Phase 1 (Weeks 1–6) — Foundation**
Migrations → Seeders → Models → Spatie RBAC → Fortify auth + 2FA → RegionScopeMiddleware → Asset/Region CRUD → Daily status log → UptimeService → Pest tests for all of the above

**Phase 2 (Weeks 7–10) — Issues, Notifications & Reports**
IssueService + full workflow → NOC panel → SlaService + scheduler → Email/SMS notifications → Reverb WebSocket + Echo → Real-time panel → Escalation → PDF/Excel reports → Public portal

**Phase 3 (Weeks 11–14) — Offline & Hardening**
PWA/Service Worker → IndexedDB offline queue → Security hardening (CSP, rate limits) → Pen test prep → DPIA → UAT on staging → Production deploy

---

## What to Always Do

- Use **Fortify** for authentication — don't build auth from scratch
- Use **Wayfinder** when calling routes from Vue components
- Write **Pest feature tests** alongside every feature (happy path + auth failure + policy rejection + validation failure)
- Put business logic in **Services**, not controllers
- Enforce region scoping at the **data layer** (Eloquent global scopes), never only at UI
- Use **FormRequest** classes for all validation
- Fire **Events** on significant state changes → Listeners handle side effects
- Write to **audit_logs** via `AuditObserver` on every model mutation
- Store all timestamps in **UTC**; display in **EAT (Africa/Nairobi)** via Carbon
- Run `vendor/bin/pint --dirty --format agent` after every PHP file change

## What to Never Do

- Never hardcode SLA values — always read from `sla_configurations`
- Never allow UPDATE or DELETE on `issue_activities` or `audit_logs`
- Never store uploaded files with original filenames — always UUID-named
- Never serve attachments directly — always `Storage::temporaryUrl()`
- Never enforce region scoping only in Vue — enforce at Eloquent level
- Never put raw queries in controllers — use Eloquent
- Never use `env()` outside config files
- Never skip the Pest test for a completed feature
- Never use `DB::` — prefer `Model::query()`
