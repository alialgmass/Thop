# THOB API Reference — `/api/v1/`

Hand-maintained. Seeded from `php artisan route:list` + the FormRequest / Resource / Policy
source on **2026-09-06**. Covers **completed phases only: 0, 1, 2, 4, 5, 6** plus the
always-public Taxonomy read endpoints (Phase 1 dependency).

Phase 3 (Catalog) is **partially done**: 3.1 products + 3.2 media + 3.3 CSV bulk import are
implemented and tested (documented in §4). XLSX import is still deferred (needs a reader
library sign-off, issue #16).

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
| 4093 | 409 | false | product not awaiting review (approve/reject/hide/request-edits on the wrong state) | Admin product review |
| 4094 | 409 | false | item already featured in that slot | Admin featured placement create |
| 4095 | 409 | false | account already suspended | Admin account suspend |
| 4096 | 409 | false | account not suspended (reactivate on a non-suspended account) | Admin account reactivate |
| 4097 | 409 | false | report already resolved/dismissed | Admin report resolve |
| 4032 | 403 | false | policy restriction, not a state conflict (e.g. target account is an admin) | Admin account suspend |
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

## 4. Phase 3 — Catalog (seller product management)

**3.1 Products + 3.2 Media + 3.3 CSV bulk import are implemented and tested**
(`Modules/Catalog`). XLSX import is still deferred (issue #16 — needs a reader library
sign-off). All routes below are `auth:sanctum`.

| Method | Path | Role / ownership | Notes |
|---|---|---|---|
| GET | `/products/mine` | owns a business profile | Paginated, all statuses, `ProductResource` (media, price tiers, internal status). |
| GET | `/products/mine/{product}` | owner or admin (else 403) | Single product, full resource. |
| POST | `/products` | importer/retailer (not wholesaler/customer) | Creates in **`draft`** (review off) or **`pending_review`** (review on). Never straight to `published` — a fresh product has no images. Enforces `product_limit` (BR-SEL-01) and price XOR (BR-SEL-03). Body: `name_ar`, `fabric_type_id`, `material_id`, `governorate_id`, `width_cm`, `weight_gsm`, `unit`, `price` XOR `price_on_contact`, `quantity_available`, `moq?`, `colors[]?`, `price_tiers[]?`, `draft?`. → **201**. |
| PATCH | `/products/{product}` | owner (else 403) | Partial update; re-syncs `colors` / `price_tiers` when present. |
| DELETE | `/products/{product}` | owner (else 403) | Soft delete (BR-SEL-04); decrements `product_count`. |
| POST | `/products/{product}/duplicate` | owner (else 403) | Copies as a `draft`; increments `product_count`. → **201**. |
| PATCH | `/products/{product}/status` | owner (else 403) | `status` ∈ `hidden` \| `unavailable` \| `published` \| `pending_review`. **Publishing goes through the gate**: ≥1 image (US-SEL-03) + price XOR → else **422** (`body.media` / `body.price`); with review on, `published` lands in `pending_review`. |
| **POST** | **`/products/{product}/media`** | **owner (else 403)** | **Multipart `file`** — one image, validated by extension + MIME + size (`catalog.media.*`), stored on the public disk at `products/{id}/…`. Cap `catalog.media.max_per_product` (default 10) → **422**. → **201**, `body.media: { id, disk, path, mime_type, size, original_name, type, sort_order, url }`. |
| **PATCH** | **`/products/{product}/media/order`** | **owner (else 403)** | Body `media: [id, …]` — the **full** ordered id list; index 0 is the cover. A partial or foreign list → **422**. → `body.media: [ … ]`. |
| **DELETE** | **`/products/{product}/media/{media}`** | **owner (else 403)** | Deletes the file from the disk and the row. Wrong product for the media id → **404**. |
| GET | `/products/import/template` | seller | Downloads the CSV template — header row + one worked example, UTF-8 BOM. `Content-Disposition: attachment`. Compound columns: `color_ids` (`3;7`), `price_tiers` (`50:41.00;200:38.50`), `price_on_contact` (`1`/`0`). |
| POST | `/products/import` | importer/retailer (not wholesaler/customer) | Multipart `file` — CSV (`mimes:csv,txt`, ≤2 MB). Stashes the file, creates a `product_import_batches` row, dispatches a **queued job**, returns **202** `body.import: { id, status, … }`. |
| GET | `/products/import/{import}` | batch owner (else 403) | Batch status + per-row report. `body.import.rows: [{ row_number, status: imported\|failed\|limit_rejected, product_id, errors }]`. `row_number` is the physical file line (header = line 1). Each valid row → a `pending_review` product (same queue as manual create, BR-SEL-02); a bad row is `failed` with its validation `errors` and skipped (spec §4.2); rows past `product_limit` (BR-SEL-01) are `limit_rejected` carrying `errors.product_limit` (the upgrade prompt, US-SEL-10) and nothing is created for them. On completion (or file-read failure) a `ProductImportCompleted` event fires — the seller notification is Phase 8; until then, poll this endpoint. |
| GET | `/admin/products` | admin (else 403) | Pending-review queue. |
| POST | `/admin/products/{product}/approve` | admin (else 403) | `pending_review` → `published`; blocked **422** if the product has no image. Audit `product.approved`, event `ProductApproved`. |
| POST | `/admin/products/{product}/reject` | admin (else 403) | Body `reason`. → `rejected` + reason. Audit `product.rejected`. |
| POST | `/admin/products/{product}/hide` | admin (else 403) | `published` → `hidden`. Audit `product.hidden`. |

The primary/cover image is the media row with the lowest `sort_order`
(`ProductCardResource.primary_image`). **AVL-NFR-03** (never lose product data on a failed
image upload) holds by construction: the product always exists before any media call.

Still pending on #16: an **XLSX** reader (CSV works today; XLSX needs a spreadsheet library
sign-off).

---

## Chat (Phase 7, #27) — `auth:sanctum`

MySQL is the source of truth; Pusher is best-effort realtime. A broadcast failure never
fails or delays a send (US-CHT-05).

| Method | Path | Who | Notes |
|---|---|---|---|
| POST | `/inquiries/{inquiry}/conversation` | inquiry participant (else 403) | Idempotent open/create — a second call returns the existing conversation (`200` vs `201`). `body.conversation: { id, inquiry_id, buyer_id, seller_business_id, unread_count?, last_message?, … }`. |
| GET | `/conversations` | authenticated | The caller's conversations, paginated, ordered by last activity. Each row carries the caller's own `unread_count`; response also carries top-level `body.total_unread`. |
| GET | `/conversations/{conversation}` | participant (else 403) | Detail + caller's `unread_count`. |
| POST | `/conversations/{conversation}/read` | participant (else 403) | Marks the other party's messages read. → `body: { unread_count: 0, total_unread }`. |
| GET | `/conversations/{conversation}/messages` | participant (else 403) | Cursor-paginated history, newest-first by default; `?direction=newer` for ascending. `body.messages: { data: [ { id, conversation_id, sender_id, body, read_at, created_at } ], meta, links }`. |
| POST | `/conversations/{conversation}/messages` | participant (else 403) | Body `body` (required, `≤ chat.message_max_length`). Persists then fires `MessageSent` (queued broadcast on `private-conversation.{id}`). Rate-limited: `chat.throttle.send_per_minute` → **429**. → **201** `body.message`. |
| POST | `/conversations/{conversation}/messages/{message}/reports` | participant (else 403) | Body `reason` (required, ≤1000). Writes a `reports` row (`reportable_type = message`). Message from another conversation → **404**. → **201** `body.report`. |

Broadcast auth: `POST /broadcasting/auth` (`auth:sanctum`) runs the same `ConversationPolicy`
as the REST endpoints — a non-participant is **403** on the channel, never trusting the
client-declared channel name (US-CHT-02).

---

## Notifications (Phase 8, #28) — `auth:sanctum`

One `Event::subscribe` listener layer turns existing domain events into queued Laravel
notifications. Channels are resolved server-side (`NotificationChannelResolver`): matrix
defaults ∩ preferences, `database` always kept, operational events (`verification_*`,
`subscription_*`) force `mail`+`sms` regardless of preference (US-NOT-03).

| Method | Path | Notes |
|---|---|---|
| GET | `/notifications` | The caller's feed, paginated, newest-first. `?unread=1` filters to unread. `body.notifications.data: [ { id, type, data, read_at, created_at } ]`. |
| GET | `/notifications/unread-count` | → `body.unread_count`. |
| POST | `/notifications/{notification}/read` | Marks one read; not the caller's → **404**. |
| POST | `/notifications/read-all` | → `body.unread_count: 0`. |
| GET | `/notification-preferences` | Effective grid: every `(category, channel)` with resolved `enabled` + `operational_locked`, plus `body.marketing_opt_in`. |
| PUT | `/notification-preferences` | Body `preferences: [{ category, channel, enabled }]` — batch upsert. Disabling a locked (operational) channel is stored but has no effect; the response echoes the effective state. |
| PUT | `/notification-preferences/marketing` | Body `enabled` (bool) — the separate marketing opt-in (US-NOT-04). |

Categories: `verification`, `product_review`, `inquiry`, `rfq`, `quotation`, `message`,
`subscription`, `marketing`. Channels: `database`, `push`, `mail`, `sms`. Push/SMS are
`log`-driver seams in R1 (no vendor in the SRS).

New scheduled command: `subscriptions:notify-expiring` (daily) fires `SubscriptionExpiring`
once per period for subscriptions ending within `SUBSCRIPTION_EXPIRING_REMINDER_DAYS`.

Out of scope (later phases): Orders/Payments/Shipping (R2–R4); `market_alert_match` and real
push/SMS providers deferred (see `docs/PHASE_STATUS.md`).

---

## Admin (Phase 9, #29) — `auth:sanctum` + `admin` role, unless noted

Every route below is additionally gated by the `admin` route middleware
(`Modules\Core\Http\Middleware\EnsureUserIsAdmin`) — the same gate the Filament panel
enforces, so REST and Filament share one authorization source per resource. Every
create/edit/decide/remove action here writes exactly one `audit_logs` row (`AuditLog::record()`,
append-only — update/delete throw at the model layer); the action column below names it.

| Method | Path | Notes | Audit action |
|---|---|---|---|
| GET | `/admin/audit-logs` | Read-only viewer, filterable by `actor_id`/`action`/`auditable_type`/`auditable_id`. Paginated. | — |
| GET | `/admin/dashboard/liquidity` | Marketplace-health snapshot: `active_sellers`/`active_products` (current-state), `active_buyers`/`inquiries_last_period`/`zero_result_terms` (windowed by `?range=`, default 7 days). Flat `body`. | — |
| GET | `/admin/products` | Pending-review queue, paginated, newest-first. | — |
| POST | `/admin/products/{product}/approve` | Guard: product must be `pending_review` (else `409/4093`) and have ≥1 image (else `422`). → `published`. | `product.approved` |
| POST | `/admin/products/{product}/reject` | Body `reason` (required, 3–1000 chars). Guard `409/4093`. → `rejected`. | `product.rejected` |
| POST | `/admin/products/{product}/request-edits` | Body `reason` (required, 3–1000 chars). Guard `409/4093`. → `draft` (not `rejected`) so the seller can revise and resubmit. | `product.edits_requested` |
| POST | `/admin/products/{product}/hide` | Guard: product must be `published` (else `409/4093`). → `hidden`. | `product.hidden` |
| GET | `/admin/taxonomy/{type}` | `{type}` ∈ `fabric-types\|materials\|colors\|units` (backed-enum route binding — unknown type → 404). All terms including inactive. | — |
| POST | `/admin/taxonomy/{type}` | Body `name_ar`, `name_en` (both required), `hex` (colors only, silently ignored elsewhere). Slug auto-derived from `name_en`, de-duplicated. | `taxonomy.created` |
| PATCH | `/admin/taxonomy/{type}/{term}` | Body: any of `name_ar`/`name_en`/`is_active`/`hex`, all `sometimes`. `is_active` transitioning `true→false` is a **deactivation**, everything else is a plain **update** — distinct audit actions. Deactivated terms drop from the public taxonomy read endpoints and product-creation validation immediately (existing `is_active` scoping — no new mechanism). | `taxonomy.updated` or `taxonomy.deactivated` |
| GET | `/admin/subscription-plans` | All plans (including inactive), with entitlements. | — |
| POST | `/admin/subscription-plans` | Body: `account_type`, `name`, `price`, `billing_cycle`, `trial_days`, `is_active`, `entitlements:[{key,value}]` (all per `StoreSubscriptionPlanRequest`). | `plan.created` |
| PATCH | `/admin/subscription-plans/{plan}` | Same fields, all `sometimes`. **Never** touches any existing subscriber's entitlements (US-SUB-05 non-retroactive) — see `apply-to-existing` below. | `plan.updated` |
| POST | `/admin/subscription-plans/{plan}/apply-to-existing` | Body `confirm` (required, must be `true` — else nothing happens). Re-copies the plan's *current* entitlements onto every currently-active subscription on it. → `body.subscriptions_updated`. | `plan.applied_to_existing` |
| GET | `/admin/featured` | All placements (active + expired), newest-first. | — |
| POST | `/admin/featured` | Body `type` (`product\|supplier`), `featurable_id`, `slot`, `starts_at`/`ends_at` (nullable). Target must exist (else 404). Same `(type, featurable_id, slot)` twice → `409/4094`. | `featured.placed` |
| DELETE | `/admin/featured/{placement}` | Hard delete + audit (audit row written before the delete, since a deleted row can't be read back). | `featured.removed` |
| GET | `/banners` | **Public, no auth.** Currently-active banners (`is_active` AND within `starts_at`/`ends_at`) ordered by `position`. For the separate marketplace client's homepage — this repo has none. Management is Filament-only (no admin REST surface for banners). | — |
| POST | `/admin/accounts/{account}/suspend` | Body `confirm` (required `true`). Guards: not already suspended (`409/4095`), target not an admin (`403/4032`). → status `suspended` + **every Sanctum token revoked** (next request with an old token → 401, no new middleware needed). Fires `AccountSuspended` (not yet wired to a notification). | `account.suspended` |
| POST | `/admin/accounts/{account}/reactivate` | No `confirm` needed. Guard: must currently be suspended (else `409/4096`). → status `active`. No token restore. | `account.reactivated` |
| GET | `/admin/reports` | Dispute/report queue, `?status=` filter, open-first ordering, paginated. Each row resolves both parties (buyer + seller business) whether the report is against an inquiry or a chat message. | — |
| POST | `/admin/reports/{report}/resolve` | Body `note` (required, 3–2000 chars), `status` (`resolved\|dismissed`, default `resolved`). Guard: not already resolved/dismissed (`409/4097`). | `report.resolved` |
| POST | `/admin/businesses` | Assisted supplier onboarding — one atomic transaction. Body: `phone` (same `EgyptianMobile` rule as public registration), `account_type` (`importer\|wholesaler\|retailer`), `password`+`password_confirmation`, `email`, `language`, plus the business-profile fields (`company_name`, `activity`, `governorate_id`, `address`, `contact_person`). Duplicate phone → `409/4091` (same exception public registration throws). Resulting account: `active` immediately, `onboarded_by_admin = true`, otherwise indistinguishable from self-registration (no subscription granted — same as self-registration). Filament wizard parity, same action underneath. | `supplier.onboarded` |

Out of scope for Phase 9 (later phases): Orders/Payments/Shipping admin (R2–R4).
