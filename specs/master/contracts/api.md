# API And Route Contracts: POS SaaS Phase 1

All protected endpoints require authentication, current tenant resolution, active user
status, and server-side authorization.

## Auth And Tenancy

- `GET /register`: tenant registration page.
- `POST /register`: creates tenant and admin user, sends verification email.
- `GET /email/verify`: verification notice.
- `POST /email/verification-notification`: resend verification email.
- `GET /login`, `POST /login`, `POST /logout`: Laravel/Fortify-compatible auth.
- Middleware: `SetCurrentTenant`, `verified`, `CheckSubscription` where required.

## Users And Permissions

- `GET /users`: list tenant users; requires `settings.manage`.
- `POST /users`: create tenant user and assign role.
- `PATCH /users/{user}`: update tenant user.
- `DELETE /users/{user}`: delete or deactivate user according to policy.
- `PATCH /users/{user}/toggle-active`: disable/enable, cannot target self.

## Products And Inventory

- `GET /products`: searchable/filterable paginated product index.
- `POST /products`: create product with optional media image.
- `PATCH /products/{product}`: update product.
- `DELETE /products/{product}`: soft delete product.
- `PATCH /products/reorder`: persist product/category sort order.
- `POST /categories`, `PATCH /categories/{category}`, `DELETE /categories/{category}`.
- `POST /categories/reorder`: transactionally update category order.
- `POST /products/import/preview`: parse file and return valid/error rows.
- `POST /products/import/confirm`: persist valid preview rows or dispatch import job.
- `POST /purchase-orders`: record purchase order and stock movements.
- `GET /notifications`, `PATCH /notifications/{id}/read`, `PATCH /notifications/read-all`.

## POS Checkout

- `GET /pos`: POS screen.
- `GET /api/pos/search?q=`: top 10 product/variant results by barcode exact match and name
  search; target under 200ms with 10,000 products.
- `POST /sales`: process sale through `ProcessSaleAction`.
- `GET /receipts/{sale}`: printable receipt view.
- `POST /discounts/preview`: optional server validation for discount permission/limits.

## Customers, Debts, Returns

- `GET /customers`, `POST /customers`, `PATCH /customers/{customer}`.
- `GET /api/customers/search?q=`: POS customer search by name or phone.
- `POST /customer-debts/{debt}/payments`: record partial/full payment.
- `GET /returns/search`: find sale by invoice number or customer.
- `POST /returns`: process return and restore stock.

## Restaurant

- `GET /tables`, `POST /tables`, `PATCH /tables/{table}`, `DELETE /tables/{table}`.
- `POST /tables/{table}/orders`: open order.
- `PATCH /orders/{order}/items`: add/edit/remove unsent items.
- `POST /orders/{order}/send-to-kitchen`: send new items to printer or KDS.
- `GET /kitchen`: KDS page.
- `PATCH /orders/{order}/ready`: mark ready.
- `POST /orders/{order}/checkout`: convert order to sale.
- `PATCH /orders/{order}/cancel`: cancel unpaid order.

## Reports And Settings

- `GET /dashboard`: role-sensitive sales dashboard.
- `GET /reports/products/top`: top products report.
- `GET /reports/profit`: profit report; requires `reports.profit`.
- `GET /reports/cashiers`: cashier performance report.
- `GET /reports/debts`: customer debt report.
- `GET /reports/{report}/export`: Excel export.
- `PATCH /settings/invoice`: update invoice branding and logo.
- `PATCH /settings/tax`: update tax settings.
- `PATCH /settings/kitchen`: printer or KDS setting.

## Offline And SaaS

- `GET /api/sync/data`: initial sync payload.
- `GET /api/sync/data?since={timestamp}`: incremental sync payload.
- `POST /api/offline/sync`: batch sync up to 50 offline sales.
- `GET /pricing`: public pricing page.
- `GET /billing`: subscription management page.
- `POST /billing/subscribe`: create gateway payment intention and redirect.
- `POST /webhooks/paymob`, `POST /webhooks/stripe`: verify webhook and renew subscription.

## Standard Error Contract

Validation and authorization errors return user-safe messages. JSON endpoints return:

```json
{
  "message": "Human readable error",
  "errors": {
    "field": ["Specific validation message"]
  }
}
```

Offline sync returns per-row statuses:

```json
[
  {"local_id": "uuid", "server_id": 123, "status": "ok", "message": null},
  {"local_id": "uuid", "server_id": null, "status": "error", "message": "Insufficient stock"}
]
```
