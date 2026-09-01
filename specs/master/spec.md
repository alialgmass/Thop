# Feature Specification: POS SaaS Phase 1

**Feature Branch**: `master`
**Created**: 2026-05-10
**Status**: Draft
**Input**: User description: "POS SaaS System — Project Plan & Task Breakdown, Egyptian Market, Phase 1, using @ui references"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Tenant Foundation And Secure Access (Priority: P1)

Store owners can register a tenant, verify email, sign in, manage employees, assign roles,
disable former employees, and rely on tenant isolation across all protected workflows.

**Why this priority**: All later inventory, POS, customer, reporting, restaurant, offline,
and subscription stories depend on authenticated users and reliable tenant boundaries.

**Independent Test**: Register a tenant, verify email, log in, create a cashier, deny the
cashier access to profit reports, disable and re-enable the cashier, and prove another
tenant's products are not visible.

**Acceptance Scenarios**:

1. **Given** a visitor submits valid tenant registration data, **When** registration
   succeeds, **Then** a tenant and admin user are created in one transaction and a
   verification email is sent.
2. **Given** an inactive user attempts to access a protected route, **When** middleware
   resolves the tenant, **Then** the user is logged out and redirected with an account
   disabled message.
3. **Given** two tenants each own products, **When** Tenant A requests product data,
   **Then** Tenant B's records are not returned.

---

### User Story 2 - Product And Inventory Operations (Priority: P1)

Managers can create products, categories, variants, import products from spreadsheets,
record purchase orders, track stock movements, receive low-stock alerts, and print barcodes.

**Why this priority**: Product and stock data are the operational source for POS sales,
restaurant orders, reports, and offline sync.

**Independent Test**: Create a category and product with media, import valid and invalid
rows, record a purchase order, observe stock movement, trigger a low-stock notification,
and confirm a soft-deleted product remains available for historical sale items.

**Acceptance Scenarios**:

1. **Given** a manager creates a product with a barcode unique in the tenant, **When** the
   product is saved, **Then** it is searchable, tenant-scoped, and available to POS screens.
2. **Given** a purchase order contains 10 units for a product, **When** it is recorded,
   **Then** stock increases, weighted cost updates, and a stock movement is persisted.
3. **Given** an import file has five valid and two invalid product rows, **When** preview
   is confirmed, **Then** five products are created and two row errors are reported.

---

### User Story 3 - Cashier POS Checkout (Priority: P1)

Cashiers can search or scan products, add items to a cart, manage quantities, apply
permitted discounts, accept cash/card/mixed/deferred payments, complete sales, deduct stock,
and print or send receipts.

**Why this priority**: Checkout is the primary revenue workflow and must work before
advanced analytics or SaaS billing matter.

**Independent Test**: Load the POS, search a 10,000 product catalog under the target time,
scan a barcode into cart, complete cash/card/mixed sales, verify stock deduction, and print
a receipt with tenant invoice settings.

**Acceptance Scenarios**:

1. **Given** a product barcode is scanned rapidly and submitted with Enter, **When** the
   barcode exists and stock is available, **Then** the product is added to the cart.
2. **Given** a cashier completes a mixed payment, **When** cash plus card amount covers the
   total, **Then** a sale and sale items are created and stock is deducted transactionally.
3. **Given** a cashier lacks the `pos.discount` permission, **When** the POS renders,
   **Then** discount controls are hidden or disabled and server-side discount attempts fail.

---

### User Story 4 - Customers, Debts, Returns, And Loyalty (Priority: P2)

Cashiers and managers can create customers from POS, link customers to sales, record
deferred debts, collect partial or full debt payments, process returns, and track loyalty.

**Why this priority**: Customer credit and debt tracking are core to Egyptian retail
operations, but they depend on the base checkout workflow.

**Independent Test**: Create a customer from POS, complete a deferred sale, verify debt
balance, record partial and full payments, process a return, and confirm stock and balances
are adjusted.

**Acceptance Scenarios**:

1. **Given** a linked customer selects deferred payment, **When** checkout completes,
   **Then** a debt record is created and the customer debt balance increases.
2. **Given** an open debt has a remaining balance, **When** a partial payment is recorded,
   **Then** paid amount and customer balance are updated in one transaction.
3. **Given** a sale item is returned with authorization, **When** the return is processed,
   **Then** stock is restored and a return receipt is available.

---

### User Story 5 - Restaurant Orders (Priority: P2)

Restaurant tenants can manage tables, open orders, send tickets to kitchen printer or KDS,
mark orders ready, and checkout orders into paid sales.

**Why this priority**: Hospitality workflows are a major target market extension and reuse
the product catalog, payment, printing, and tenant infrastructure.

**Independent Test**: Create tables, open an order from an available table, add items and
notes, send only new items to kitchen, mark ready, checkout, and confirm the table returns
to available.

**Acceptance Scenarios**:

1. **Given** an available table is selected, **When** an order is opened, **Then** the table
   becomes occupied and the order screen opens.
2. **Given** an order is sent to kitchen, **When** KDS mode is enabled, **Then** the sent
   order appears on `/kitchen` by realtime channel or polling fallback.
3. **Given** an order is paid, **When** checkout succeeds, **Then** a sale is created,
   stock is deducted, order status becomes paid, and table status becomes available.

---

### User Story 6 - Reports And Settings (Priority: P2)

Admins and managers can view sales dashboards, product/profit/cashier/debt reports, export
reports, configure invoice branding, printer settings, and tax.

**Why this priority**: Owners need daily operational visibility and invoice compliance, but
accurate reports depend on sales, returns, stock, debt, and customer data.

**Independent Test**: Complete sample sales and returns, open dashboard and reports with
role-sensitive data, export a report, update invoice logo/tax/printer settings, and confirm
receipt output uses the updated settings.

**Acceptance Scenarios**:

1. **Given** a cashier requests profit reporting, **When** the permission is missing,
   **Then** access is denied and profit data is not returned.
2. **Given** a tenant enables 14% VAT, **When** a discounted sale is processed, **Then** tax
   is calculated on the post-discount subtotal and shown on the receipt.
3. **Given** a report is exported, **When** the file is downloaded, **Then** it includes
   title, date range, tenant name, and generation timestamp.

---

### User Story 7 - Offline POS And SaaS Billing (Priority: P3)

Tenants can install the app, sell offline with local queued sales, sync when online, trial
the product, select a plan, and pay subscription invoices through PayMob with Stripe fallback.

**Why this priority**: Offline reliability and SaaS billing complete the Phase 1 business
model, but they depend on stable POS, tenant, and sales processing contracts.

**Independent Test**: Install the PWA, sync local products/customers, record three offline
sales, reconnect, sync the queue idempotently, then complete a subscription checkout and
verify plan access.

**Acceptance Scenarios**:

1. **Given** the browser is offline, **When** a cashier completes a sale, **Then** the sale
   is stored locally with a UUID and appears in pending sync.
2. **Given** connectivity returns, **When** pending sales sync, **Then** successful sales are
   marked synced and duplicate `local_id` submissions are skipped safely.
3. **Given** a trial expires without subscription, **When** a protected route is requested,
   **Then** the tenant is redirected to billing or placed into read-only mode as configured.

### Edge Cases

- Cross-tenant ID tampering for products, customers, users, sales, orders, and reports.
- Race conditions where stock changes between cart selection and sale transaction.
- Duplicate offline sale sync requests with the same `local_id`.
- Spreadsheet import rows with duplicate barcodes, missing categories, invalid prices, or
  tenant-conflicting values.
- Thermal printer unavailable, popup blocked for WhatsApp, browser offline, or service
  worker cache miss.
- Arabic/RTL layout, long Arabic labels, and numeric alignment for EGP values.
- Payment gateway webhook retry, failed renewal, expired trial, and grace-period downgrade.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST create tenants and admin users atomically with email verification.
- **FR-002**: System MUST authenticate users with Laravel authentication primitives and
  rate-limit login attempts.
- **FR-003**: System MUST apply tenant scoping automatically to all tenant-owned domain data.
- **FR-004**: System MUST support tenant roles and permissions for admin, manager, and cashier.
- **FR-005**: System MUST manage products, categories, variants, media, stock, purchases,
  stock movements, imports, low-stock alerts, and barcodes.
- **FR-006**: System MUST provide POS search, barcode scanning, cart management, discounts,
  payments, receipts, and stock-safe sale processing.
- **FR-007**: System MUST manage customers, deferred debts, debt payments, returns, refunds,
  and loyalty points.
- **FR-008**: System MUST support restaurant tables, orders, kitchen output, and order
  checkout.
- **FR-009**: System MUST provide dashboard and reports for sales, top products, profit,
  cashier performance, and customer debt with role-sensitive visibility.
- **FR-010**: System MUST support invoice branding, printer setup, tax configuration, PWA
  installability, offline sales, sync, subscription plans, and payment gateway integration.

### Constitution Requirements

- **CR-001 Tenant Isolation**: Every tenant-owned entity MUST include `tenant_id` and
  cross-tenant feature tests unless explicitly documented as global.
- **CR-002 Action Architecture**: Business workflows MUST be implemented through Action
  classes and Repository classes with controllers limited to orchestration.
- **CR-003 Testing**: Unit tests MUST cover Action classes; feature tests MUST cover routes,
  validation, authorization, and tenant isolation; selected performance tests MUST cover POS
  search and offline sync.
- **CR-004 Permissions & Audit**: Discounts, refunds, user management, profit reports, debt
  payments, stock changes, and subscriptions MUST enforce permissions and persist audit
  signals.
- **CR-005 Operational UX**: UI MUST follow `ui/egyptian_pos_standard/DESIGN.md`, support
  RTL/Arabic, desktop/tablet use, loading states, user-facing errors, and print/offline
  recovery paths.

### Key Entities *(include if feature involves data)*

- **Tenant**: Store account, settings, trial, plan, subscription status.
- **User**: Tenant member with active flag, email verification, and roles.
- **Role/Permission**: Spatie RBAC records scoped per tenant where required.
- **Product/Category/ProductVariant**: Sellable catalog, grouping, barcode, price, cost,
  stock, status, media.
- **PurchaseOrder/PurchaseOrderItem/StockMovement**: Incoming stock and inventory audit.
- **Sale/SaleItem/SaleReturn/SaleReturnItem**: Checkout, receipt, stock deduction, return.
- **Customer/CustomerDebt/DebtPayment**: CRM, deferred credit, payment ledger, balance.
- **Table/Order/OrderItem**: Restaurant table state, kitchen lifecycle, checkout source.
- **Plan/Subscription**: SaaS plan limits, trial, renewal, gateway identifiers.
- **OfflineSale**: Client-side queued sale payload keyed by `local_id`.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: POS product search returns top 10 matches in under 200ms with 10,000 products.
- **SC-002**: Cash, card, and mixed checkout create sales and deduct stock within one
  database transaction.
- **SC-003**: Cross-tenant tests prove no tenant can read or mutate another tenant's domain
  records.
- **SC-004**: Dashboard and profit reports exclude unauthorized roles and correctly account
  for discounts, returns, tax, and deferred payments.
- **SC-005**: Offline sync creates each queued sale once and marks successful local records
  synced.
- **SC-006**: Core UI screens render usable desktop/tablet and RTL/Arabic variants matching
  the supplied `ui/` references.

## Assumptions

- Laravel 13 and Inertia v3 in the current repository supersede the user's older Laravel 11
  stack note.
- The `ui/` HTML and screenshot folders are visual references, not code to copy directly.
- PayMob is primary for Egypt subscription payments and Stripe remains fallback.
- POS terminal target is desktop/tablet browser first; phone support is secondary.
