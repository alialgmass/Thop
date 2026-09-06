# THOB API Reference — `/api/v1/`

Hand-maintained. Seeded from `php artisan route:list` + the FormRequest / Resource / Policy
source on **2026-09-06**. Covers **completed phases only: 0, 1, 2, 4, 5, 6** plus the
always-public Taxonomy read endpoints (Phase 1 dependency).

Phase 3 (Catalog CRUD: `POST/PATCH/DELETE /products*`, `/admin/products*`) is **Partial /
Phase 3 Pending** — those routes exist in code but the phase is not implemented or tested.
They are listed at the bottom under "Not covered" and have **no** request/response contract here.

---

## 1. Conventions

### 1.1 Response envelope

Every `/api/v1/` response (success and most errors) is:

```json
{ "custom_code": 2000, "status": true, "message": "…", "body": { … }, "info": "…" }
```

- `status` = `true` for any 2xx, `false` otherwise.
- `body` = the payload object, or on validation error a `{ "field": ["message", …] }` map.
- `message` is localized from `Accept-Language` (`ar` | `en`) by the Core `api.language` middleware.
- Paginated lists nest Laravel's `{ data, links, meta }` under a body key
  (e.g. `body.products.data[]`, `body.products.meta.total`).

### 1.2 `custom_code` registry

| code | HTTP | `status` | Meaning | Where |
|---|---|---|---|---|
| 2000 | 2xx | true | success | `ApiResponse` default |
| 4000 | 400 | false | validation failed / malformed request | every `BaseRequest` failure, some inline guards |
| 4001 | 401 | false | unauthenticated (missing/invalid bearer token) | Core `Handler::unauthenticated` |
| 4003 | 403 | false | not authorized (generic) | `ExceptionAction::unauthorized` (rare) |
| 4031 | 403 | false | authorization denied (policy / `abort(403)`) — **enveloped for every module** | Core `Handler` |
| 4040 | 404 | false | resource not found (route-model-binding miss / `abort(404)`) — **enveloped for every module** | Core `Handler`; also `POST /favorites` target-missing |
| 4091 | 409 | false | phone already registered / already has active subscription | OTP request, Register, Subscribe |
| 4092 | 409 | false | account type already set / verification request not pending | account-type, verification approve-reject |
| 4221 | 422 | false | invalid OTP (no active request / expired / locked / mismatch) **or** subscription not active on update | OTP verify, subscription update |
| 4222 | 422 | false | login failed (bad phone or password) / plan ↔ account-type mismatch | Login, Subscribe |
| 4224 | 422 | false | invalid onboarding handoff token | Register, Password reset |
| 4231 | 422 | false | inquiry/RFQ limit reached (`body.inquiry_limit`) | Inquiry store, RFQ store |
| 4290 | 429 | false | inline rate limit (OTP request/verify, login, inquiry/RFQ create) — `body.throttle` | `ThrottlesByKey` |
| 4291 | 429 | false | `throttle.phone` middleware limit — `body.throttle` | Core `CustomThrottleRequests` (not currently on any route) |
| 5031 | 503 | false | OTP delivery failed (`body.phone`) | OTP request |

### 1.3 Envelope coverage

Every `/api/v1/` response is enveloped, including failures:

- **403** (`$this->authorize()`, `abort(403)`, `abort_unless`, policy denials) → `{ custom_code: 4031, status: false, message, body: [], info }`, HTTP 403. Core `Handler`.
- **404** (route-model-binding miss, `abort(404)`) → `{ custom_code: 4040, status: false, … }`, HTTP 404. Core `Handler`.
- **Validation** (all modules, Subscriptions included) → `{ custom_code: 4000, status: false, body: { field: [msg] } }`, HTTP 400.
- **401**, **429**, **503**, **409**, **422** — see the registry above.

Method-not-allowed (405) and unhandled 500s keep Laravel's native shape by design.

### 1.4 Auth

- **Bearer token**: `Authorization: Bearer <sanctum plain-text token>`. Obtained from
  `POST /auth/register` or `POST /auth/login` (`body.token`).
- **`auth:sanctum`** — token required; missing/invalid → 401 / 4001.
- **`optional.sanctum`** (Search only) — token optional; never rejects; a resolved user is
  used only for zero-result attribution.
- **public** — no token.
- **`signed`** (verification document download) — Laravel signed URL; bad/absent signature → 403.
- **Roles**: the only application role is `admin` (Spatie). Everything else is Account Type +
  ownership, enforced by Laravel Policies.

---

## 2. Flat endpoint table

Legend — Auth: `pub`=public, `S`=`auth:sanctum`, `S?`=`optional.sanctum`, `sig`=signed URL.
Plan: entitlement key checked server-side, or `—`.

| # | Method | Path | Auth | Role / ownership | Plan | Phase | US / BR |
|---|---|---|---|---|---|---|---|
| 1 | POST | `/auth/otp/request` | pub | — | — | 0 | US-ACC-01 |
| 2 | POST | `/auth/otp/verify` | pub | — | — | 0 | US-ACC-01, SEC-NFR-02 |
| 3 | POST | `/auth/register` | pub | valid registration_token | — | 0 | US-ACC-01, BR-ACC-01 |
| 4 | POST | `/auth/login` | pub | — | — | 0 | US-ACC-07 |
| 5 | POST | `/auth/password/reset` | pub | valid reset_token | — | 0 | US-ACC-07 |
| 6 | GET | `/auth/account-types` | pub | — | — | 0 | US-ACC-02 |
| 7 | GET | `/auth/me` | S | self | — | 0 | US-ACC-07 |
| 8 | POST | `/auth/logout` | S | self | — | 0 | US-ACC-07 |
| 9 | POST | `/auth/account-type` | S | self, not yet chosen | — | 0 | US-ACC-02, BR-ACC-02 |
| 10 | POST | `/businesses` | S | importer/wholesaler/retailer, no profile yet | — | 1 | US-ACC-03, BR-ACC-03 |
| 11 | GET | `/businesses/{business}` | S | any authed (private fields: owner/admin) | — | 1 | US-ACC-03, US-ACC-05 |
| 12 | PATCH | `/businesses/{business}` | S | owner or admin | — | 1 | US-ACC-03 |
| 13 | POST | `/businesses/{business}/verification-documents` | S | owner, business not yet verified | — | 1 | US-ACC-04, SEC-NFR-05 |
| 14 | GET | `/businesses/{business}/verification-documents/{document}` | S + sig | owner or admin | — | 1 | US-ACC-04, SEC-NFR-01 |
| 15 | POST | `/businesses/{business}/verification-request` | S | owner | — | 1 | US-ACC-04 |
| 16 | GET | `/businesses/{business}/verification-status` | S | owner or admin | — | 1 | US-ACC-04, US-ACC-05 |
| 17 | GET | `/admin/verification-requests` | S | admin | — | 1 | US-ADM-01 |
| 18 | POST | `/admin/verification-requests/{verificationRequest}/approve` | S | admin | — | 1 | US-ADM-01, US-ADM-09, BR-ADM-01 |
| 19 | POST | `/admin/verification-requests/{verificationRequest}/reject` | S | admin | — | 1 | US-ADM-01, US-ADM-09, BR-ADM-01 |
| 20 | GET | `/subscription-plans` | S | any authed | — | 2 | US-SUB-01/02 |
| 21 | POST | `/subscriptions` | S | own business profile, no active sub | — | 2 | US-SUB-05/07, BR-SUB-01 |
| 22 | GET | `/subscriptions/{subscription}` | S | owner or admin | — | 2 | US-SUB-04 |
| 23 | GET | `/subscriptions/{subscription}/usage` | S | owner or admin | — | 2 | US-SUB-04 |
| 24 | PATCH | `/subscriptions/{subscription}` | S | owner or admin | — | 2 | US-SUB-05, BR-SUB-02 |
| 25 | GET | `/products` | S? | — (public discovery) | featured_products (ranking only) | 4 | US-SRC-01/02/03/04/10, BR-SRC-01/02 |
| 26 | GET | `/products/{product}` | S? | — (buyer-visible only, else 404) | — | 4 | US-SRC-05 |
| 27 | GET | `/businesses` | S? | — (business accounts only) | featured_supplier (ranking only) | 4 | US-SRC-07, US-SRC-10 |
| 28 | GET | `/businesses/{business}/catalog` | S? | — | — | 4 | US-SRC-06 |
| 29 | GET | `/favorites` | S | self (list is self-scoped) | — | 5 | US-SRC-08, US-BUY-02 |
| 30 | POST | `/favorites` | S | self | — | 5 | US-SRC-08, BR-FAV-01 |
| 31 | DELETE | `/favorites/{favorite}` | S | owner | — | 5 | US-SRC-08 |
| 32 | GET | `/compare` | S | self | — | 5 | US-SRC-09, US-BUY-05, BR-CMP-01 |
| 33 | GET | `/inquiries` | S | buyer or seller (self-scoped by `?role=`) | — | 6 | US-INQ-06/07, US-ANL-03 |
| 34 | POST | `/inquiries` | S | wholesaler/retailer | `inquiry_limit` (seller side always; buyer side if plan defines it) | 6 | US-INQ-01, BR-INQ-01/02 |
| 35 | GET | `/inquiries/{inquiry}` | S | party to the inquiry | — | 6 | US-INQ-01 |
| 36 | PATCH | `/inquiries/{inquiry}` | S | the seller | — | 6 | US-INQ-07 |
| 37 | POST | `/inquiries/{inquiry}/rfqs` | S | the inquiry's buyer | `inquiry_limit` (re-checked, not re-incremented) | 6 | US-INQ-02, BR-INQ-02 |
| 38 | GET | `/rfqs/{rfq}` | S | party to the RFQ | — | 6 | US-INQ-02/03 |
| 39 | POST | `/rfqs/{rfq}/quotations` | S | the RFQ's addressed seller | — | 6 | US-INQ-03 |
| 40 | POST | `/inquiries/{inquiry}/reports` | S | party to the inquiry | — | 6 | US-INQ-09 |
| 41 | GET | `/taxonomy/governorates` | pub | — | — | 1 | US-ACC-03 (dependency) |
| 42 | GET | `/taxonomy/fabric-types` | pub | — | — | 1/4 | US-SRC-04 (dependency) |
| 43 | GET | `/taxonomy/materials` | pub | — | — | 1/4 | dependency |
| 44 | GET | `/taxonomy/colors` | pub | — | — | 1/4 | dependency |
| 45 | GET | `/taxonomy/units` | pub | — | — | 1/4 | dependency |

**45 documented endpoints** across the six completed phases + taxonomy.

---

## 3. Endpoint detail

Request bodies below are the FormRequest `rules()` verbatim. `→` marks the success body shape.

### Phase 0 — Auth  (`Modules/Auth`)

#### 1. `POST /auth/otp/request`
```
phone   : required, string, Egyptian mobile (01[0125]xxxxxxxx / +20… / 20…)
purpose : required, in: registration | password_reset
```
Behaviour: throttled 3/min per phone (`4290`). `registration` + phone exists → `409/4091`
(`body.phone`). `password_reset` + phone unknown → `200` with empty body (enumeration-safe,
nothing sent). Delivery failure → `503/5031` (`body.phone`).
→ `body: {}` · message "A verification code has been sent."

#### 2. `POST /auth/otp/verify`
```
phone   : required, string, Egyptian mobile
code    : required, string
purpose : required, in: registration | password_reset
```
Throttled 5/min per phone (`4290`). Wrong/expired/locked/no-request → `422/4221`
(`body.code`, message differs per case; 3 wrong attempts → locked).
→ `body: { "registration_token": "<enc>" }` **or** `{ "reset_token": "<enc>" }` (per `purpose`).

#### 3. `POST /auth/register`
```
registration_token   : required, string
password             : required, string, confirmed, default password rules
password_confirmation : required (by `confirmed`)
email                : nullable, email, max:255, unique:users,email
language             : nullable, in: ar | en   (default ar)
```
Bad/expired token → `422/4224` (`body.registration_token`). Phone already a user → `409/4091`.
→ **HTTP 201**, `body: { token, user: {id,phone,email,account_type:null,status:"pending_type_selection",language}, next_onboarding_step:"account_type_selection" }`

#### 4. `POST /auth/login`
```
phone    : required, string
password : required, string
```
Throttled 5/min per `phone|ip` (`4290`). Unknown phone or bad password → `422/4222`
(`body.phone`, generic message — no user enumeration).
→ `body: { token, user, next_onboarding_step: "account_type_selection"|"business_profile"|"none" }`

#### 5. `POST /auth/password/reset`
```
reset_token          : required, string
password             : required, string, confirmed, default password rules
password_confirmation : required
```
Bad/expired token → `422/4224` (`body.reset_token`). On success **all** the user's bearer
tokens are revoked. → `body: {}` · message "Password updated. Please log in."

#### 6. `GET /auth/account-types`
No input. → `body: { account_types: [ { value, label, description, requires_business_profile } × 4 ] }`
(labels/descriptions follow `Accept-Language`).

#### 7. `GET /auth/me`  · `auth:sanctum`
→ `body: { user: { id, phone, email, account_type, status, language } }`

#### 8. `POST /auth/logout`  · `auth:sanctum`
Deletes the current access token. → `body: {}`

#### 9. `POST /auth/account-type`  · `auth:sanctum`
```
account_type : required, in: importer | wholesaler | retailer | customer
```
Already chosen → `409/4092` (`body.account_type`). On success sets `status = active`.
→ `body: { user, next_onboarding_step: "business_profile"|"none" }`

---

### Phase 1 — Business Profile / Verification / Audit  (`Modules/Businesses`, `Modules/Verification`)

#### 10. `POST /businesses`  · `auth:sanctum`
```
company_name             : required, string, max:255
activity                 : required, string, max:255
governorate_id           : required, integer, exists in governorates (is_active=true)
address                  : required, string, max:500
contact_person           : required, string, max:255
contact_channels         : nullable, array
contact_channels.*.type  : required_with:contact_channels, string, max:50
contact_channels.*.value : required_with:contact_channels, string, max:255
```
Customer account → `422` `body.account_type` (`customer_forbidden`). Second profile →
`422` `body.user_id` (`already_exists`). Both carry `custom_code 4000`.
→ **HTTP 201**, `body: { business: { id, company_name, activity, governorate_id, verified:false } }`
(owner also sees `address, contact_person, contact_channels, verification_status, onboarded_by_admin, created_at, updated_at`).

#### 11. `GET /businesses/{business}`  · `auth:sanctum`
No authorize gate. Non-owner sees the **public subset only**
(`id, company_name, activity, governorate_id, verified`), **plus `contact_channels`** when the
seller's active plan grants the `contact_info_visible` entitlement (US-INQ-05; default off).
Owner/admin additionally see `address, contact_person, contact_channels, verification_status,
onboarded_by_admin, timestamps`.
→ `body: { business: {…} }`

#### 12. `PATCH /businesses/{business}`  · `auth:sanctum` · owner or admin (else 403)
Same fields as #10, each scalar prefixed `sometimes`. → `body: { business: {…} }`

#### 13. `POST /businesses/{business}/verification-documents`  · `auth:sanctum` · owner, business not verified (else 403)
```
document_type_id : required, integer, exists in document_types (is_active=true)
file             : required, file, mimes:<config verification.accepted_mimes>,
                   mimetypes:<config verification.accepted_mimetypes>,
                   max:<config verification.max_file_size_kb> KB
```
Stored on the private `verification` disk at `business/{id}/{uuid}.{ext}`.
→ **HTTP 201**, `body: { document: { id, document_type_id, mime_type, size, original_name, uploaded_at, download_url } }`
`download_url` is a temporary **signed** route (`verification.download_link_ttl_seconds`).

#### 14. `GET /businesses/{business}/verification-documents/{document}`  · `auth:sanctum` + `signed` · owner or admin
Streams the raw file (not an envelope). Wrong business for the document → 404. Bad signature → 403.

#### 15. `POST /businesses/{business}/verification-request`  · `auth:sanctum` · owner only (admin cannot)
No body. Zero documents on the open request → `422` `body.documents` (`custom_code 4000`).
On success: business → `Pending`, `submitted_at` set, event `VerificationSubmitted`.
→ `body: { verification: { business_id, verification_status, verified, rejection_reason, reviewed_at, documents: [...] } }`

#### 16. `GET /businesses/{business}/verification-status`  · `auth:sanctum` · owner or admin
→ `body: { verification: {…same shape as #15…} }`

#### 17. `GET /admin/verification-requests`  · `auth:sanctum` · admin
Paginated queue of `pending` + submitted requests, newest first.
→ `body: { verification_requests: { data: [ { id, business_account_id, status, submitted_at, reviewed_by, reviewed_at, rejection_reason, documents:[…] } ], links, meta } }`

#### 18. `POST /admin/verification-requests/{verificationRequest}/approve`  · `auth:sanctum` · admin
No body. Request not `pending`/submitted → `409/4092` (`body.status`). On success: request
→ `Approved`, business → `Verified`, audit row `verification.approved`, event `VerificationApproved`.
→ `body: { verification_request: {…} }`

#### 19. `POST /admin/verification-requests/{verificationRequest}/reject`  · `auth:sanctum` · admin
```
reason : required, string, min:3, max:1000
```
Same `409/4092` guard. On success: request → `Rejected` + reason, business → `Rejected`,
audit row `verification.rejected`, event `VerificationRejected`.
→ `body: { verification_request: {…} }`

---

### Phase 2 — Subscriptions  (`Modules/Subscriptions`)

#### 20. `GET /subscription-plans`  · `auth:sanctum`
Query: `?account_type=importer|wholesaler|retailer` (optional filter). Only `is_active` plans.
→ `body: { plans: [ { id, name, account_type, price|null, billing_cycle, entitlements:[{key,value}] } ] }`

#### 21. `POST /subscriptions`  · `auth:sanctum` · caller must own a business profile (else `403/4031`)
```
plan_id       : required, integer, exists in subscription_plans (is_active=true)
trial_ends_at : nullable, date, after:now
```
Validation errors → enveloped `400/4000` (like every other module).
Active subscription already exists → `409/4091`. Plan's `account_type` ≠ caller's → `422/4222`.
→ **HTTP 201**, `body: { subscription: { id, business_account_id, plan:{id,name,account_type,price,billing_cycle,entitlements:{key:value}}, status, current_period_end, trial_ends_at, is_trial, created_at } }`

#### 22. `GET /subscriptions/{subscription}`  · `auth:sanctum` · owner or admin (else `403/4031`)
→ `body: { subscription: {…} }`

#### 23. `GET /subscriptions/{subscription}/usage`  · `auth:sanctum` · owner or admin (else `403/4031`)
→ `body: { subscription_id, plan, usage: { <entitlement_key>: { limit, current|null } }, current_period_end, trial_ends_at }`

#### 24. `PATCH /subscriptions/{subscription}`  · `auth:sanctum` · owner or admin (else `403/4031`)
```
action  : required, in: upgrade | downgrade | cancel
plan_id : required_with action upgrade/downgrade, integer, exists in subscription_plans (is_active=true)
```
Validation error → enveloped `400/4000`. Subscription not `active` → `422/4221`.
`upgrade` → old row cancelled, **new active** row on the target plan (immediate).
`downgrade` → `notes.pending_plan_id` set, current row untouched until `current_period_end`.
`cancel` → `notes.cancel_at_period_end = true`, current row untouched.
→ `body: { subscription: {…} }` (the new row for upgrade, the current row otherwise).

---

### Phase 4 — Search  (`Modules/Search`) · all `optional.sanctum`

#### 25. `GET /products`
```
search               : nullable, string, max:120
sort                 : nullable, in: relevance | price_asc | price_desc | newest | supplier_rating
page                 : nullable, integer, min:1
per_page             : nullable, integer, 1..50   (default 20, hard cap 50)
filters.fabric_type_id      : nullable, integer
filters.material_id         : nullable, integer
filters.governorate_id      : nullable, integer
filters.business_account_id : nullable, integer
filters.color_id            : nullable  (scalar or color_id[]; each integer)
filters.width_cm_min / _max : nullable, integer, min:0
filters.price_min / _max    : nullable, numeric, min:0
filters.availability        : nullable, boolean   (true → quantity_available > 0)
filters.moq_max             : nullable, integer, min:1  (matches moq IS NULL OR moq <= n)
```
Default sort: `relevance` when `search` is present, else `newest`. `supplier_rating` degrades
to "verified first, then newest" (no rating system in R1). Only buyer-visible products
(published, active owner, **and the owner's subscription not lapsed** — BR-SUB-03).
Featured items get a within-page boost (`relevance`/`newest` only) and `featured: true`.
Zero results are logged to `search_logs`.
→ `body: { products: { data: [ ProductCard ], links, meta } }`
ProductCard: `id, name_ar, name_en, description, width_cm, weight_gsm, price, price_on_contact, currency, unit, moq, quantity_available, featured, fabric_type, material, governorate, colors, primary_image, supplier:{id,company_name,verified}`

#### 26. `GET /products/{product}`  (numeric id only)
Non-buyer-visible → **404**. → `body: { product: ProductDetail } }`
ProductDetail = ProductCard fields + `price_tiers[], media[], supplier.governorate,
actions:{contact:{supplier_id,product_id}, request_quotation:{…}}` (minus `primary_image`).
`supplier.contact_channels` is present only when the seller's plan grants
`contact_info_visible` (US-INQ-05; default off).

#### 27. `GET /businesses`
```
search   : nullable, string, max:120
page     : nullable, integer, min:1
per_page : nullable, integer, 1..50
filters.governorate_id      : nullable, integer
filters.verification_status : nullable, in: <VerificationStatus values>
filters.specialty           : nullable, string, max:120   (full-text on search_text)
```
Business accounts only (customers never appear); suspended-owner suppliers excluded.
Ordered `company_name` asc, `id` desc; featured suppliers boosted + `featured: true`.
→ `body: { suppliers: { data: [ { id, company_name, activity, governorate, verified, featured } ], links, meta } }`

#### 28. `GET /businesses/{business}/catalog`  (numeric id only)
Same query contract as #25, scoped to one business. No zero-result logging.
→ `body: { products: { data: [ ProductCard ], links, meta } }`

---

### Phase 5 — Favorites / Comparison  (`Modules/Favorites`, `Modules/Comparison`)

#### 29. `GET /favorites`  · `auth:sanctum`
Query: `?type=product|supplier` (optional filter). Self-scoped, newest first, paginated.
→ `body: { favorites: { data: [ { id, type, favoritable_id, created_at, item: ProductCard|SupplierCard } ], links, meta } }`

#### 30. `POST /favorites`  · `auth:sanctum`
```
type : required, in: product | supplier
id   : required, integer, min:1
```
Target not found → **404 / 4040** (enveloped).
Duplicate → **HTTP 200** (not 201), same row (idempotent, `BR-FAV-01`).
→ **HTTP 201** (first time), `body: { favorite: {…} }`

#### 31. `DELETE /favorites/{favorite}`  · `auth:sanctum` · owner (else 403)
→ `body: {}`

#### 32. `GET /compare`  · `auth:sanctum`
```
type : required, in: product | supplier
ids  : required, array, 1..4  (accepts ?ids=1,2,3 comma string or ids[]; de-duplicated)
ids.* : integer, min:1
```
5th id → `400/4000` (`body.ids`, message "…up to 4…"). Non-visible / unknown ids are
returned in `missing_ids`, not errored.
→ `body: { type, items: [ ProductDetail | SupplierCard ], missing_ids: [ int ] }`

---

### Phase 6 — Inquiries / RFQ / Quotation / Reporting  (`Modules/Inquiries`)

#### 33. `GET /inquiries`  · `auth:sanctum`
Query: `?role=buyer|seller` (default `buyer`), `?lead_status=new|in_progress|done|not_completed`
(raw equality, not validated), `?page=`. `role=seller` with no business profile → empty list.
→ `body: { inquiries: { data: [ { id, buyer_id, seller_business_id, product_id, message, lead_status, last_activity_at, created_at, updated_at } ], links, meta } }`
`last_activity_at` = latest of {inquiry touched, RFQ raised, quotation sent} (US-ANL-03).
_This **is** the "Lead Management screen" (US-ANL-03) — spec §11 was corrected to point here; there is no `/businesses/{id}/leads` route._

#### 34. `POST /inquiries`  · `auth:sanctum` · wholesaler or retailer (else 403)
```
seller_business_id : required_without:product_id, nullable, integer, exists:business_accounts,id
product_id         : required_without:seller_business_id, nullable, integer, exists:products,id
message            : required, string, max:2000
```
Throttled `create_per_minute` (default 10) per user → `429/4290`. `product_id` given with a
mismatching `seller_business_id` → `422` `body.seller_business_id`. Seller past `inquiry_limit`
→ `422/4231` `body.inquiry_limit`. Buyer-side limit only enforced if the buyer's plan defines
`inquiry_limit` (none do yet). On success: `lead_status = new`, `inquiry_count` incremented,
event `InquiryCreated`.
→ **HTTP 201**, `body: { inquiry: {…} }`

#### 35. `GET /inquiries/{inquiry}`  · `auth:sanctum` · buyer or seller-owner (else 403)
→ `body: { inquiry: {…} }`

#### 36. `PATCH /inquiries/{inquiry}`  · `auth:sanctum` · the seller only (else 403)
```
lead_status : required, in: new | in_progress | done | not_completed
```
→ `body: { inquiry: {…} }`
_(Spec §11 previously said `.../status`; corrected to match this path.)_

#### 37. `POST /inquiries/{inquiry}/rfqs`  · `auth:sanctum` · the inquiry's buyer (else 403)
```
product_id     : required, integer, exists:products,id  (must belong to the inquiry's seller)
quantity       : required, integer, min:1
color_id       : nullable, integer, exists:colors,id
needed_by_date : required, date
```
Throttled with the same key as #34. Foreign product → `422` `body.product_id`. Seller past
`inquiry_limit` → `422/4231` (re-checked, **not** re-incremented). Below MOQ → `below_moq: true`,
still `201`. Event `RfqCreated`.
→ **HTTP 201**, `body: { rfq: { id, inquiry_id, product_id, quantity, color_id, needed_by_date, below_moq, created_at, quotations:[…] } }`
_(Spec §11 previously said `.../rfq` singular; corrected to match this path.)_

#### 38. `GET /rfqs/{rfq}`  · `auth:sanctum` · party to the RFQ (else 403)
→ `body: { rfq: {…, quotations:[ { id, rfq_id, price, availability_note, valid_until, expired, created_at } ] } }`

#### 39. `POST /rfqs/{rfq}/quotations`  · `auth:sanctum` · the RFQ's addressed seller (else 403)
```
price             : required, numeric, min:0
availability_note : nullable, string, max:255
valid_until       : required, date
```
Event `QuotationReceived`. `expired` is computed from `valid_until` on read.
→ **HTTP 201**, `body: { quotation: {…} }`

#### 40. `POST /inquiries/{inquiry}/reports`  · `auth:sanctum` · party to the inquiry (else 403)
```
reason : required, string, max:1000
```
Durable record only (no moderation workflow — Phase 9).
→ **HTTP 201**, `body: { report: { id, reportable_type, reportable_id, reason, created_at } }`

---

### Taxonomy (public read — Phase 1 / Phase 4 dependency)  (`Modules/Taxonomy`)

#### 41–45. `GET /taxonomy/{governorates|fabric-types|materials|colors|units}`
No input, no pagination. Active rows only, ordered by `name_en`.
→ `body: { <key>: [ { id, slug, name, name_ar, name_en, hex? } ] }`
(`hex` only on `colors`; `name` follows `Accept-Language`.) Write verbs → **405**.

---

## 4. Not covered here (Phase 3 Catalog — **Partial / Phase 3 Pending**)

These routes are registered (`Modules/Catalog/routes/api.php`) but the phase is **not
implemented or tested**. No request/response contract is documented; do not build a client
or Postman request against them until Phase 3 lands.

```
GET    /api/v1/products/mine
GET    /api/v1/products/mine/{product}
POST   /api/v1/products
PATCH  /api/v1/products/{product}
DELETE /api/v1/products/{product}
POST   /api/v1/products/{product}/duplicate
PATCH  /api/v1/products/{product}/status
GET    /api/v1/admin/products
POST   /api/v1/admin/products/{product}/approve
POST   /api/v1/admin/products/{product}/reject
POST   /api/v1/admin/products/{product}/hide
```

Search (Phase 4) reads the `products` / `product_media` / `product_price_tiers` tables that
Phase 3 created, and `ProductCardResource` / `ProductDetailResource` live in the Catalog
module — that read path **is** covered. Product *writes* are not.

Also out of scope (later phases): Chat (7), Notifications (8), the rest of Admin (9),
Orders/Payments/Shipping (R2–R4).
