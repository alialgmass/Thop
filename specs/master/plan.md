# Implementation Plan: POS SaaS Phase 1

**Branch**: `master` | **Date**: 2026-05-10 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/master/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command.

## Summary

Build Phase 1 of a multi-tenant Egyptian POS SaaS on the existing Laravel/Inertia/Vue
application using `nwidart/laravel-modules` for business-domain boundaries. The system
covers tenant registration/auth, RBAC, product and inventory management, cashier checkout,
customer debts, restaurant orders, reporting/settings, offline PWA sync, and SaaS
subscriptions. UI implementation will follow the supplied `ui/` reference screens and
`ui/egyptian_pos_standard/DESIGN.md`.

## Technical Context

**Language/Version**: PHP 8.4 target per project guidance, current composer constraint PHP ^8.3; TypeScript; Vue 3
**Primary Dependencies**: Laravel 13, Inertia Laravel v3, Vue 3, Fortify, Sanctum, Spatie Permission, Spatie Media Library, Filament v5 where admin panels are appropriate, Wayfinder, PHPUnit 12, Pint
**Storage**: Single relational database with tenant-scoped tables; Redis/cache for reports and tagged product cache; browser sql.js or fallback IndexedDB for offline POS mirror
**Testing**: PHPUnit unit/feature/integration tests with focused `php artisan test --compact`; targeted performance tests for POS search and offline sync; TypeScript checks for frontend changes
**Target Platform**: Web POS/admin SaaS for desktop and tablet browsers in Egypt, with PWA installability for cashier terminals
**Project Type**: Laravel + Inertia SaaS web application
**Performance Goals**: POS search under 200ms with 10,000 products; barcode scan-to-cart under 1 second; report dashboard cached for 1 hour; offline sync batches up to 50 sales
**Constraints**: Tenant isolation, permission enforcement, action-centric business logic, Repository-backed data access, RTL/tablet support, no unapproved dependencies
**Scale/Scope**: Phase 1 covers 40 user stories across 7 sprints, 43 detailed task groups, single-database tenancy, retail and restaurant tenants, and Egyptian payment/receipt workflows

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Tenant Isolation**: PASS. Tenant-owned tables are identified in data model. All domain
  models except global plans and permission metadata require `tenant_id`, `BelongsToTenant`,
  factories, and cross-tenant tests.
- **Action Architecture**: PASS. Workflows are planned as Action classes:
  `RegisterTenantAction`, `CreateProductAction`, `ImportProductsAction`,
  `UpdateStockAction`, `ProcessSaleAction`, `ApplyDiscountAction`, `RecordDebtAction`,
  `RecordDebtPaymentAction`, `ProcessReturnAction`, `SendToKitchenAction`,
  `CheckoutOrderAction`, `ProcessOfflineSaleAction`, and subscription actions. Repository
  classes own query/data access per aggregate.
- **Testing**: PASS. Plan requires unit tests for each Action, feature tests for routes and
  permissions, integration tests for tenant boundaries/offline sync, and performance tests
  for POS search.
- **Permissions & Audit**: PASS. High-risk operations are permission-gated and auditable:
  discounts, refunds, stock movements, debts, user management, reports, subscription
  changes, kitchen/order lifecycle, and payment webhooks.
- **Operational UX**: PASS. UI references in `ui/` and Egyptian POS design system drive
  desktop/tablet, dense operational layouts, RTL/Arabic mirroring, loading/error states,
  receipt/kitchen printing, offline badges, and recovery messaging.
- **Dependency Control**: PASS WITH APPROVAL REQUIRED. The user plan requests packages not
  currently present: `maatwebsite/excel`, barcode generation, PDF generation, PWA plugin,
  `sql.js`, PayMob/Stripe clients, and possibly Reverb. These must be approved before
  implementation and can be deferred or replaced with existing primitives where feasible.

## Project Structure

### Documentation (this feature)

```text
specs/master/
|-- spec.md
|-- plan.md
|-- research.md
|-- data-model.md
|-- quickstart.md
`-- contracts/
    |-- api.md
    `-- ui.md
```

### Source Code (repository root)

```text
Modules/
|-- Shared/
|   |-- app/Concerns/
|   |-- app/DTOs/
|   |-- app/Enums/
|   |-- app/Exceptions/
|   |-- app/Repositories/
|   `-- tests/
|-- Tenancy/
|-- Identity/
|-- Inventory/
|-- Sales/
|-- Customers/
|-- Restaurant/
|-- Reports/
|-- Settings/
|-- Offline/
`-- Billing/
    |-- app/
    |   |-- Actions/
    |   |-- DTOs/
    |   |-- Http/Controllers/
    |   |-- Http/Middleware/
    |   |-- Http/Requests/
    |   |-- Models/
    |   |-- Policies/
    |   `-- Repositories/
    |-- database/
    |   |-- factories/
    |   |-- migrations/
    |   `-- seeders/
    |-- resources/
    |   |-- js/
    |   `-- views/
    |-- routes/
    |-- tests/
    `-- module.json
app/
`-- Models/User.php
routes/
|-- web.php
|-- api.php
`-- settings.php
resources/js/
|-- actions/
`-- routes/
```

**Structure Decision**: Use `nwidart/laravel-modules` for POS SaaS business domains. Keep
business logic, requests, controllers, repositories, DTOs, models, migrations, routes,
views, and tests inside the owning module. Use root `app/Models/User.php` only where the
starter authentication model must remain global. Keep generated Wayfinder outputs in the
existing root frontend helper locations.

## Complexity Tracking

> Fill only if Constitution Check has violations that must be justified.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| New import/export/PDF/PWA/offline/payment dependencies require approval | The user plan explicitly includes spreadsheet import/export, barcode/PDF print, PWA, browser-local DB, and payment gateways | Existing stack does not provide spreadsheet parsing, PDF layout, service worker generation, browser SQLite, or hosted payment checkout primitives |

## Phase 0: Research

Completed in [research.md](research.md). All technical clarifications have decisions,
rationales, and alternatives recorded. No unresolved clarification markers remain.

## Phase 1: Design & Contracts

Completed design artifacts:

- [data-model.md](data-model.md): tenant-owned entities, relationships, validation rules,
  and state transitions.
- [contracts/api.md](contracts/api.md): web/API endpoint contracts for auth, inventory,
  POS, customers, restaurant, reports, settings, offline sync, and subscriptions.
- [contracts/ui.md](contracts/ui.md): screen-level UI contracts mapped to `ui/` references.
- [quickstart.md](quickstart.md): verification path for planning, implementation setup, and
  focused tests.

## Post-Design Constitution Check

- **Tenant Isolation**: PASS. Data model marks global vs tenant-owned entities and requires
  tenant boundary tests.
- **Action Architecture**: PASS. Contracts map endpoint behavior to Action and Repository
  ownership.
- **Testing**: PASS. Quickstart and contracts define focused PHPUnit and frontend checks.
- **Permissions & Audit**: PASS. Contracts identify permission boundaries and audit-bearing
  operations.
- **Operational UX**: PASS. UI contract maps supplied screens, responsive constraints,
  RTL/Arabic, loading states, printing, and offline behavior.
- **Dependency Control**: PASS WITH TRACKED APPROVAL. New dependency candidates are isolated
  in research and complexity tracking for explicit approval during implementation.
