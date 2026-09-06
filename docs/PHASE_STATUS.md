# THOB — Phase & Endpoint Status

Last updated: **2026-09-06**. Companion to `docs/PROGRESS.md` (narrative) — this file is the
at-a-glance grid. Verification columns are deliberately conservative: **passing automated
tests ≠ VERIFIED**.

## Legend

| Column | Meaning |
|---|---|
| **Impl** | Code exists and is wired (routes, policy, migration). |
| **Auto** | Automated test coverage: ✅ good · 🟡 partial · ⬜ none. |
| **QA** | Manual QA pass performed & recorded in `docs/qa/`. `—` = not yet run. |
| **Postman** | Request present in `docs/postman/THOB API.postman_collection.json`. |
| **Docs** | Endpoint contract in `docs/API_REFERENCE.md`. |
| **Verif.** | Overall verification status — see scale below. |

Verification scale: **PENDING** (not built) · **BUILT** (code only) · **TESTED**
(automated tests green) · **DOCUMENTED** (tests + reference + Postman) · **VERIFIED**
(DOCUMENTED + manual QA sign-off + spec discrepancies resolved or accepted).

Nothing is **VERIFIED** yet — manual QA (`docs/qa/`, issue #25) has not been executed. The
D1–D13 spec/implementation discrepancies are now all resolved or fixed (see bottom); the
remaining open gaps are Phase-3 build work (#15/#16), infra (#24), and product decision #26.

---

## Phase roll-up

| Phase | Area | Impl | Automated tests | QA | Postman | Docs | Verification |
|---|---|---|---|---|---|---|---|
| 0 | Auth foundation | ✅ | ✅ 46 | — | ✅ | ✅ | DOCUMENTED |
| 1 | Business profile / Verification / Audit | ✅ | ✅ 46 (Businesses 12 + Verification 29 + Admin 5) | — | ✅ | ✅ | DOCUMENTED |
| 2 | Subscriptions & entitlements | ✅ | ✅ 64 | — | ✅ | ✅ | DOCUMENTED |
| 3 | **Catalog** — 3.1 Products ✅ · 3.2 Media ⬜ (#15) · 3.3 Bulk import ⬜ (#16) | 🟡 products CRUD/lifecycle/review done; media + import missing | 🟡 11 Catalog tests (products) | — | 🟡 read path only | 🟡 (excluded from Postman until 3.2/3.3) | **PARTIAL** |
| 4 | Search | ✅ | ✅ 37 | — | ✅ | ✅ | DOCUMENTED |
| 5 | Favorites + Comparison | ✅ | ✅ 15 (Favorites 9 + Comparison 6) | — | ✅ | ✅ | DOCUMENTED |
| 6 | Inquiries / RFQ / Quotation / Reporting | ✅ | ✅ 36 | — | ✅ | ✅ | DOCUMENTED |
| 7 | Chat (Pusher) | ⬜ | ⬜ | — | ⬜ | ⬜ | PENDING |
| 8 | Notifications | ⬜ (events fire, no listeners) | ⬜ | — | ⬜ | ⬜ | PENDING |
| 9 | Admin dashboard (rest of) | 🟡 verification + subscriptions panels only | 🟡 12 Filament tests | — | n/a (Filament) | ⬜ | PENDING |
| 10 | R1 hardening (authz suite / load / security) | ⬜ | ⬜ | — | ⬜ | ⬜ | PENDING |

**Full suite: 315 automated tests, 315 passing, 0 failing, 0 skipped, 927 assertions**
(`php artisan test`, SQLite `:memory:`, 2026-09-06). Timeline: 294 baseline → 296 (D6/D7 fixes)
→ **315** (gap-closure batch: D5 envelope, D8 contact-info, D11/D13 env + OTP capture, D3
last-activity, BR-SUB-03 product hiding).

---

## Endpoint grid (completed phases)

| Endpoint | Impl | Auto | QA | Postman | Docs | Verif. |
|---|---|---|---|---|---|---|
| **Phase 0 — Auth** | | | | | | |
| `POST /auth/otp/request` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /auth/otp/verify` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /auth/register` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /auth/login` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /auth/password/reset` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /auth/account-types` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /auth/me` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /auth/logout` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /auth/account-type` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| **Phase 1 — Business / Verification** | | | | | | |
| `POST /businesses` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /businesses/{id}` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `PATCH /businesses/{id}` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /businesses/{id}/verification-documents` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /businesses/{id}/verification-documents/{doc}` | ✅ | ✅ | — | 🟡 (signed URL, manual) | ✅ | DOCUMENTED |
| `POST /businesses/{id}/verification-request` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /businesses/{id}/verification-status` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /admin/verification-requests` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /admin/verification-requests/{id}/approve` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /admin/verification-requests/{id}/reject` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| **Phase 2 — Subscriptions** | | | | | | |
| `GET /subscription-plans` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /subscriptions` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /subscriptions/{id}` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /subscriptions/{id}/usage` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `PATCH /subscriptions/{id}` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| **Phase 4 — Search** | | | | | | |
| `GET /products` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /products/{id}` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /businesses` (supplier search) | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /businesses/{id}/catalog` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| **Phase 5 — Favorites / Comparison** | | | | | | |
| `GET /favorites` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /favorites` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED (404→4040 fixed) |
| `DELETE /favorites/{id}` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /compare` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| **Phase 6 — Inquiries** | | | | | | |
| `GET /inquiries` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /inquiries` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /inquiries/{id}` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `PATCH /inquiries/{id}` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /inquiries/{id}/rfqs` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /rfqs/{id}` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /rfqs/{id}/quotations` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /inquiries/{id}/reports` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |
| **Taxonomy (dependency)** | | | | | | |
| `GET /taxonomy/{5 lists}` | ✅ | ✅ | — | ✅ | ✅ | DOCUMENTED |

---

## Current development frontier

1. **Phase 3 media + bulk import** (#15 → #16) — the only remaining R1 feature build.
   3.1 (product CRUD, lifecycle, review queue, plan limit) is done and tested; media upload
   and XLSX/CSV import are not.
2. **Manual QA pass** on Phases 0–2, 4–6 (#25) — checklists in `docs/qa/`, none executed;
   blocked on #15 + the gap-closure batch landing.
3. **Follow-ups**: MySQL FULLTEXT CI lane + 100k load check (#24, D12); AV scan on
   verification uploads (#21, SEC-NFR-05); product decision on unverified-supplier
   visibility (#26, Open Decision #5).

## Open spec / implementation discrepancies

| # | Spec says | Implementation does | Severity | Suggested resolution |
|---|---|---|---|---|
| D1 | `PATCH /inquiries/{id}/status` (§11) | `PATCH /inquiries/{id}` | Low | **RESOLVED 2026-09-06** — spec §11 corrected to the implemented path. |
| D2 | `POST /inquiries/{id}/rfq` (§11) | `POST /inquiries/{id}/rfqs` | Low | **RESOLVED 2026-09-06** — spec §11 corrected. |
| D3 | `GET /businesses/{id}/leads` (US-ANL-03 / §11) | leads = `GET /inquiries?role=seller` | Low | **RESOLVED 2026-09-06** — spec §11 corrected; `last_activity_at` added to each lead. |
| D4 | `inquiries.status` **and** `lead_status` (§10.5) | only `lead_status` | Low | **RESOLVED 2026-09-06** — the `docs/docs/` spec copy already had only `lead_status`; §10.5 note made explicit. |
| D5 | "unified envelope on every `/api/v1/` response" (§11) | 403/404 native; `POST`/`PATCH /subscriptions` validation native 422 | Medium | **FIXED 2026-09-06** — Core `Handler` envelopes 403 (`4031`) and 404 (`4040`); Subscriptions requests now extend `BaseRequest` (enveloped 400). |
| D6 | `POST /favorites` missing target | HTTP 404 but `custom_code: 2000` | Low (bug) | **FIXED 2026-09-06** — not-found branch now returns `custom_code 4040`; test asserts it. |
| D7 | `rfq.needed_by_date` "must be today or future"; `quotation.valid_until` "must be future at creation" (§7 assumptions) | both were just `['required','date']` | Medium | **FIXED 2026-09-06** — added `after_or_equal:today` / `after:now`; 2 new tests. |
| D8 | US-INQ-05: buyer sees seller phone/WhatsApp per plan/settings | `contact_channels` owner/admin-only, no plan gate | Medium | **FIXED 2026-09-06** — `contact_info_visible` entitlement (default off); `BusinessResource` + `ProductDetailResource.supplier` expose `contact_channels` to buyers only when the seller's active plan grants it. Open Decision #4 resolved-with-default. |
| D9 | US-SUB-04: "usage vs limits, renewal date, **billing history**" | usage + renewal only; no invoices | Low (expected) | Billing history depends on payments (R4). |
| D10 | US-SUB-06 recurring billing | not implemented | Low (expected) | R4 / payment provider. |
| D11 | `.env.example` | was the POS starter kit; no THOB keys | Low | **FIXED 2026-09-06** — rewritten for THOB (locale, DB, `OTP_DRIVER`, `VERIFICATION_DISK*`, `CATALOG_REVIEW_*`). |
| D12 | PRF-NFR-01 (<2s p95 @ 100k products) | no load test; FULLTEXT only exercised on SQLite `LIKE` | Medium | Phase 10 / issue #24 — MySQL CI lane + load script. |
| D13 | No way to read an OTP in local dev | `log` sender never emits the code | Low | **FIXED 2026-09-06** — `capture` OTP driver (local/testing only, refused in production) writes the code to the cache. |
| BR-SUB-03 | expiry hides seller products | subscription → Restricted was tested; product hiding not wired | Medium | **FIXED 2026-09-06** — `Product::scopeBuyerVisible` now requires the owner's subscription not be lapsed (never-subscribed keeps current visibility); 8 tests. |
