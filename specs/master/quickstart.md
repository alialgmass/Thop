# Quickstart: POS SaaS Phase 1 Planning Artifacts

## 1. Review The Plan

Read these files in order:

1. `specs/master/spec.md`
2. `specs/master/plan.md`
3. `specs/master/research.md`
4. `specs/master/data-model.md`
5. `specs/master/contracts/api.md`
6. `specs/master/contracts/ui.md`

## 2. Confirm Dependency Approvals

Before implementation, explicitly approve or defer these requested dependencies:

- Spreadsheet import/export: `maatwebsite/excel` or equivalent.
- Barcode generation package.
- PDF generation package for barcode grids.
- PWA plugin for Vite.
- Browser local database: `sql.js` or IndexedDB fallback.
- PayMob and Stripe integration clients.
- Realtime/KDS support such as Reverb, with polling fallback.

## 3. Generate Tasks

Run the Spec Kit task workflow after reviewing the plan:

```powershell
$speckit-tasks
```

Expected output: `specs/master/tasks.md` grouped by independently testable user stories.

## 4. Implementation Verification Commands

Use focused checks during implementation:

```powershell
php artisan test --compact tests/Feature/Auth/RegistrationTest.php
php artisan test --compact --filter=ProcessSaleAction
php artisan test --compact --filter=TenantIsolation
vendor/bin/pint --dirty --format agent
npm run types:check
```

Run frontend build or dev only when UI work needs browser verification:

```powershell
npm run build
```

## 5. UI Verification

Compare implemented screens against `ui/` references:

- Public registration/pricing
- Admin dashboard
- Product inventory and categories
- POS cashier interface and payment modal
- Customer debt management and profile
- Restaurant table map and KDS
- Profit report
- Settings invoice/tax configuration
- Offline sync/PWA status

Verify desktop at 1280px+, tablet at 768px, and Arabic/RTL variants where relevant.
