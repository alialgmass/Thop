---
description: "Executable task plan for POS SaaS Phase 1 using nwidart/laravel-modules"
---

# Tasks: POS SaaS Phase 1

**Input**: Design documents from `D:\Projects\pos\specs\master`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/`, `quickstart.md`
**Architecture**: Modular Laravel using `nwidart/laravel-modules`; business domains live under `Modules/`
**Tests**: Mandatory per specification and constitution

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel because it touches different files and has no dependency on another unfinished task
- **[Story]**: User story label, required only in user-story phases
- Every task includes an exact file path

## Phase 1: Setup (Module Skeleton And Shared Conventions)

**Purpose**: Establish the modular architecture and shared contracts before domain work.

- [X] T001 Create `Modules/Shared/module.json` and `Modules/Shared/app/Providers/SharedServiceProvider.php` to bootstrap shared concerns, enums, DTOs, repositories, exceptions, and tests
- [X] T002 Create domain module service providers in `Modules/Identity/app/Providers/IdentityServiceProvider.php`, `Modules/Inventory/app/Providers/InventoryServiceProvider.php`, `Modules/Sales/app/Providers/SalesServiceProvider.php`, `Modules/Customers/app/Providers/CustomersServiceProvider.php`, `Modules/Restaurant/app/Providers/RestaurantServiceProvider.php`, `Modules/Reports/app/Providers/ReportsServiceProvider.php`, `Modules/Settings/app/Providers/SettingsServiceProvider.php`, `Modules/Offline/app/Providers/OfflineServiceProvider.php`, and `Modules/Billing/app/Providers/BillingServiceProvider.php`
- [X] T003 Create `Modules/Shared/app/Concerns/BelongsToTenant.php` for tenant global scope and tenant assignment
- [X] T004 Create `Modules/Shared/app/Support/CurrentTenant.php` value object for current tenant binding
- [X] T005 [P] Create `Modules/Shared/app/Enums/PaymentMethod.php` with Cash, Card, Mixed, and Deferred values
- [X] T006 [P] Create `Modules/Shared/app/Enums/StockMovementType.php` with purchase, sale, return, adjustment, and offline_sync values
- [X] T007 [P] Create `Modules/Shared/app/Enums/SubscriptionStatus.php`, `Gateway.php`, `GatewayPaymentStatus.php`, and `OfflineSyncStatus.php` with subscription, PayMob/Stripe, gateway lifecycle, and offline sync row result values
- [X] T008 [P] Create `Modules/Shared/tests/Feature/ModuleRoutesLoadTest.php` to assert enabled module route files are registered
- [X] T009 Update root `routes/web.php` to avoid domain routes and defer POS SaaS routes to module route service providers
- [X] T010 Verify `nwidart/laravel-modules` autoload and module namespace configuration in `composer.json` and `config/modules.php`, updating only deterministic module settings required for `Modules\` classes

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Tenant resolution, auth integration, permissions, and cross-module rules that block all user stories.

- [X] T011 Create `Modules/Tenancy/module.json` for tenant foundation module metadata
- [X] T012 Create `Modules/Tenancy/app/Models/Tenant.php` with settings cast, plan relation, user relation, factory support, and explicit global model exemption from `BelongsToTenant`
- [X] T013 Create `Modules/Tenancy/database/migrations/2026_05_10_000001_create_tenants_table.php` with `name`, `settings`, `plan_id`, and `trial_ends_at`
- [X] T014 Create `Modules/Tenancy/app/Http/Middleware/SetCurrentTenant.php` to bind `CurrentTenant` and reject inactive users
- [X] T015 Create `Modules/Tenancy/app/Providers/TenancyServiceProvider.php` to register middleware aliases and tenant bindings
- [X] T016 Create `Modules/Identity/module.json` for user and permission module metadata
- [X] T017 Update `app/Models/User.php` to use `BelongsToTenant`, `HasRoles`, `is_active`, and tenant relation
- [X] T018 Create `Modules/Identity/database/migrations/2026_05_10_000002_add_tenant_and_active_fields_to_users_table.php`, `Modules/Tenancy/database/factories/TenantFactory.php`, and tenant-aware updates in `database/factories/UserFactory.php` for `tenant_id` and `is_active`
- [X] T019 Create `Modules/Identity/database/seeders/TenantPermissionSeeder.php` with admin, manager, cashier roles and permissions
- [X] T020 Create `Modules/Identity/app/Policies/UserPolicy.php` for tenant-scoped user management and self-disable prevention
- [X] T021 Create `Modules/Shared/tests/Feature/TenantIsolationTest.php` proving two tenants cannot read each other's tenant-owned records
- [X] T022 Create `Modules/Identity/tests/Feature/PermissionBoundaryTest.php` proving cashier cannot access profit report, refunds, or user management
- [X] T023 Create `Modules/Shared/tests/Unit/BelongsToTenantTest.php` for tenant auto-assignment and tenant query scope
- [X] T024 Create `Modules/Shared/app/Repositories/BaseTenantRepository.php` with tenant-aware query helpers
- [X] T025 Create `Modules/Shared/app/DTOs/PaginatedResultData.php` for consistent paginated module responses
- [X] T026 Create `Modules/Shared/app/Exceptions/UserFacingException.php` for safe UI/API error messages

**Checkpoint**: Tenant, auth, permission, shared enum, repository, and exception foundations are ready.

## Phase 3: User Story 1 - Tenant Foundation And Secure Access (Priority: P1)

**Goal**: Tenant registration, login, employee management, active-user enforcement, and tenant isolation.
**Independent Test**: Register a tenant, verify email, log in, create a cashier, deny restricted access, disable/re-enable the cashier, and prove cross-tenant isolation.

### Tests for User Story 1

- [X] T027 [P] [US1] Create tenant registration feature test in `Modules/Tenancy/tests/Feature/TenantRegistrationTest.php`
- [X] T028 [P] [US1] Create login and lockout feature test in `Modules/Identity/tests/Feature/LoginSessionTest.php`
- [X] T029 [P] [US1] Create user CRUD and role assignment feature test in `Modules/Identity/tests/Feature/UserManagementTest.php`
- [X] T030 [P] [US1] Create user active toggle feature test in `Modules/Identity/tests/Feature/UserActiveToggleTest.php`

### Implementation for User Story 1

- [X] T031 [US1] Create `Modules/Tenancy/app/Actions/RegisterTenantAction.php` to transactionally create tenant, admin user, roles, trial, and verification notification
- [X] T032 [US1] Create `Modules/Tenancy/app/DTOs/RegisterTenantData.php` for tenant registration input
- [X] T033 [US1] Create `Modules/Tenancy/app/Http/Requests/RegisterTenantRequest.php` for store name, owner name, email, password, and confirmation validation
- [X] T034 [US1] Create `Modules/Tenancy/app/Http/Controllers/RegisteredTenantController.php` with create/store and check-inbox responses
- [X] T035 [US1] Create Inertia registration page in `Modules/Tenancy/resources/js/pages/RegisterTenant.vue` using `ui/public_registration_pricing` references
- [X] T036 [US1] Create `Modules/Identity/app/Actions/AuthenticateUserAction.php` wrapping Laravel auth attempt, remember-me, and rate-limit behavior
- [X] T037 [US1] Create `Modules/Identity/app/Http/Controllers/AuthenticatedSessionController.php` for login and logout orchestration
- [X] T038 [US1] Create `Modules/Identity/app/Actions/CreateTenantUserAction.php` for employee creation and role sync
- [X] T039 [US1] Create `Modules/Identity/app/Actions/UpdateTenantUserAction.php` for employee profile and role updates
- [X] T040 [US1] Create `Modules/Identity/app/Actions/ToggleTenantUserActiveAction.php` with self-disable prevention
- [X] T041 [US1] Create `Modules/Identity/app/Repositories/UserRepository.php` for tenant-scoped user queries
- [X] T042 [US1] Create `Modules/Identity/app/Http/Controllers/UserController.php` with index, store, update, destroy, and toggle-active methods
- [X] T043 [US1] Create user management page in `Modules/Identity/resources/js/pages/Users/Index.vue` using `ui/user_management_roles` reference
- [X] T044 [US1] Define module routes in `Modules/Tenancy/routes/web.php` for registration and `Modules/Identity/routes/web.php` for login/users
- [X] T045 [US1] Run focused auth tests documented in `Modules/Tenancy/tests/Feature/TenantRegistrationTest.php` and `Modules/Identity/tests/Feature/UserManagementTest.php`

## Phase 4: User Story 2 - Product And Inventory Operations (Priority: P1)

**Goal**: Product, category, variants, import, stock, purchase, alerts, media, and barcode management.
**Independent Test**: Create catalog data, import valid/invalid rows, record stock purchase, trigger alert, and preserve historical sale compatibility.

### Tests for User Story 2

- [X] T046 [P] [US2] Create product CRUD feature test in `Modules/Inventory/tests/Feature/ProductCrudTest.php`
- [X] T047 [P] [US2] Create category reorder feature test in `Modules/Inventory/tests/Feature/CategoryReorderTest.php`
- [X] T048 [P] [US2] Create product import feature test in `Modules/Inventory/tests/Feature/ProductImportTest.php`
- [X] T049 [P] [US2] Create purchase order stock movement test in `Modules/Inventory/tests/Feature/PurchaseOrderTest.php`
- [X] T050 [P] [US2] Create low-stock notification test in `Modules/Inventory/tests/Feature/LowStockNotificationTest.php`

### Implementation for User Story 2

- [X] T051 [US2] Create `Modules/Inventory/module.json` for inventory module metadata
- [X] T052 [US2] Create migrations for categories, products, product_variants, purchase_orders, purchase_order_items, stock_movements, and barcode media references in `Modules/Inventory/database/migrations/`
- [X] T053 [US2] Create inventory models in `Modules/Inventory/app/Models/Category.php`, `Product.php`, `ProductVariant.php`, `PurchaseOrder.php`, `PurchaseOrderItem.php`, and `StockMovement.php` with `BelongsToTenant`, factories, enum casts, and Media Library image/barcode collections where applicable
- [X] T054 [US2] Create `Modules/Inventory/app/Enums/ProductStatus.php` and cast all inventory status/type fields, including product status and stock movement type, to backed enums
- [X] T055 [US2] Create `Modules/Inventory/app/Repositories/ProductRepository.php` and `Modules/Inventory/app/Repositories/StockRepository.php`
- [X] T056 [US2] Create `Modules/Inventory/app/Actions/CreateProductAction.php`, `UpdateProductAction.php`, `DeleteProductAction.php`, and `GenerateProductBarcodeAction.php`
- [X] T057 [US2] Create `Modules/Inventory/app/Actions/ReorderCategoriesAction.php` and `ReorderProductsAction.php`
- [X] T058 [US2] Create `Modules/Inventory/app/Actions/ImportProductsAction.php` with preview and confirm modes
- [X] T059 [US2] Create `Modules/Inventory/app/Actions/UpdateStockAction.php` and `RecordPurchaseAction.php` with transactional stock movement writes
- [X] T060 [US2] Create `Modules/Inventory/app/Jobs/CheckLowStockJob.php` and `Modules/Inventory/app/Notifications/LowStockNotification.php`
- [X] T061 [US2] Create inventory requests in `Modules/Inventory/app/Http/Requests/ProductRequest.php`, `CategoryRequest.php`, `ImportProductsRequest.php`, and `PurchaseOrderRequest.php`
- [X] T062 [US2] Create controllers in `Modules/Inventory/app/Http/Controllers/ProductController.php`, `CategoryController.php`, `ProductImportController.php`, `PurchaseOrderController.php`, and `BarcodeController.php`
- [X] T063 [US2] Create product pages in `Modules/Inventory/resources/js/pages/Products/Index.vue`, `Edit.vue`, `Import.vue`, `Variants.vue`, `Barcode.vue`, and `BarcodeBulkPrint.vue`
- [X] T064 [US2] Create category page in `Modules/Inventory/resources/js/pages/Categories/Index.vue` using `ui/category_management` reference
- [X] T065 [US2] Create module routes in `Modules/Inventory/routes/web.php` and `Modules/Inventory/routes/api.php`
- [ ] T066 [US2] Gate spreadsheet, barcode, and PDF package changes through explicit approval before editing `composer.json`, then wire approved package configuration in `config/excel.php`, `config/barcode.php`, or `config/dompdf.php` as applicable

## Phase 5: User Story 3 - Cashier POS Checkout (Priority: P1)

**Goal**: Search, barcode scan, cart, discounts, payments, sale processing, stock deduction, and receipts.
**Independent Test**: Search 10,000 products under 200ms, scan barcode into cart, complete cash/card/mixed sales, verify stock, and print receipt.

### Tests for User Story 3

- [X] T067 [P] [US3] Create POS search performance test in `Modules/Sales/tests/Feature/PosSearchPerformanceTest.php`
- [X] T068 [P] [US3] Create sale processing unit test in `Modules/Sales/tests/Unit/ProcessSaleActionTest.php`
- [X] T069 [P] [US3] Create discount permission feature test in `Modules/Sales/tests/Feature/DiscountPermissionTest.php`
- [X] T070 [P] [US3] Create receipt rendering feature test in `Modules/Sales/tests/Feature/ReceiptTest.php`

### Implementation for User Story 3

- [X] T071 [US3] Create `Modules/Sales/module.json` for POS and sales module metadata
- [X] T072 [US3] Create migrations for sales, sale_items, sale_returns, and sale_return_items in `Modules/Sales/database/migrations/` with enum-backed `payment_method`, `status`, and `refund_method` columns
- [X] T073 [US3] Create models in `Modules/Sales/app/Models/Sale.php`, `SaleItem.php`, `SaleReturn.php`, and `SaleReturnItem.php` with `BelongsToTenant`, factories, and enum casts
- [X] T074 [US3] Create `Modules/Sales/app/Enums/SaleStatus.php`, `Modules/Sales/app/Enums/RefundMethod.php`, `Modules/Sales/app/Repositories/SaleRepository.php`, and `Modules/Sales/app/Repositories/PosProductSearchRepository.php`
- [X] T075 [US3] Create `Modules/Sales/app/Actions/ProcessSaleAction.php` with stock lock, sale creation, item snapshots, payment validation, and `SaleCompleted` event
- [X] T076 [US3] Create `Modules/Sales/app/Actions/ApplyDiscountAction.php` with role permission and max discount validation
- [X] T077 [US3] Create `Modules/Sales/app/Http/Controllers/PosController.php`, `SaleController.php`, `DiscountController.php`, and `ReceiptController.php`
- [X] T078 [US3] Create `Modules/Sales/resources/js/stores/useCartStore.ts` for cart items, totals, tax, discounts, and stock limits
- [X] T079 [US3] Create `Modules/Sales/resources/js/composables/useBarcodeScanner.ts` for rapid keystroke scanner detection
- [X] T080 [US3] Create POS page and components in `Modules/Sales/resources/js/pages/Pos/Index.vue`, `ProductGrid.vue`, `CartSidebar.vue`, and `PaymentModal.vue`
- [X] T081 [US3] Create print receipt view in `Modules/Sales/resources/views/receipts/show.blade.php` and printer composable in `Modules/Sales/resources/js/composables/usePrinter.ts`
- [X] T082 [US3] Create module routes in `Modules/Sales/routes/web.php` and `Modules/Sales/routes/api.php`
- [X] T083 [US3] Generate Wayfinder route helpers after route registration and update imports in `Modules/Sales/resources/js/pages/Pos/Index.vue`

## Phase 6: User Story 4 - Customers, Debts, Returns, And Loyalty (Priority: P2)

**Goal**: Customer CRUD, POS linking, deferred sales, debt payments, returns, and loyalty.
**Independent Test**: Create customer from POS, complete deferred sale, pay debt partially and fully, process return, and verify balances/stock.

### Tests for User Story 4

- [X] T084 [P] [US4] Create customer CRUD and POS search test in `Modules/Customers/tests/Feature/CustomerCrudTest.php`
- [X] T085 [P] [US4] Create deferred sale debt test in `Modules/Customers/tests/Feature/DeferredSaleDebtTest.php`
- [X] T086 [P] [US4] Create debt payment action test in `Modules/Customers/tests/Unit/RecordDebtPaymentActionTest.php`
- [X] T087 [P] [US4] Create return processing feature test in `Modules/Customers/tests/Feature/ProductReturnTest.php`

### Implementation for User Story 4

- [X] T088 [US4] Create `Modules/Customers/module.json` for customer and debt module metadata
- [X] T089 [US4] Create migrations for customers, customer_debts, debt_payments, and loyalty_transactions in `Modules/Customers/database/migrations/` with enum-backed debt status and payment method columns
- [X] T090 [US4] Create models in `Modules/Customers/app/Models/Customer.php`, `CustomerDebt.php`, `DebtPayment.php`, and `LoyaltyTransaction.php` plus `Modules/Customers/app/Enums/CustomerDebtStatus.php` with `BelongsToTenant`, factories, and enum casts
- [X] T091 [US4] Create `Modules/Customers/app/Repositories/CustomerRepository.php` and `DebtRepository.php`
- [X] T092 [US4] Create `Modules/Customers/app/Actions/CreateCustomerAction.php`, `RecordDebtAction.php`, `RecordDebtPaymentAction.php`, and `ApplyLoyaltyPointsAction.php`
- [X] T093 [US4] Create `Modules/Sales/app/Actions/ProcessReturnAction.php` and integrate it with `Modules/Inventory/app/Actions/UpdateStockAction.php`
- [X] T094 [US4] Create controllers in `Modules/Customers/app/Http/Controllers/CustomerController.php`, `CustomerSearchController.php`, and `DebtPaymentController.php`
- [X] T095 [US4] Create customer pages in `Modules/Customers/resources/js/pages/Customers/Index.vue`, `Show.vue`, and `DebtManagement.vue`
- [X] T096 [US4] Extend `Modules/Sales/resources/js/pages/Pos/PaymentModal.vue` with deferred payment tab and customer-required validation
- [X] T097 [US4] Create module routes in `Modules/Customers/routes/web.php` and `Modules/Customers/routes/api.php`

## Phase 7: User Story 5 - Restaurant Orders (Priority: P2)

**Goal**: Tables, orders, kitchen output, KDS, and checkout order to paid sale.
**Independent Test**: Open table order, add notes, send to kitchen, mark ready, checkout, and free table.

### Tests for User Story 5

- [X] T098 [P] [US5] Create table lifecycle feature test in `Modules/Restaurant/tests/Feature/TableLifecycleTest.php`
- [X] T099 [P] [US5] Create order kitchen workflow test in `Modules/Restaurant/tests/Feature/KitchenWorkflowTest.php`
- [X] T100 [P] [US5] Create order checkout action test in `Modules/Restaurant/tests/Unit/CheckoutOrderActionTest.php`

### Implementation for User Story 5

- [X] T101 [US5] Create `Modules/Restaurant/module.json` for restaurant module metadata
- [X] T102 [US5] Create migrations for tables, orders, and order_items in `Modules/Restaurant/database/migrations/`
- [X] T103 [US5] Create models in `Modules/Restaurant/app/Models/Table.php`, `Order.php`, and `OrderItem.php` with `BelongsToTenant`, factories, and enum casts
- [X] T104 [US5] Create enums in `Modules/Restaurant/app/Enums/TableStatus.php` and `Modules/Restaurant/app/Enums/OrderStatus.php`
- [X] T105 [US5] Create repositories in `Modules/Restaurant/app/Repositories/TableRepository.php` and `OrderRepository.php`
- [X] T106 [US5] Create actions in `Modules/Restaurant/app/Actions/OpenOrderAction.php`, `SendToKitchenAction.php`, `MarkOrderReadyAction.php`, and `CheckoutOrderAction.php`
- [X] T107 [US5] Create controllers in `Modules/Restaurant/app/Http/Controllers/TableController.php`, `OrderController.php`, and `KitchenController.php`
- [X] T108 [US5] Create pages in `Modules/Restaurant/resources/js/pages/Tables/Index.vue`, `Orders/Show.vue`, and `Kitchen/Index.vue`
- [X] T109 [US5] Create kitchen ticket view in `Modules/Restaurant/resources/views/kitchen-ticket.blade.php`
- [X] T110 [US5] Create module routes in `Modules/Restaurant/routes/web.php` and `Modules/Restaurant/routes/api.php`

## Phase 8: User Story 6 - Reports And Settings (Priority: P2)

**Goal**: Dashboard, reports, exports, invoice settings, printer settings, and tax.
**Independent Test**: Seed sample sales/returns/debts, verify role-sensitive dashboard/report output, export report, update settings, and confirm receipt settings.

### Tests for User Story 6

- [X] T111 [P] [US6] Create dashboard report feature test in `Modules/Reports/tests/Feature/DashboardReportTest.php`
- [X] T112 [P] [US6] Create profit permission report test in `Modules/Reports/tests/Feature/ProfitReportAuthorizationTest.php`
- [X] T113 [P] [US6] Create report export test in `Modules/Reports/tests/Feature/ReportExportTest.php`
- [X] T114 [P] [US6] Create settings receipt/tax test in `Modules/Settings/tests/Feature/InvoiceTaxSettingsTest.php`

### Implementation for User Story 6

- [X] T115 [US6] Create `Modules/Reports/module.json` and `Modules/Settings/module.json` for reports and settings module metadata
- [X] T116 [US6] Create report repositories in `Modules/Reports/app/Repositories/SalesReportRepository.php`, `ProductReportRepository.php`, `ProfitReportRepository.php`, `CashierReportRepository.php`, and `DebtReportRepository.php`
- [X] T117 [US6] Create report actions in `Modules/Reports/app/Actions/GenerateDashboardMetricsAction.php`, `GenerateTopProductsReportAction.php`, `GenerateProfitReportAction.php`, `GenerateCashierReportAction.php`, and `GenerateDebtReportAction.php`
- [X] T118 [US6] Create export contract and implementations in `Modules/Reports/app/Exports/ExportableReport.php` and `Modules/Reports/app/Exports/ReportExport.php`
- [X] T119 [US6] Create controllers in `Modules/Reports/app/Http/Controllers/DashboardController.php`, `TopProductsReportController.php`, `ProfitReportController.php`, `CashierReportController.php`, `DebtReportController.php`, and `ReportExportController.php`
- [X] T120 [US6] Create report pages in `Modules/Reports/resources/js/pages/Dashboard.vue`, `ProductsTop.vue`, `Profit.vue`, `Cashiers.vue`, and `Debts.vue`
- [X] T121 [US6] Create settings DTO and action in `Modules/Settings/app/DTOs/TenantSettingsData.php` and `Modules/Settings/app/Actions/UpdateTenantSettingsAction.php`
- [X] T122 [US6] Create settings controllers in `Modules/Settings/app/Http/Controllers/InvoiceSettingsController.php`, `TaxSettingsController.php`, and `PrinterSettingsController.php`
- [X] T123 [US6] Create settings pages in `Modules/Settings/resources/js/pages/Invoice.vue`, `Tax.vue`, and `Printer.vue`
- [X] T124 [US6] Create module routes in `Modules/Reports/routes/web.php` and `Modules/Settings/routes/web.php`

## Phase 9: User Story 7 - Offline POS And SaaS Billing (Priority: P3)

**Goal**: PWA installability, local data mirror, offline sale queue, idempotent sync, plans, billing, gateway webhooks, and subscription enforcement.
**Independent Test**: Install PWA, sync data, record offline sales, reconnect and sync once, then subscribe and enforce plan/trial status.

### Tests for User Story 7

- [X] T125 [P] [US7] Create offline sync integration test in `Modules/Offline/tests/Feature/OfflineSyncTest.php`
- [X] T126 [P] [US7] Create duplicate offline local ID test in `Modules/Offline/tests/Feature/OfflineIdempotencyTest.php`
- [X] T127 [P] [US7] Create subscription flow test in `Modules/Billing/tests/Feature/SubscriptionFlowTest.php`
- [X] T128 [P] [US7] Create subscription middleware test in `Modules/Billing/tests/Feature/CheckSubscriptionTest.php`

### Implementation for User Story 7

- [X] T129 [US7] Create `Modules/Offline/module.json` and `Modules/Billing/module.json` for offline and billing module metadata
- [X] T130 [US7] Create billing migrations for plans and subscriptions in `Modules/Billing/database/migrations/` with global plans and tenant-owned subscriptions using enum-backed status, gateway, and gateway payment fields
- [X] T131 [US7] Add `offline_local_id` unique index migration in `Modules/Sales/database/migrations/2026_05_10_000099_add_offline_local_id_to_sales_table.php`
- [X] T132 [US7] Create billing models in `Modules/Billing/app/Models/Plan.php` and `Modules/Billing/app/Models/Subscription.php` plus `Modules/Billing/database/seeders/PlanSeeder.php` with factories, global plan exemption, tenant-owned subscription scope, enum casts, and Basic/Advanced/Pro limits
- [X] T133 [US7] Create billing actions in `Modules/Billing/app/Actions/CreatePaymentIntentAction.php`, `HandlePaymentWebhookAction.php`, and `RenewSubscriptionAction.php`
- [X] T134 [US7] Create `Modules/Billing/app/Http/Middleware/CheckSubscription.php` for trial, active subscription, billing redirect, and read-only enforcement
- [X] T135 [US7] Create billing controllers in `Modules/Billing/app/Http/Controllers/PricingController.php`, `SubscriptionController.php`, and `WebhookController.php`
- [X] T136 [US7] Create billing pages in `Modules/Billing/resources/js/pages/Pricing.vue` and `Billing.vue`
- [X] T137 [US7] Create offline server action and controller in `Modules/Offline/app/Actions/ProcessOfflineSaleAction.php` and `Modules/Offline/app/Http/Controllers/OfflineSyncController.php` using `OfflineSyncStatus` enum responses and sale `offline_local_id` idempotency
- [X] T138 [US7] Create sync data controller in `Modules/Offline/app/Http/Controllers/SyncDataController.php`
- [X] T139 [US7] Create offline frontend store in `Modules/Offline/resources/js/stores/useOfflineStore.ts` with online/offline tracking, pending sync count, and install prompt state
- [X] T140 [US7] Create browser DB wrapper in `Modules/Offline/resources/js/lib/db.ts` with sql.js/IndexedDB fallback methods for products, categories, customers, offline sales, and sync status
- [X] T141 [US7] Create sync composable in `Modules/Offline/resources/js/composables/useOfflineSync.ts` and PWA install composable in `Modules/Offline/resources/js/composables/useInstallPrompt.ts`
- [X] T142 [US7] Create offline status page/component in `Modules/Offline/resources/js/pages/OfflineStatus.vue` and install banner component in `Modules/Offline/resources/js/components/InstallAppBanner.vue`
- [X] T143 [US7] Create module routes in `Modules/Offline/routes/api.php`, `Modules/Billing/routes/web.php`, and `Modules/Billing/routes/api.php`
- [ ] T144 [US7] Gate PWA, sql.js, PayMob, Stripe, and Reverb package changes through explicit approval before editing `package.json` or `composer.json`, then implement `public/offline.html`, PWA manifest/icons, and service worker configuration in `vite.config.ts`

## Phase 10: Polish & Cross-Cutting Concerns

**Purpose**: Final checks across modules and generated frontend route helpers.

- [X] T145 Regenerate Wayfinder outputs after all module routes are registered and update `resources/js/actions/` and `resources/js/routes/`
- [X] T146 Run PHP formatting for touched PHP files with `vendor/bin/pint --dirty --format agent`
- [X] T147 Run focused full feature test set with `php artisan test --compact Modules/Tenancy/tests Modules/Identity/tests Modules/Inventory/tests Modules/Sales/tests`
- [X] T148 Run focused remaining module tests with `php artisan test --compact Modules/Customers/tests Modules/Restaurant/tests Modules/Reports/tests Modules/Settings/tests Modules/Offline/tests Modules/Billing/tests`
- [X] T149 Run frontend type check with `npm run types:check`
- [X] T150 Update `.env.example` with approved dependency environment variables for PayMob, Stripe, cache, queue, broadcasting, and PWA in `.env.example`
- [X] T151 Verify migration freshness for module migrations under `Modules/*/database/migrations/` with `php artisan migrate:fresh --seed --no-interaction`
- [X] T152 Compare implemented screens under `Modules/*/resources/js/pages/` against `ui/` references across desktop, tablet, and RTL viewports

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Starts immediately.
- **Foundational (Phase 2)**: Depends on setup; blocks all user stories.
- **US1, US2, US3 (P1)**: Start after foundational; US3 depends on enough of US2 product/stock model work to process sales.
- **US4 (P2)**: Depends on US3 sale processing and US2 stock updates.
- **US5 (P2)**: Depends on US2 product catalog and US3 checkout/payment.
- **US6 (P2)**: Depends on US2, US3, US4, and US5 data for complete reporting/settings coverage.
- **US7 (P3)**: Depends on US1 tenant/subscription context and US3 sale processing.
- **Polish**: Depends on all selected user stories.

### User Story Completion Order

1. US1 Tenant Foundation And Secure Access
2. US2 Product And Inventory Operations
3. US3 Cashier POS Checkout
4. US4 Customers, Debts, Returns, And Loyalty
5. US5 Restaurant Orders
6. US6 Reports And Settings
7. US7 Offline POS And SaaS Billing

## Parallel Execution Examples

### US1

```text
T027, T028, T029, and T030 can be written in parallel.
T031-T035 can proceed in Tenancy while T036-T043 proceed in Identity after T011-T020.
```

### US2

```text
T046-T050 can be written in parallel.
T056 product actions, T057 reorder actions, T058 import actions, and T060 low-stock job can proceed in parallel after T052-T055.
```

### US3

```text
T067-T070 can be written in parallel.
T078-T080 frontend store/components can proceed while T075-T077 backend actions/controllers are implemented, then integrate with T083.
```

### US4

```text
T084-T087 can be written in parallel.
T092 customer/debt actions can proceed while T093 return action integrates with Sales and Inventory.
```

### US5

```text
T098-T100 can be written in parallel.
T108 table/order/KDS pages can proceed while T106 actions and T107 controllers are implemented.
```

### US6

```text
T111-T114 can be written in parallel.
T116 report repositories, T117 report actions, T121 settings DTO/action, and T123 settings pages can proceed in parallel by module boundary.
```

### US7

```text
T125-T128 can be written in parallel.
T133-T136 Billing work and T137-T142 Offline work can proceed in parallel after T129-T132.
```

## Implementation Strategy

### MVP First

1. Complete Phase 1 and Phase 2.
2. Complete US1 for tenant/auth/user safety.
3. Complete US2 product and stock foundation.
4. Complete US3 checkout for revenue workflow.
5. Validate MVP with focused module tests and UI checks.

### Incremental Delivery

1. Deliver US1 as secure tenant foundation.
2. Deliver US2 as inventory back office.
3. Deliver US3 as cashier-ready POS.
4. Add US4 customer debt workflows.
5. Add US5 restaurant workflows.
6. Add US6 owner reporting/settings.
7. Add US7 offline and SaaS billing.

### Quality Gates

- No user story is complete without its module tests passing.
- No tenant-owned module is complete without cross-tenant isolation coverage.
- No PHP changes are complete without Pint.
- No Vue/TypeScript changes are complete without `npm run types:check`.
- No new package is installed until explicit user approval is captured in the implementation conversation before editing `composer.json` or `package.json`.
