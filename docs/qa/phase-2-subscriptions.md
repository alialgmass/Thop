# Phase 2 QA — Subscriptions & Entitlements

Module: `Modules/Subscriptions`. Spec: issue #30 · US-SUB-01..08 · BR-SUB-01/02/03 ·
SEC-NFR-04 · Appendix A.

## 1. Status summary

| Axis | State |
|---|---|
| Implementation | ✅ Plans + entitlements (key/value), `EntitlementService` gate, subscribe/upgrade/downgrade/cancel, trials, `subscriptions:process-period-ends` command, Filament plan + subscription resources |
| Automated Tests | ✅ **64 passing** (142 assertions) incl. 11 Filament tests |
| Manual QA | ⬜ **NOT RUN** — cases in §4 |
| Postman Coverage | ✅ 5/5 REST endpoints. Admin trial/extend/cancel actions are Filament-only (manual). |
| Documentation | ✅ `docs/API_REFERENCE.md` §3 items 20–24 |
| **Verification Status** | **DOCUMENTED** — D5 envelope deviations fixed 2026-09-06 |

## 2. Requirements traceability

| Req | Acceptance criterion | Automated test(s) | Verdict |
|---|---|---|---|
| US-SUB-01/02 | Plans listed per account type; Importer sees Basic/Pro/Premium | `SubscriptionPlanTest::plans_are_listed_filtered_by_account_type`, `only_active_plans_are_shown` | ✅ |
| US-SUB-01/02 | Plan carries its entitlements | `SubscriptionPlanTest::plans_include_entitlements` | ✅ |
| US-SUB-03 / SEC-NFR-04 / BR-SUB-01 | Entitlement checks entirely server-side, never client plan name | `EntitlementServiceTest` (8), `ClientTamperingTest` (5) | ✅ |
| US-SUB-03 | Numeric limit checked against usage counter | `EntitlementServiceTest::can_checks_numeric_limit_against_usage`, `increment_and_decrement_usage_work_correctly` | ✅ |
| US-SUB-04 | "My Subscription": plan, usage vs limits, renewal date | `SubscriptionShowTest` (4); `usage` shape exercised in `UpgradeSubscriptionTest`/`SubscribeTest` | 🟡 (no billing history — see §3) |
| US-SUB-05 / BR-SUB-02 | Upgrade effective immediately | `UpgradeSubscriptionTest::upgrade_creates_new_subscription_immediately`, `upgrade_is_effective_immediately` | ✅ |
| US-SUB-05 / BR-SUB-02 | Downgrade / cancel at end of paid term, no truncation | `DowngradeSubscriptionTest` (3), `PeriodEndCommandTest::command_applies_pending_downgrade…`, `…cancels_subscription_marked…` | ✅ |
| US-SUB-05 | Cannot upgrade a non-active subscription | `UpgradeSubscriptionTest::cannot_upgrade_non_active_subscription` (422/4221) | ✅ |
| US-SUB-06 | Recurring billing | — | ⬜ **not implemented (R4)** |
| US-SUB-07 | Admin trial/promo activates entitlements without a payment method | `TrialPromoTest` (4), `SubscriptionPanelTest::an_admin_can_grant_a_trial_from_the_view_page` | ✅ |
| US-SUB-08 / BR-SUB-03 | Expiry → restricted state, products hidden not deleted | `SubscriptionExpiryTest` (8), `PeriodEndCommandTest`, **`SubscriptionCatalogVisibilityTest` (8) — product hiding now wired into `Product::scopeBuyerVisible`** | ✅ |
| BR-SUB-01 | Forged plan/entitlement claim rejected | `ClientTamperingTest::forged_plan_id_in_request_body_is_ignored`, `entitlement_always_checked_server_side_not_from_client` | ✅ |
| — | Cannot subscribe to a plan of another account type | `SubscribeTest::cannot_subscribe_to_plan_of_different_account_type` (422/4222) | ✅ |
| — | Cannot hold two active subscriptions | `SubscribeTest::cannot_subscribe_with_duplicate_active_subscription` (409/4091) | ✅ |
| — | No business profile → cannot subscribe | `SubscribeTest::user_without_business_profile_cannot_subscribe` (native 403) | ✅ |
| — | Ownership on view/usage/update | `SubscriptionShowTest::other_user_cannot_view…` (403/4031), `ClientTamperingTest::non_owner_cannot_modify_subscription` | ✅ |
| — | Scheduled period-end processing | `PeriodEndCommandTest` (5) | ✅ |
| MNT-NFR-02 | Plans/entitlements admin-editable without deploy | `Filament/SubscriptionPlanPanelTest` (6) | ✅ |

## 3. Coverage gaps

| Gap | Nature | Mitigation |
|---|---|---|
| **Recurring billing / payment provider** (US-SUB-06) | Not implemented — R4. `price` is nullable; nothing charges. | Out of R1 scope. |
| **Billing / invoice history** (US-SUB-04) | Not implemented — depends on payments. `usage` endpoint gives plan + usage + renewal date only. | Lands with Payments (R4). |
| **Product hiding on expiry** (BR-SUB-03) | ~~Not wired~~ **FIXED 2026-09-06** — `Product::scopeBuyerVisible` requires the owner's subscription not be lapsed; `SubscriptionCatalogVisibilityTest` covers expiry-hides / renewal-restores / `/products/mine` unaffected / detail-404. Note: a business that **never** subscribed keeps current visibility (deliberate — spec is about *expiry*). | Confirm the never-subscribed carve-out with the product owner. |
| **`POST` / `PATCH /subscriptions` validation envelope** (D5) | ~~Native 422~~ **FIXED 2026-09-06** — both requests extend `BaseRequest`; validation errors are enveloped `400/4000` like every other module. | — |
| **Concurrency** — two subscribe calls racing, double period-end runs | Not simulated. | Note for Phase 10. |
| **Real proration** | Explicitly rejected (Open Decision D2, user-approved). | N/A. |
| **Numeric plan prices** | `price` nullable everywhere — pricing is Open Decision #1. | Product decision. |

## 4. Manual QA test cases

### QA-2-1 · Envelope consistency on subscription writes (D5 — regression check)
- **Steps:** `POST /subscriptions` with `{}` (no `plan_id`). `PATCH /subscriptions/{id}` with
  `{"action":"banana"}`.
- **Expected:** both → **HTTP 400**, `{ custom_code: 4000, status: false, body: { <field>: [msg] } }`
  — identical shape to `POST /businesses` `{}`. (Covered by automation; this is a manual
  sanity check on staging.)
- **Result:** ☐ pass ☐ fail

### QA-2-2 · Downgrade does not shorten the paid period (BR-SUB-02, US-SUB-05)
- **Pre:** an active subscription on Pro with `current_period_end` ~20 days out.
- **Steps:** `PATCH /subscriptions/{id}` `{"action":"downgrade","plan_id":<Basic>}`. Immediately
  `GET /subscriptions/{id}` and `GET .../usage`. Then run
  `php artisan subscriptions:process-period-ends` **before** the period end, and again **after**
  moving the clock past it (staging: adjust `current_period_end` in DB).
- **Expected:** right after downgrade — still Pro, `notes.pending_plan_id = Basic`,
  `current_period_end` unchanged, Pro entitlements still active. Command before period end — no
  change. Command after period end — plan swaps to Basic, new future period end, still active.
- **Result:** ☐ pass ☐ fail

### QA-2-3 · Cancel keeps access until period end, then restricts (US-SUB-08, BR-SUB-03)
- **Steps:** `PATCH {action:"cancel"}` → `GET /subscriptions/{id}` (still active,
  `notes.cancel_at_period_end = true`). Move clock past period end → run the command.
- **Expected:** after the command, status = `Cancelled` (or `Restricted` if it also expired),
  `notes` cleared, entitlement `can()` now false.
- **Result:** ☐ pass ☐ fail

### QA-2-4 · Restricted subscription actually hides products (BR-SUB-03) — covered by automation
- **Pre:** a seller with published products (3.1 products exist) and an active subscription.
- **Steps:** let the subscription lapse (or force `Restricted`); run the period-end command;
  `GET /api/v1/products?filters[business_account_id]=<seller>` and `GET /products/{id}` as a
  buyer; `GET /products/mine` as the seller. Then renew and repeat.
- **Expected:** after the lapse the seller's products **disappear from buyer search** and
  `GET /products/{id}` → 404, but they still show in `/products/mine` and are not deleted;
  renewal restores them. (Automated: `SubscriptionCatalogVisibilityTest`.)
- **Result:** ☐ pass ☐ fail

### QA-2-5 · Admin trial grant via Filament (US-SUB-07)
- **Pre:** admin user; a business with a **cancelled/expired** subscription.
- **Steps:** `/admin` → Subscriptions → open the record → Grant Trial (pick plan + trial end).
  Then Extend Period on an active one; then Cancel an active one.
- **Expected:** Grant Trial → status Active, `is_trial = true`, `current_period_end = null`,
  `trial_ends_at` set, no payment asked. Extend → `current_period_end` = chosen date. Cancel →
  status Cancelled. Grant Trial button hidden while a subscription is already active.
- **Result:** ☐ pass ☐ fail

### QA-2-6 · Client tampering (SEC-NFR-04, BR-SUB-01)
- **Steps:** as a Basic subscriber, call an entitlement-gated flow (e.g. try to exceed a limit).
  Then replay the request with a forged body claiming `plan: "Premium"` / extra
  `entitlements[...]`.
- **Expected:** identical result both times — the gate resolves from the DB; the forged fields
  are ignored. `GET .../usage` reflects the real Basic limits.
- **Result:** ☐ pass ☐ fail

## 5. Spec ⇄ implementation notes

- `GET /subscription-plans` requires `auth:sanctum` (spec doesn't say). A misleadingly-named
  test (`unauthenticated_user_can_list_plans`) actually asserts the 401. Rename or confirm intent.
- Subscription 403s use a **custom** `4031` envelope (unlike other modules' native 403).
- `subscriptions.notes` merges intent without clobbering (P3 code-review fix).
