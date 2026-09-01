# Data Model: POS SaaS Phase 1

## Shared Rules

- Tenant-owned entities include `tenant_id` and use `BelongsToTenant`.
- Money fields use decimal precision appropriate for EGP, normally `decimal(12, 2)`.
- Status/type fields use PHP backed enums stored as small integer values.
- Historical sale/order item names, prices, costs, discounts, and tax values are snapshot
  fields and MUST NOT depend on current product edits.
- Global entities are limited to `plans` and framework-level metadata unless explicitly
  justified.

## Entities

### Tenant

Fields: `id`, `name`, `settings` JSON, `plan_id`, `trial_ends_at`, timestamps.

Relationships: has many users, products, categories, sales, customers, orders, tables,
subscriptions; belongs to current plan.

Validation: name required, max 80 for registration; settings validated by settings forms.

### User

Fields: `id`, `tenant_id`, `name`, `email`, `password`, `email_verified_at`,
`remember_token`, `is_active`, timestamps.

Relationships: belongs to tenant; has Spatie roles/permissions; has many sales and orders.

Rules: email unique globally or at least unique for login; inactive users cannot access
protected tenant workflows; users cannot disable their own active admin account.

### Product

Fields: `id`, `tenant_id`, `category_id`, `name`, `barcode`, `price`, `cost`, `stock_qty`,
`min_stock`, `status`, `has_variants`, timestamps, soft deletes.

Relationships: belongs to tenant/category; has many variants, sale items, order items, stock
movements; has one media image collection.

Validation: name required; barcode unique per tenant; price and cost non-negative; stock
quantity cannot go below zero during confirmed sale.

States: Active, Inactive, OutOfStock. Stock reaching zero sets OutOfStock unless manually
inactive.

### Category

Fields: `id`, `tenant_id`, `name`, `sort_order`, timestamps.

Relationships: belongs to tenant; has many products.

Rules: cannot delete while products exist; reorder updates in a transaction.

### ProductVariant

Fields: `id`, `tenant_id`, `product_id`, `name`, `sku`, `barcode`, `price`, `cost`,
`stock_qty`, timestamps.

Relationships: belongs to tenant/product; may appear in sale/order items.

Rules: adding a variant sets parent `has_variants = true`; variant barcode unique per tenant.

### PurchaseOrder And PurchaseOrderItem

PurchaseOrder fields: `id`, `tenant_id`, `supplier_name`, `total_cost`, `notes`,
timestamps.

PurchaseOrderItem fields: `id`, `purchase_order_id`, `product_id`, `variant_id`, `qty`,
`unit_cost`.

Rules: recording purchase inserts header/items, updates stock, recalculates weighted average
cost, and writes stock movements in one transaction.

### StockMovement

Fields: `id`, `tenant_id`, `product_id`, `variant_id`, `type`, `qty_change`, `qty_before`,
`qty_after`, `reference_type`, `reference_id`, `user_id`, timestamps.

Types: purchase, sale, return, adjustment, offline_sync.

Rules: every stock mutation creates a movement record.

### Sale And SaleItem

Sale fields: `id`, `tenant_id`, `user_id`, `customer_id`, `order_id`, `invoice_number`,
`payment_method`, `subtotal`, `discount_amount`, `tax_amount`, `total`, `paid_amount`,
`change_amount`, `status`, `offline_local_id`, timestamps.

SaleItem fields: `id`, `tenant_id`, `sale_id`, `product_id`, `variant_id`, `name`, `price`,
`cost`, `qty`, `discount`, `tax_amount`, `total`.

States: completed, refunded, partially_refunded, voided.

Rules: checkout validates stock inside transaction, creates sale/items, deducts stock, fires
`SaleCompleted`, and returns sale ID.

### Customer, CustomerDebt, DebtPayment

Customer fields: `id`, `tenant_id`, `name`, `phone`, `debt_balance`, `loyalty_points`,
timestamps.

CustomerDebt fields: `id`, `tenant_id`, `customer_id`, `sale_id`, `amount`, `paid_amount`,
`status`, `due_date`, timestamps.

DebtPayment fields: `id`, `tenant_id`, `debt_id`, `amount`, `payment_method`, `notes`,
`user_id`, timestamps.

Rules: phone unique per tenant; deferred sales create debts; debt payments update paid
amount, status, and customer balance in one transaction.

### SaleReturn And SaleReturnItem

SaleReturn fields: `id`, `tenant_id`, `sale_id`, `customer_id`, `user_id`, `refund_method`,
`total_refund`, timestamps.

SaleReturnItem fields: `id`, `tenant_id`, `sale_return_id`, `sale_item_id`, `product_id`,
`variant_id`, `qty`, `refund_amount`.

Rules: returns require permission, restore stock, update sale status, and produce a return
receipt.

### Restaurant Table, Order, OrderItem

Table fields: `id`, `tenant_id`, `name`, `capacity`, `status`, `position_x`, `position_y`,
timestamps.

Order fields: `id`, `tenant_id`, `table_id`, `user_id`, `order_number`, `status`, `notes`,
timestamps.

OrderItem fields: `id`, `tenant_id`, `order_id`, `product_id`, `variant_id`, `name`,
`price`, `qty`, `notes`, `sent_to_kitchen_at`.

States: table Available, Occupied, Reserved; order Open, Sent, Ready, Paid, Cancelled.

Rules: checkout converts order items to sale items and frees table.

### Plan And Subscription

Plan fields: `id`, `name`, `price_monthly`, `max_users`, `max_products`, `features` JSON,
timestamps.

Subscription fields: `id`, `tenant_id`, `plan_id`, `status`, `starts_at`, `ends_at`,
`gateway`, `gateway_subscription_id`, timestamps.

Rules: registration starts a 14-day trial; expired trial without subscription redirects to
billing or read-only mode; gateway webhooks create/renew subscriptions.

### OfflineSale

Client-side fields: `local_id`, `payload` JSON, `created_at`, `synced`.

Server-side sale field: `offline_local_id` unique per tenant.

Rules: sync batches up to 50 sales; duplicate `local_id` skips gracefully; successful rows
are marked synced client-side.

## Key State Transitions

- Product stock: purchase increases, sale decreases, return increases, zero stock marks
  OutOfStock.
- Customer debt: open -> partially_paid -> paid.
- Order: Open -> Sent -> Ready -> Paid or Cancelled.
- Table: Available -> Occupied -> Available, or Reserved -> Occupied -> Available.
- Subscription: trialing -> active -> past_due -> grace -> read_only/cancelled.
