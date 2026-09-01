# Research: POS SaaS Phase 1

## Decision: Use The Current Repository Stack As Source Of Truth

**Rationale**: The user plan mentions Laravel 11, but the installed project is Laravel 13
with Inertia v3, Fortify, Sanctum, Filament v5, Spatie Permission, Spatie Media Library,
Wayfinder, PHPUnit 12, and Pint. Planning against installed versions avoids stale APIs and
matches the constitution.

**Alternatives considered**: Downgrading or targeting Laravel 11 was rejected because it
would conflict with current dependencies and project guidance.

## Decision: Single-Database Tenancy With Application-Level Scope

**Rationale**: The project scope explicitly requires single DB tenancy. Every tenant-owned
model will use `tenant_id`, `BelongsToTenant`, `SetCurrentTenant`, factories, and
cross-tenant feature tests. Global tables are limited to plans and framework-level metadata.

**Alternatives considered**: Database-per-tenant was rejected for Phase 1 due to operational
complexity and because the supplied plan requires single DB.

## Decision: Action And Repository Layers Own Business Workflows

**Rationale**: The constitution and user Definition of Done require Action classes and
Repository classes. This isolates transactions for sale processing, stock updates, returns,
debts, offline sync, and subscription renewals.

**Alternatives considered**: Fat controllers or model callbacks were rejected because they
obscure transaction boundaries and make tenant/permission tests harder to target.

## Decision: Use Nwidart Modules For Business Domains

**Rationale**: The user requested modular architecture with `nwidart/laravel-modules`.
Tenant, identity, inventory, sales, customers, restaurant, reports, settings, offline, and
billing domains will live under `Modules/`, each with module-local controllers, actions,
repositories, DTOs, requests, models, routes, migrations, and tests. Shared cross-domain
infrastructure such as tenant scoping and enums lives in `Modules/Shared`.

**Alternatives considered**: Root `app/Actions`, `app/Repositories`, and root route files
were rejected for domain logic because they blur ownership and make large Phase 1 delivery
harder to split by module.

## Decision: Inertia/Vue For Operational UI, Blade For Print Tickets

**Rationale**: The app already uses Inertia/Vue and the UI references are app screens rather
than marketing pages. Inertia pages should implement POS, inventory, customers, reports,
settings, and billing. Blade remains appropriate for thermal receipt and kitchen ticket
print views because print CSS and iframe/window printing are easier to isolate.

**Alternatives considered**: Building all UI in Filament was rejected for the cashier POS
because the supplied references require a touch-optimized operational surface.

## Decision: Use The `ui/` Folder As Visual Contract

**Rationale**: The user explicitly requested planning with `@ui`. The design system defines
Deep Indigo, Emerald success, Amber warning, compact tables, touch POS tiles, IBM Plex Sans
Arabic, RTL mirroring, and desktop/tablet density. Screen references map to public
registration, dashboard, POS, products, categories, variants, customers/debts, restaurant
tables, KDS, reports, settings, and offline status.

**Alternatives considered**: Replacing the visual direction with generic starter-kit UI was
rejected because it would not meet the Egyptian POS operating context.

## Decision: Spreadsheet, Barcode, PDF, PWA, Offline DB, And Payment Packages Need Approval

**Rationale**: The user plan requests dependencies not currently installed:
`maatwebsite/excel`, a barcode generator, DOMPDF or equivalent, Vite PWA plugin, `sql.js`,
PayMob/Stripe clients, and possibly Reverb. The constitution forbids dependency changes
without approval, so implementation tasks must request approval before adding them.

**Alternatives considered**: Hand-rolling spreadsheet/PDF/service worker/payment clients was
rejected unless a package is denied, because mature libraries reduce risk for core business
flows.

## Decision: Offline Sync Uses Server Idempotency

**Rationale**: Offline sales must never duplicate revenue or stock deductions. Each offline
sale carries `local_id`; the server stores it with a unique index and returns per-item
statuses. Conflicts skip gracefully and client marks only successful rows synced.

**Alternatives considered**: Blind replay without idempotency was rejected due to duplicate
sale risk.

## Decision: Reporting Aggregates Must Declare Accounting Rules

**Rationale**: Sales, profit, cashier, and debt reports must consistently handle discounts,
returns, tax, deferred payments, and tenant scoping. Report actions should centralize these
rules and cache expensive dashboard data.

**Alternatives considered**: Ad hoc controller queries were rejected because they create
inconsistent financial numbers.
