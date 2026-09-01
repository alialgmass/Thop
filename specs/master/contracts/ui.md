# UI Contracts: POS SaaS Phase 1

The `ui/` folder provides visual references. Implementations must reuse the existing
Laravel/Inertia/Vue component system while matching the operational behavior and density of
the supplied screens.

## Global Design

- Design source: `ui/egyptian_pos_standard/DESIGN.md`.
- Primary color: Deep Indigo; success: Emerald; warning: Amber; error: Red.
- Typography: IBM Plex Sans Arabic for bilingual harmony; numeric POS values use clear
  Latin glyphs.
- Shape: 4px to 8px radii for operational controls and panels.
- Density: compact tables and 8px/16px spacing for admin/POS productivity.
- RTL: Arabic variants mirror navigation, POS cart/catalog placement, table alignment, and
  directional icons.

## Public Registration And Pricing

References: `ui/public_registration_pricing`, `ui/public_registration_pricing_arabic`.

Contract: first viewport presents tenant signup and plan comparison, with monthly/annual
toggle, 14-day trial messaging, validation errors, loading state, and Arabic layout support.

## Admin Dashboard

References: `ui/admin_dashboard`, `ui/admin_dashboard_arabic`,
`ui/admin_dashboard_updated_nav`.

Contract: dashboard shows today's sales, transaction count, average sale, top product,
30-day sales chart, payment breakdown, and low-stock table. Cashiers see reduced metrics.

## Product And Inventory

References: `ui/product_inventory_management`, `ui/product_inventory_arabic`,
`ui/category_management`, `ui/product_variants_management`.

Contract: product index supports search/filter/pagination, category sidebar, media
thumbnail, status badges, low-stock indicators, import actions, and variant management tab.

## POS Cashier Interface

References: `ui/pos_cashier_interface`, `ui/pos_cashier_interface_arabic`,
`ui/detailed_payment_modal`.

Contract: POS has fixed product catalog and persistent cart layout, autofocus search,
category tabs, product tiles at least 80x80px, multi-invoice tabs, scanner handling,
customer chip, permission-aware discounts, and payment modal for cash/card/mixed/deferred.

## Customers And Debts

References: `ui/customer_debt_management`, `ui/detailed_customer_profile`.

Contract: customer search and ledger use compact tables, overdue color coding, debt payment
modal, payment history disclosure, and WhatsApp reminder affordance.

## Restaurant And Kitchen

References: `ui/restaurant_table_map`, `ui/kitchen_display_system_kds`.

Contract: table map shows available/occupied/reserved states, active order shortcuts, add
table action, order cards, KDS cards with mark-ready action, and polling fallback state.

## Reports

Reference: `ui/detailed_profit_report`.

Contract: report pages have date/category/cashier filters, role-sensitive metrics, compact
tables, charts, export buttons, and explicit empty/loading/error states.

## Settings And Offline

References: `ui/settings_invoice_tax_configuration`, `ui/offline_sync_pwa_status`.

Contract: settings provide invoice branding preview, tax controls, printer controls, KDS
output toggle, and offline/PWA status badges. Offline UI must expose pending sync count,
last sync time, failed item detail, and retry action.
