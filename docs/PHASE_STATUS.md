# THOB — Phase & Endpoint Status

Last updated: **2026-09-11**. Companion to `docs/PROGRESS.md` (narrative) — this file is the
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
remaining open gaps are the deferred XLSX import reader (#16 — CSV done), infra (#24), and
product decision #26.

---

## Phase roll-up

| Phase | Area | Impl | Automated tests | QA | Postman | Docs | Verification |
|---|---|---|---|---|---|---|---|
| 0 | Auth foundation | ✅ | ✅ 46 | — | ✅ | ✅ | DOCUMENTED |
| 1 | Business profile / Verification / Audit | ✅ | ✅ 52 (Businesses 16 + Verification 31 + Admin 5) | — | ✅ | ✅ | DOCUMENTED |
| 2 | Subscriptions & entitlements | ✅ | ✅ 64 | — | ✅ | ✅ | DOCUMENTED |
| 3 | **Catalog** — 3.1 Products ✅ · 3.2 Media ✅ (#15) · 3.3 CSV bulk import ✅ (#16) · XLSX import ⬜ (#16, needs lib sign-off) | 🟡 CSV path done; XLSX reader deferred | ✅ 31 Catalog tests | — | ✅ product + media + import endpoints | ✅ (§4) | **PARTIAL** |
| 4 | Search | ✅ | ✅ 37 | — | ✅ | ✅ | DOCUMENTED |
| 5 | Favorites + Comparison | ✅ | ✅ 15 (Favorites 9 + Comparison 6) | — | ✅ | ✅ | DOCUMENTED |
| 6 | Inquiries / RFQ / Quotation / Reporting | ✅ | ✅ 36 | — | ✅ | ✅ | DOCUMENTED |
| 7 | Chat (Pusher) | ✅ (#27) | ✅ 21 | — | ⬜ | 🟡 (API_REFERENCE §Chat) | **PARTIAL** — postman + QA plan pending |
| 8 | Notifications | ✅ (#28) | ✅ 21 | — | ⬜ | 🟡 (API_REFERENCE §Notifications) | **PARTIAL** — postman + QA traceability (US-NOT-24) pending |
| 9 | Admin dashboard — taxonomy, plans, featured, banners, liquidity, suspend/reactivate, reports, onboarding, audit-completeness | ✅ (#30–#40) | ✅ 118 (T1–T11 combined) | — | ✅ | ✅ | DOCUMENTED |
| 10 | R1 hardening (authz suite / load / security) | ⬜ | ⬜ | — | ⬜ | ⬜ | PENDING |

**Full suite: 507 automated tests, 507 passing, 0 failing, 0 skipped**
(`php artisan test`, SQLite `:memory:`, 2026-09-11). Timeline: 294 baseline → 296 (D6/D7)
→ 315 (gap-closure) → 326 (#15 Phase 3.2 media) → 328 (#21 SEC-NFR-05 malware scan seam)
→ 338 (#16 Phase 3.3 CSV bulk import) → 357 (#27 Phase 7 Chat) → 376 (#28 Phase 8 Notifications)
→ 378 (Phase 7/8 code-review fixes) → 389 (#30 Phase 9 T1 admin gate + audit-log viewer)
→ 411 (#31 T2 product review) → 430 (#32 T3 taxonomy) → 438 (#33 T4 plan management +
entitlement-snapshot architecture fix) → 452 (#34 T5 featured placements) → 462 (#35 T6
banners) → 471 (#36 T7 liquidity dashboard) → 483 (#37 T8 suspend/reactivate) → 498 (#38
T9 reports queue) → **507** (#39 T10 assisted onboarding). #40 (T11) added no new
production code — audit-completeness was already fully covered by every prior ticket's own
tests (verified, not re-tested) — and closes out this doc set.

### Phase 7 / 8 deferrals (documented, not gaps in the phase scope)

- **`market_alert_match` (§14 / US-BUY-03)** — no `MarketAlertMatched` event or notification. Depends on the buyer "market alerts" feature (BUY-FR-03), which is unbuilt. Whole matrix row deferred.
- **Real push / SMS providers (SI-FR-04)** — `PushSender` / `SmsSender` are seams with a `log` default driver (`null` alt). No FCM/APNs/OneSignal or SMS gateway; that is a follow-up ticket.
- **Marketing campaign sending** — only the opt-in flag (preference rows) + the resolver's marketing branch ship. No marketing `Notification` classes, no separate suppression-list table (structure deferred until a campaign feature exists).
- **Notification digests** (§17 cron mention) — R1 sends per event.
- **Order / payment / shipment matrix rows** — R2 / R4.
- **Sub-user notification routing (ACC-FR-08)** — R2; owner-only recipients in R1.
- **US-NOT-24 traceability doc** — `docs/qa/phase-7-*.md` / `phase-8-*.md` + postman folders not yet written (follow-up, same as Phases 3.2/3.3 which also lag their postman/QA docs).

### Phase 9 deferrals (documented, not gaps in the phase scope)

- **`units` taxonomy management has no consumer** (#32 / T3) — `products.unit` is a hardcoded DB enum (`per_meter`/`per_kg`), never wired to the `units` taxonomy table T3 built CRUD for. A user-approved decision: build the CRUD as-is, flag the disconnect, defer the schema migration to wire `Product.unit` to a real `unit_id` FK (out of this phase's scope).
- **`AccountSuspended` event not wired to a notification** (#37 / T8) — dispatched on suspend, but no listener/Notification class exists yet, and no Notification Matrix row (§14) covers "account suspended". Follow-up once a matrix row is decided.
- **Phase 9 T1's Filament panel i18n/UX pass** (nav groups, ar/en, `filament-language-switch`) shipped inside #30 but the theme rebuild is blocked on the pre-existing broken legacy `resources/js` Vite build (ADR-0001 removal debt) — switcher works, styling incomplete until that cleanup lands.
- **No REST admin surface for banners** (#35 / T6) — the ticket explicitly asks for Filament CRUD + one public read endpoint only, unlike every other Phase 9 ticket's "Filament + REST parity" wording; an initial pass built a parallel REST CRUD anyway and it was removed as scope creep during code review.

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
| **Phase 7 — Chat** | | | | | | |
| `POST /inquiries/{id}/conversation` | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| `GET /conversations` | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| `GET /conversations/{id}` | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| `POST /conversations/{id}/read` | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| `GET\|POST /conversations/{id}/messages` | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| `POST /conversations/{id}/messages/{id}/reports` | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| `POST /broadcasting/auth` (channel policy) | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| **Phase 8 — Notifications** | | | | | | |
| `GET /notifications` (+ `?unread=1`) | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| `GET /notifications/unread-count` | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| `POST /notifications/{id}/read` · `/read-all` | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| `GET\|PUT /notification-preferences` | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| `PUT /notification-preferences/marketing` | ✅ | ✅ | — | ⬜ | ✅ | TESTED |
| **Phase 9 — Admin** | | | | | | |
| `GET /admin/audit-logs` | ✅ (#30) | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /admin/dashboard/liquidity` | ✅ (#36) | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /admin/products` | ✅ (#31) | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /admin/products/{id}/approve\|reject\|request-edits\|hide` | ✅ (#31) | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET\|POST /admin/taxonomy/{type}` · `PATCH /admin/taxonomy/{type}/{term}` | ✅ (#32) | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET\|POST /admin/subscription-plans` · `PATCH .../{id}` · `POST .../{id}/apply-to-existing` | ✅ (#33) | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET\|POST /admin/featured` · `DELETE /admin/featured/{id}` | ✅ (#34) | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /banners` (public) | ✅ (#35) | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /admin/accounts/{id}/suspend\|reactivate` | ✅ (#37) | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `GET /admin/reports` · `POST /admin/reports/{id}/resolve` | ✅ (#38) | ✅ | — | ✅ | ✅ | DOCUMENTED |
| `POST /admin/businesses` | ✅ (#39) | ✅ | — | ✅ | ✅ | DOCUMENTED |

---

## Current development frontier

1. **Phase 3 bulk import** (#16) — 3.1 (product CRUD, lifecycle, review queue, plan limit),
   3.2 (media upload/reorder/delete + publish requires ≥1 image) and 3.3 **CSV** bulk import
   (template download, queued per-row job, per-row report, batch-wide `product_limit`) are
   done and tested. Only the **XLSX** reader remains — deferred pending a spreadsheet-library
   sign-off (native `fgetcsv` covers CSV with no new dependency).
2. **Manual QA pass** on Phases 0–2, 4–6 (#25) — checklists in `docs/qa/`, none executed;
   blocked on #15 + the gap-closure batch landing.
3. **Follow-ups**: MySQL FULLTEXT CI lane + 100k load check (#24, D12); a real ClamAV/hosted
   adapter for the verification `FileScanner` seam (#21 shipped the seam + EICAR default);
   product decision on unverified-supplier visibility (#26, Open Decision #5); a
   500-concurrent-user load test (NFR-PRF-04) still needs its own ticket filed (see #41).
4. **Phase 10 — R1 Hardening** (#41, spec'd via `/to-spec` → tickets #42–#50, `ready-for-agent`)
   is next: authorization-matrix suite per Policy, §15 security-checklist pass, one root-level
   R1 end-to-end story test, gap report. Phase 9's own authorization-matrix coverage (§8
   "Admin Functions" row) is scoped to what's built today only — Phase 9 T2–T10 (#31–#39, all
   now shipped).

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
| SEC-NFR-05 | uploaded files "scanned before publish" | only ext + MIME + size checked | Medium | **FIXED 2026-09-06 (#21)** — `FileScanner` seam wired into the verification upload (scans the temp file before anything is stored); default `SignatureFileScanner` flags EICAR; `VERIFICATION_SCANNER=null` disables; real ClamAV/hosted adapter is a deploy-config binding. |
| D9 | US-SUB-04: "usage vs limits, renewal date, **billing history**" | usage + renewal only; no invoices | Low (expected) | Billing history depends on payments (R4). |
| D10 | US-SUB-06 recurring billing | not implemented | Low (expected) | R4 / payment provider. |
| D11 | `.env.example` | was the POS starter kit; no THOB keys | Low | **FIXED 2026-09-06** — rewritten for THOB (locale, DB, `OTP_DRIVER`, `VERIFICATION_DISK*`, `CATALOG_REVIEW_*`). |
| D12 | PRF-NFR-01 (<2s p95 @ 100k products) | no load test; FULLTEXT only exercised on SQLite `LIKE` | Medium | Phase 10 / issue #24 — MySQL CI lane + load script. |
| D13 | No way to read an OTP in local dev | `log` sender never emits the code | Low | **FIXED 2026-09-06** — `capture` OTP driver (local/testing only, refused in production) writes the code to the cache. |
| BR-SUB-03 | expiry hides seller products | subscription → Restricted was tested; product hiding not wired | Medium | **FIXED 2026-09-06** — `Product::scopeBuyerVisible` now requires the owner's subscription not be lapsed (never-subscribed keeps current visibility); 8 tests. |
