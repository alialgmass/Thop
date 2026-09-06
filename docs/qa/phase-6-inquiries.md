# Phase 6 QA — Inquiries · RFQ · Quotation · Leads · Reporting

Module: `Modules/Inquiries`. Spec: issue #14 (3 tickets) · US-INQ-01..09, US-ANL-03 ·
BR-INQ-01/02 · §36 Leads.

> RFQs reference Products (Catalog tables — **Partial / Phase 3 Pending**, factory data).
> In-app chat (US-INQ-04) is **Phase 7** and not in scope here.

## 1. Status summary

| Axis | State |
|---|---|
| Implementation | ✅ `inquiries` (lead_status IS the Lead), `rfqs`, `quotations`, `reports` (polymorphic); 8 EP; entitlement gate + rate limit via shared concerns; 4 events (no listeners — Phase 8) |
| Automated Tests | ✅ **36 passing** — Inquiry 17, RfqQuotation 14, Report 5 (incl. 2 added for D7) |
| Manual QA | ⬜ **NOT RUN** — cases in §4 |
| Postman Coverage | ✅ 8/8 endpoints incl. `?role=`/`?lead_status=` list variants |
| Documentation | ✅ `docs/API_REFERENCE.md` §3 items 33–40 (with the two spec path deviations) |
| **Verification Status** | **DOCUMENTED** |

## 2. Requirements traceability

| Req | Acceptance criterion | Automated test(s) | Verdict |
|---|---|---|---|
| US-INQ-01 | Contact from a product/supplier → `Inquiry` (buyer, seller, optional product), `new` lead | `InquiryTest::a_buyer_sends_an_inquiry_about_a_product`, `…general_inquiry…with_no_product`, `a_new_inquiry_is_a_lead_in_new_status` | ✅ |
| US-INQ-01 | Seller's inbound limit reached → clear message, buyer not silently dropped | `InquiryTest::sending_to_a_seller_past_their_inquiry_limit_is_rejected_clearly` (422/4231, `body.inquiry_limit`) | ✅ |
| US-INQ-01 | Importer / customer cannot send | `InquiryTest::an_importer_cannot_send_an_inquiry` (403) | ✅ |
| US-INQ-01 | Mismatched product ↔ seller → rejected | `InquiryTest::a_mismatched_product_and_seller_business_is_rejected` (422) | ✅ |
| BR-INQ-01 | Every inquiry = exactly one Lead (no separate table) | `InquiryTest::a_new_inquiry_is_a_lead_in_new_status` + schema (no `leads` table) | ✅ |
| US-INQ-02 | RFQ: product, quantity, color, needed-by-date → `RFQ` on the inquiry thread | `RfqQuotationTest::a_buyer_submits_an_rfq_on_their_inquiry` | ✅ |
| US-INQ-02 | Missing RFQ fields → validation blocks | `RfqQuotationTest::missing_required_rfq_fields_are_rejected` (400) | ✅ |
| US-INQ-02 | Below MOQ → warning, not block | `RfqQuotationTest::a_quantity_below_moq_warns_but_still_succeeds` (`below_moq: true`, 201) | ✅ |
| US-INQ-02 | RFQ product must belong to the inquiry's seller | `RfqQuotationTest::a_product_not_belonging_to_the_inquirys_seller_is_rejected` (422) | ✅ |
| US-INQ-03 | Seller replies with price + availability + validity → `Quotation`, buyer notified | `RfqQuotationTest::the_addressed_seller_replies_with_a_quotation` (event `QuotationReceived`) | 🟡 (record ✅; notification is Phase 8) |
| US-INQ-03 | Expired offer shown as expired, not actionable | `RfqQuotationTest::an_expired_quotation_is_shown_as_expired` (`expired: true`) | ✅ |
| US-INQ-03 | Non-target seller cannot quote → 403 | `RfqQuotationTest::a_seller_who_is_not_the_rfqs_target_cannot_quote` | ✅ |
| US-INQ-04 | In-app chat tied to inquiry | — | ⬜ **Phase 7** |
| US-INQ-05 | Seller contact info shown to buyer per plan/settings | `Businesses/ContactVisibilityTest` (4) — `contact_info_visible` entitlement gates `contact_channels` on `GET /businesses/{id}` + `ProductDetailResource.supplier` | ✅ (default off; Open Decision #4 resolved-with-default) |
| US-INQ-06/07 | Inquiry logged as Lead, status New → In progress → Done → Not completed (only these) | `InquiryTest::the_seller_moves_a_lead_through_every_status`, `an_unknown_lead_status_is_a_validation_error`, `a_buyer_cannot_change_lead_status` (403) | ✅ |
| US-INQ-08 / BR-INQ-02 | Plan limits enforced server-side, sender + receiver | `InquiryTest::sending_to_a_seller_past…`; `RfqQuotationTest::an_rfq_is_rejected_when_the_seller_is_past_their_inquiry_limit` (re-checked, not re-incremented) | ✅ (buyer side no-ops — no buyer plan defines `inquiry_limit` yet) |
| US-INQ-09 | Rate-limit → 429 with cooldown | `InquiryTest::inquiry_creation_is_rate_limited`; `RfqQuotationTest::rfq_creation_is_rate_limited` (429/4290) | 🟡 (single-process; wall-clock = §4-1) |
| US-INQ-09 | Either party can report abuse → admin ticket | `ReportTest` (5): buyer reports, seller reports, non-party 403, missing reason 400, guest 401 | 🟡 (durable record ✅; **admin ticket / dispute queue is Phase 9**) |
| US-ANL-03 | Leads screen: all inquiries + status + last activity, filterable | `InquiryTest::the_seller_sees_only_their_own_leads`, `the_seller_can_filter_leads_by_status`, `a_buyer_sees_their_own_sent_inquiries`, **`raising_an_rfq_advances_the_leads_last_activity`** | ✅ (`last_activity_at` added; the leads screen IS `GET /inquiries?role=seller` — spec §11 corrected, D3) |
| — | Non-party cannot view an inquiry / RFQ | `InquiryTest::a_non_party_cannot_view_an_inquiry`, `…different_sellers_business_owner…`; `RfqQuotationTest::a_non_party_cannot_view_an_rfq` | ✅ |
| — | Guests blocked everywhere | `InquiryTest::guests_cannot_use_inquiries`, `RfqQuotationTest::guests_cannot_use_rfqs_or_quotations`, `ReportTest::guests_cannot_report_an_inquiry` | ✅ |

## 3. Coverage gaps

| Gap | Nature | Mitigation |
|---|---|---|
| **In-app chat** (US-INQ-04) | Phase 7 — not started. | Out of scope. |
| **Seller contact-info visibility gate** (US-INQ-05, Open Decision #4) | ~~Not implemented~~ **FIXED 2026-09-06** — `contact_info_visible` entitlement (default off) gates `contact_channels` for buyers on `GET /businesses/{id}` and the product-detail supplier block. Owner/admin unaffected. | Product owner to confirm the default and whether it should be per-business as well as per-plan. |
| **Report → admin ticket / dispute queue** (US-INQ-09, US-ADM-08) | `reports` rows are durable but there is **no moderation workflow, no admin queue, no notification**. | Phase 9. |
| **Content-based spam heuristics** (US-INQ-09) | Only per-user rate limiting. No content flags / hold-and-review. | Backlog; spec says the requirement, not the algorithm. |
| **"Last activity" on the leads list** (US-ANL-03) | ~~Not surfaced~~ **FIXED 2026-09-06** — `last_activity_at` = latest of {inquiry touched, RFQ raised, quotation sent}. Message activity folds in with Chat (Phase 7). | — |
| **`GET /businesses/{id}/leads` route** (spec §11 / US-ANL-03) | Doesn't exist; leads = `GET /inquiries?role=seller&lead_status=`. **Spec §11 corrected 2026-09-06.** | Resolved (D3). |
| **`needed_by_date` / `valid_until` date-floor** (D7) | **Fixed earlier** — `after_or_equal:today` / `after:now`. | Resolved. |
| **Wall-clock rate-limit window** | Tests hit the limit in one process; the 60s reset isn't exercised. | Manual case §4-1. |
| **Buyer-side inquiry limit** | Code path exists but is a no-op — no buyer plan defines `inquiry_limit`. Untested against a real limit. | Re-test once buyer plans gain the key (Open Decision #2). |

## 4. Manual QA test cases

### QA-6-1 · Rate limit engages and then recovers (US-INQ-09)
- **Pre:** set `inquiries.throttle.create_per_minute` low (e.g. 2) in config, or use the default 10.
- **Steps:** as one buyer, send N+1 inquiries in <60s. Then send an RFQ within the same minute.
  Wait 60s; send again.
- **Expected:** the (N+1)th inquiry → **429 / 4290**, `body.throttle` with a "wait N seconds"
  message. The RFQ shares the same counter → also 429. After the window, both succeed.
- **Result:** ☐ pass ☐ fail

### QA-6-2 · Seller inquiry-limit is a clear block, not a silent drop (US-INQ-01, BR-INQ-02)
- **Pre:** a seller on a plan with `inquiry_limit = 1`; `inquiry_count` already at 1.
- **Steps:** as a buyer, `POST /inquiries` to that seller. Then `POST /inquiries/{id}/rfqs`
  on an existing inquiry with that seller.
- **Expected:** both → **422 / 4231**, `body.inquiry_limit` with a message that names the seller
  as currently unreachable via this channel. No `inquiries`/`rfqs` row created. `GET .../usage`
  for the seller shows `inquiry_count` unchanged.
- **Result:** ☐ pass ☐ fail

### QA-6-3 · Quotation expiry lifecycle (US-INQ-03, D7)
- **Steps:** seller creates a quotation with `valid_until` = tomorrow → `GET /rfqs/{id}` shows
  `expired: false`. In staging, move `valid_until` to yesterday (or wait) → re-fetch. Then try
  to create a quotation with `valid_until` = yesterday.
- **Expected:** first read `expired: false`; after the date passes `expired: true` and the client
  treats it as non-actionable. Creating a past-dated quotation → **400 `body.valid_until`**
  (after the D7 fix).
- **Result:** ☐ pass ☐ fail

### QA-6-4 · Lead pipeline end to end (US-INQ-06/07, US-ANL-03)
- **Steps:** buyer sends 3 inquiries to one seller. Seller `GET /inquiries?role=seller` (sees 3,
  all `new`). Seller PATCHes one to `in_progress`, one to `done`, one to `not_completed`. Seller
  `GET /inquiries?role=seller&lead_status=done` (sees 1). Buyer `GET /inquiries?role=buyer`
  (sees their 3). Buyer tries to PATCH a lead_status → 403. PATCH an invalid status → 400.
- **Expected:** all as described; seller list is scoped to their business only; a second seller
  cannot see these.
- **Result:** ☐ pass ☐ fail

### QA-6-5 · Seller contact info visibility (US-INQ-05)
- **Steps:** as a buyer, `GET /businesses/{sellerId}` and `GET /products/{id}` for one of that
  seller's products, first with the seller on a plan **without** `contact_info_visible`, then
  after granting it (admin edits the plan entitlement).
- **Expected:** without the entitlement → no `contact_channels` in either response. With it →
  `contact_channels` present for the buyer in both. Seller/admin views always show it.
- **Result:** ☐ pass ☐ fail

### QA-6-6 · Report an abusive inquiry (US-INQ-09)
- **Steps:** buyer `POST /inquiries/{id}/reports {reason:"spam"}`; seller does the same on
  another inquiry; a third, unrelated user tries to report → 403; empty reason → 400.
- **Expected:** 201 with `report.reporter_id` set correctly for each party; `reports` row is
  create-only (no `updated_at`). **Note:** no admin is notified and no queue exists yet (Phase 9).
- **Result:** ☐ pass ☐ fail

## 5. Spec ⇄ implementation notes

- **Spec §11 corrected 2026-09-06** to match the implemented surface: `PATCH /inquiries/{id}`,
  `POST /inquiries/{id}/rfqs`, and "the leads screen is `GET /inquiries?role=seller`".
- **Schema:** `inquiries` has `lead_status` (+ `message`, `updated_at`) — no separate `status`
  column, no `leads` table (BR-INQ-01). §10.5 note made explicit.
- An RFQ **re-checks** the seller `inquiry_limit` but does **not** re-increment `inquiry_count`.
- **D7:** `StoreRfqRequest.needed_by_date` → `after_or_equal:today`;
  `StoreQuotationRequest.valid_until` → `after:now`.
