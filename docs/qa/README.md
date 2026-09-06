# THOB QA — Test Plan & Traceability

Hand-maintained. One document per **completed** phase:

| File | Phase | Scope |
|---|---|---|
| [phase-0-auth.md](phase-0-auth.md) | 0 | OTP, register, login/logout, password reset, account type |
| [phase-1-business-verification.md](phase-1-business-verification.md) | 1 | Business profile, verification upload/review, audit log |
| [phase-2-subscriptions.md](phase-2-subscriptions.md) | 2 | Plans, subscribe, upgrade/downgrade/cancel, entitlements, trials |
| [phase-4-search.md](phase-4-search.md) | 4 | Product & supplier search, filters, sort, featured, zero-result log |
| [phase-5-favorites-comparison.md](phase-5-favorites-comparison.md) | 5 | Favorites, comparison |
| [phase-6-inquiries.md](phase-6-inquiries.md) | 6 | Inquiry, RFQ, quotation, leads, reporting |

Phase 3 (Catalog): **3.1 products + 3.2 media are built and tested** (`Modules/Catalog` —
`ProductTest` 11, `ProductMediaTest` 10; endpoints in `docs/API_REFERENCE.md` §4). A
dedicated phase-3 QA doc is a follow-up; **3.3 bulk import (#16)** is not built.
Phases 7–10 are not started.

---

## How to read a phase QA doc

Each phase doc has the same five sections:

1. **Status summary** — a table over six axes. These are **independent**:
   - **Implementation** — code exists and is wired.
   - **Automated Tests** — PHPUnit coverage (count + verdict).
   - **Manual QA** — human test pass recorded in that doc. `NOT RUN` until executed.
   - **Postman Coverage** — requests present in the collection.
   - **Documentation** — endpoint contract in `docs/API_REFERENCE.md`.
   - **Verification Status** — the roll-up. See scale below.
2. **Requirements traceability matrix** — every `US-*` / `BR-*` / relevant `NFR` for the
   phase → the automated test(s) that exercise it → a coverage verdict.
3. **Coverage gaps** — requirements that are uncovered or only partially covered, and why.
4. **Manual QA test cases** — only scenarios automation cannot reliably reach
   (real SMS/OTP delivery, wall-clock rate-limit windows, real file-upload/AV, S3 signed
   URLs, MySQL full-text, Filament UI). Each case: preconditions → steps → expected → result.
5. **Spec ⇄ implementation notes** — where the running code diverges from the spec.

### Verification Status scale

| Status | Means |
|---|---|
| `PENDING` | not built |
| `BUILT` | code only, no tests |
| `TESTED` | automated tests green |
| `DOCUMENTED` | `TESTED` + in API reference + in Postman |
| `VERIFIED` | `DOCUMENTED` + manual QA signed off + spec discrepancies resolved/accepted |

> **A phase is never `VERIFIED` on automated tests alone.** As of 2026-09-06 every completed
> phase is `DOCUMENTED`; none has had a manual QA pass.

---

## Master traceability matrix (all completed phases)

`✅` covered by automated tests · `🟡` partial · `⬜` not covered by automation
(needs manual QA or is out of R1 scope).

| Req | Title | Phase | Auto | Evidence (test file · method) |
|---|---|---|---|---|
| US-ACC-01 | Register with phone + OTP | 0 | ✅ | OtpRequestTest, OtpVerifyTest, RegistrationTest (all) |
| US-ACC-02 | Choose account type | 0 | ✅ | AccountTypeTest (7), Unit/AccountTypeTest |
| US-ACC-07 | Login / logout / session / recovery | 0 | ✅ | LoginTest (4), PasswordResetTest (3) |
| SEC-NFR-02 | OTP hashed, 5-min expiry, rate-limited | 0 | 🟡 | OtpRequestTest `it_sends_a_hashed_code…`, `it_throttles…`; OtpVerifyTest `an_expired_code…`, `three_wrong_attempts_lock…` — **wall-clock window & real provider = manual** |
| US-ACC-03 | Business account profile capture | 1 | ✅ | BusinessProfileTest (12) |
| US-ACC-04 | Upload verification documents | 1 | 🟡 | VerificationUploadTest (12) — **real AV scan / real S3 = manual** |
| US-ACC-05 | Verified badge display | 1 | ✅ | BusinessProfileTest `the_verified_flag_tracks…`; AdminVerificationReviewTest `the_verified_badge_shows_after_approval` |
| US-ADM-01 | Verification review approve/reject + reason | 1 | ✅ | AdminVerificationReviewTest (9), Filament/VerificationRequestPanelTest (6) |
| US-ADM-09 | Immutable audit log | 1 | ✅ | AuditLogTest (5), AdminVerificationReviewTest (audit rows) |
| BR-ADM-01 | Every admin action audited | 1 | 🟡 | Covered for verification approve/reject; **other admin actions are Phase 9** |
| SEC-NFR-05 | Uploaded files type/size validated, scanned | 1 | 🟡 | type/size ✅ (VerificationUploadTest); **"scanned before publish" not implemented** |
| SEC-NFR-01 | No tokens/credentials in URLs | 1 | 🟡 | signed doc URL uses `signature`/`expires` query params only; **full TLS review = manual/infra** |
| US-SUB-01/02 | Plan catalog per account type | 2 | ✅ | SubscriptionPlanTest (4) |
| US-SUB-03 | Server-side entitlement enforcement | 2 | ✅ | EntitlementServiceTest (8), ClientTamperingTest (5) |
| US-SUB-04 | View plan / usage / renewal / billing history | 2 | 🟡 | SubscriptionShowTest (4), `usage` covered via SubscribeTest/UpgradeTest; **billing history not built (no payments)** |
| US-SUB-05 | Upgrade / downgrade / cancel | 2 | ✅ | UpgradeSubscriptionTest (5), DowngradeSubscriptionTest (3), PeriodEndCommandTest (5) |
| US-SUB-06 | Recurring billing | 2 | ⬜ | **not implemented — R4 payment provider** |
| US-SUB-07 | Trial & promotional grants | 2 | ✅ | TrialPromoTest (4), SubscriptionPanelTest `grant_a_trial` |
| US-SUB-08 | Expiry → restricted state | 2 | ✅ | SubscriptionExpiryTest (8), PeriodEndCommandTest |
| BR-SUB-01 | Client plan claims never trusted | 2 | ✅ | ClientTamperingTest (5) |
| BR-SUB-02 | Upgrade immediate / downgrade at term end | 2 | ✅ | UpgradeSubscriptionTest, DowngradeSubscriptionTest, PeriodEndCommandTest |
| BR-SUB-03 | Expiry hides products, never deletes | 2 | ✅ | SubscriptionExpiryTest (state) + **SubscriptionCatalogVisibilityTest (8) — product hiding wired into `Product::scopeBuyerVisible`** |
| US-SRC-01/02/03/04 | Search / filter / sort / browse | 4 | ✅ | ProductSearchTest (19), SearchNormalizerTest (3) |
| US-SRC-05 | Product detail screen | 4 | ✅ | ProductSearchTest `the_public_product_detail_hides_internal_fields_and_404s…` |
| US-SRC-06 | Navigate to supplier catalog | 4 | ✅ | ProductSearchTest `the_supplier_catalog_is_scoped_and_filterable` |
| US-SRC-07 | Supplier search | 4 | ✅ | SupplierSearchTest (5) |
| US-SRC-10 | Featured ranking, labeled | 4 | ✅ | ProductSearchTest `featured_products_outrank…`, `the_featured_boost_disappears…`; SupplierSearchTest `a_featured_supplier_is_ranked_first…` |
| US-SRC-11 | Zero-result logging | 4 | ✅ | ProductSearchTest `a_zero_result_search_is_logged…`, `a_matched_search_is_not_logged` |
| BR-SRC-01 | Featured boosts always labeled | 4 | ✅ | as US-SRC-10 |
| BR-SRC-02 | Only published/visible products in search | 4 | ✅ | ProductSearchTest `it_returns_only_published_visible_products`, `products_from_a_suspended_supplier_are_hidden` |
| PRF-NFR-01 | Search <2s p95 @ 100k products | 4 | ⬜ | **no load test; SQLite only — manual/CI on MySQL (Phase 10)** |
| US-SRC-08 / US-BUY-02 | Favorites (products + suppliers) | 5 | ✅ | FavoriteTest (9) |
| US-SRC-09 / US-BUY-05 | Compare up to 4 | 5 | ✅ | ComparisonTest (6) |
| BR-FAV-01 | No duplicate favorites | 5 | ✅ | FavoriteTest `favoriting_the_same_item_twice…` |
| BR-CMP-01 | Comparison capped at 4 | 5 | ✅ | ComparisonTest `comparing_five_items_is_rejected…` |
| US-INQ-01 | Send inquiry | 6 | ✅ | InquiryTest (17) |
| US-INQ-02 | Structured RFQ (+ below-MOQ warning) | 6 | ✅ | RfqQuotationTest `a_buyer_submits_an_rfq…`, `a_quantity_below_moq_warns_but_still_succeeds` |
| US-INQ-03 | Seller quotation (+ expiry) | 6 | ✅ | RfqQuotationTest `the_addressed_seller_replies…`, `an_expired_quotation_is_shown_as_expired`, `a_seller_who_is_not_the_rfqs_target…` |
| US-INQ-04 | In-app chat tied to inquiry | 6 | ⬜ | **Phase 7 (Chat) — not started** |
| US-INQ-05 | Seller contact info visibility per plan | 6 | ✅ | **ContactVisibilityTest (4)** — `contact_info_visible` entitlement (default off) gates `contact_channels` for buyers |
| US-INQ-06/07 | Lead logging & status | 6 | ✅ | InquiryTest `a_new_inquiry_is_a_lead_in_new_status`, `the_seller_moves_a_lead_through_every_status` |
| US-INQ-08 | Plan limits on inquiries | 6 | ✅ | InquiryTest `sending_to_a_seller_past_their_inquiry_limit…`; RfqQuotationTest `an_rfq_is_rejected_when_the_seller_is_past…` |
| US-INQ-09 | Spam rate-limit + reporting | 6 | 🟡 | InquiryTest `inquiry_creation_is_rate_limited`, ReportTest (5); **content heuristics not implemented; report → admin ticket is Phase 9** |
| US-ANL-03 | Lead management screen | 6 | ✅ | InquiryTest — own-leads scoping, status filter, **`raising_an_rfq_advances_the_leads_last_activity`** (`last_activity_at` added; spec §11 corrected to `GET /inquiries?role=seller`) |
| BR-INQ-01 | Every inquiry = one Lead | 6 | ✅ | InquiryTest `a_new_inquiry_is_a_lead_in_new_status` |
| BR-INQ-02 | Inquiry/RFQ volume limits per plan | 6 | ✅ | as US-INQ-08 |

### Requirements with automation gaps (summary)

| Gap | Requirement(s) | Nature | Owner |
|---|---|---|---|
| Wall-clock OTP expiry / lockout window & real SMS provider | SEC-NFR-02, US-ACC-01 | needs manual (time + external) | QA — see phase-0 |
| Real antivirus scan of uploaded documents | SEC-NFR-05 (`scanned before publish`) | **not implemented** | Issue #21 |
| Real private-S3 storage + signed-URL expiry against S3 | US-ACC-04, SEC-NFR-01 | needs manual (infra) | QA — see phase-1 |
| Search performance @ 100k rows, MySQL FULLTEXT relevance | PRF-NFR-01, US-SRC-01 | needs manual/CI on MySQL | Issue #24 |
| ~~Product hiding on subscription expiry~~ | ~~BR-SUB-03~~ | **DONE** — `SubscriptionCatalogVisibilityTest` | — |
| ~~Seller contact-info visibility gate~~ | ~~US-INQ-05~~ | **DONE** — `contact_info_visible` entitlement | — |
| Recurring subscription billing | US-SUB-06 | **not implemented** — R4 | R4 |
| In-app chat | US-INQ-04 | **not started** — Phase 7 | Phase 7 |
| Report → admin ticket / dispute queue | US-INQ-09 (2nd half), US-ADM-08 | **not implemented** — Phase 9 | Phase 9 |
| Content-based spam heuristics | US-INQ-09 | **not implemented** (rate-limit only) | Backlog |
| ~~"Last activity" on the leads screen~~ | ~~US-ANL-03~~ | **DONE** — `last_activity_at` per lead (message activity folds in with Chat) | — |
| Billing history / invoices | US-SUB-04 | **not implemented** — with Payments | R4 |
