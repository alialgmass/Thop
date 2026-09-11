# Phase 9 QA — Admin Dashboard

Modules: `Modules/Admin`, `Modules/Catalog` (review queue), `Modules/Taxonomy` (management),
`Modules/Subscriptions` (plan management), `Modules/Search` (featured placements),
`Modules/Inquiries` (report queue), plus `app/Filament/Resources/Users` (suspend/reactivate).
Spec: issue #29 (parent), tickets #30–#40 · US-ADM-01..10, BR-ADM-01 · SRS §7 "Enough Supply +
Enough Buyers = Marketplace Liquidity".

## 1. Status summary

| Axis | State |
|---|---|
| Implementation | ✅ 11 tickets (#30–#40), all shipped — admin gate, audit-log viewer, product review, taxonomy CRUD, plan management + entitlement-snapshot architecture, featured placements, banners, liquidity dashboard, suspend/reactivate, report queue, assisted onboarding |
| Automated Tests | ✅ **118 passing** across T1–T11 (REST + Filament panel tests for every ticket) |
| Manual QA | ⬜ **NOT RUN** — cases in §4 |
| Postman Coverage | ✅ Admin folder added — every REST endpoint except Banners (Filament-only by design, T6) |
| Documentation | ✅ `docs/API_REFERENCE.md` "Admin (Phase 9, #29)" section |
| **Verification Status** | **DOCUMENTED** |

## 2. Requirements traceability

| Req | Acceptance criterion (spec §4.7) | Automated test(s) | Verdict |
|---|---|---|---|
| US-ADM-01 | Pending verification request → admin approves/rejects, status updates, reason logged, business notified on reject | `Modules/Verification/tests/Feature/AdminVerificationReviewTest`, `Filament/VerificationRequestPanelTest` (Phase 1); notification wired in `NotificationEventSubscriber::onVerificationRejected` (Phase 8) | ✅ (pre-existing, re-verified by T11 audit sweep) |
| US-ADM-02 | Pending-review product → admin approves/rejects/requests edits, visibility updates, seller notified | `Modules/Catalog/tests/Feature/AdminProductReviewTest` (11), `Filament/ProductReviewPanelTest` (8) — approve/reject/hide/request-edits, state guard (409), non-admin 403 | 🟡 approve/reject notify the seller (`onProductApproved`/`onProductRejected`); **`request-edits` (`ProductEditsRequested`) does not yet notify** — no listener wired (documented gap, T2) |
| US-ADM-03 | Admin edits fabric types/materials/colors/units → immediately reflected in product-creation forms and search filters | `Modules/Taxonomy/tests/Feature/AdminTaxonomyTest` (13) — `a_deactivated_term_disappears_from_the_public_read_endpoint`, `a_deactivated_term_is_rejected_by_product_creation_validation`; `Filament/TaxonomyPanelTest` (6) | ✅ "search filters" and "product forms" both source their term lists from the same public taxonomy read endpoint the deactivation test already covers — no separate search-filter-options endpoint exists to test independently (documented in the test file) |
| US-ADM-04 | Admin edits plan price/entitlements/trial length → applies to new subscriptions; existing follow non-retroactive-shrink unless admin forces it (confirmation required) | `Modules/Subscriptions/tests/Feature/AdminSubscriptionPlanTest` (6), `Filament/SubscriptionPlanPanelTest` (8, 2 new for T4) — `a_plain_plan_edit_leaves_existing_subscriptions_entitlements_untouched`, `apply_to_existing_without_confirm_changes_nothing`, `apply_to_existing_with_confirm_updates_every_active_subscription_and_is_audited` | ✅ Required building the actual entitlement-snapshot mechanism (`subscription_entitlement_snapshots`) — before T4 there was no snapshot at all, so a plan edit was already instantly retroactive; see `docs/PROGRESS.md` T4 entry |
| US-ADM-05 | Admin selects featured suppliers/products/banners → appear in designated slots | `Modules/Search/tests/Feature/AdminFeaturedPlacementTest` (10), `Filament/FeaturedPlacementPanelTest` (4); `Modules/Admin/tests/Feature/BannerTest` (3), `Filament/BannerPanelTest` (9) | ✅ `FeaturedRanker` unions an active placement OR plan entitlement; banners via `GET /banners` (public, no auth — for the separate marketplace client) |
| US-ADM-06 | Liquidity dashboard: active sellers/products/buyers, weekly inquiries, zero-result search terms | `Modules/Admin/tests/Feature/LiquidityDashboardTest` (9), `Filament/LiquidityStatsWidgetTest` (2) | ✅ sellers/products are current-state snapshots, buyers/inquiries/zero-result-terms windowed by `?range=` (default 7 days) — both halves proven distinct by `active_sellers_and_products_are_current_state_snapshots_unaffected_by_range` |
| US-ADM-07 | Admin suspends/bans an account (destructive, needs confirmation) → loses access immediately, audit entry created | `Modules/Admin/tests/Feature/AdminAccountModerationTest` (8), `Filament/UserModerationPanelTest` (4) — `confirm=true` required, tokens revoked, next request with the old token → 401 | ✅ "bans" read as suspend in this codebase (no separate ban state in the schema — Implementation Assumption, matches `UserStatus` enum's two non-pending states) |
| US-ADM-08 | Report/dispute → ticket queue admin can investigate and resolve | `Modules/Inquiries/tests/Feature/AdminReportTest` (10, +2 from the T11 review pass), `Filament/ReportPanelTest` (6, +1) | ✅ open-first queue, resolve requires a note, both reportable types (inquiry, chat message) proven on both surfaces |
| US-ADM-09 | Any admin action (ADM-FR-01..08) → written to an immutable audit log | `Modules/Admin/tests/Feature/AuditLogTest` — `entries_cannot_be_updated`, `entries_cannot_be_deleted` (Phase 1, re-verified here); every write path below | ✅ see §5 — all 21 `AuditAction` cases have a passing test asserting the `audit_logs` row |
| US-ADM-10 | Ops staff onboard a supplier on their behalf → `onboarded_by_admin=true`, behaves identically to self-registered thereafter | `Modules/Admin/tests/Feature/AdminBusinessOnboardingTest` (7), `Filament/SupplierOnboardingPanelTest` (4) — `the_onboarded_account_can_authenticate_and_use_seller_endpoints_like_a_self_registered_one` | ✅ same duplicate-phone rule as public registration (`PhoneAlreadyRegisteredException`, shared not re-implemented) |
| BR-ADM-01 | Every admin action is audited | §5 sweep | ✅ |

## 3. Coverage gaps

| Gap | Nature | Mitigation |
|---|---|---|
| **`request-edits` doesn't notify the seller** (US-ADM-02) | `ProductEditsRequested` event dispatched, no listener wired in `NotificationEventSubscriber` | Follow-up — same shape as `ProductApproved`/`ProductRejected`'s existing wiring |
| **`AccountSuspended` doesn't notify anyone** (US-ADM-07, adjacent) | Event dispatched, no listener; no Notification Matrix (§14) row for "account suspended" exists to wire against | Follow-up once a matrix row is decided — not invented here (CLAUDE.md rule 5) |
| **`units` taxonomy management has no consumer** (US-ADM-03, adjacent) | `products.unit` is a hardcoded enum, not wired to the `units` taxonomy table T3 manages | User-approved deferral (T3) — a `unit_id` FK migration is separate, larger work |
| **No admin REST surface for banners** (US-ADM-05) | T6's own acceptance criteria ask only for Filament CRUD + the one public read endpoint (unlike every other Phase 9 ticket's "Filament + REST parity") | By design, not a gap — an initial pass built REST anyway and it was removed as scope creep during code review |
| **"Ban" is not a distinct state from "suspend"** (US-ADM-07) | Spec says "suspends/bans"; the schema (`UserStatus`) has only `active`/`suspended`/`pending_type_selection` | Implementation Assumption — flagged here, not silently decided; revisit if the product owner wants a harder, non-reversible "ban" distinct from a reversible "suspend" |
| **Manual QA** | Not executed for any Phase 9 ticket | Cases in §4; extend issue #25's manual pass to cover Phase 9 (currently scoped to 0/1/2/4/5/6) |

## 4. Manual QA test cases

### QA-9-1 · Non-retroactive plan edit vs. forced apply (US-ADM-04)
- **Pre:** a plan with `product_limit=10`; a business subscribed to it.
- **Steps:** admin edits the plan's `product_limit` to `50` (plain edit, no confirm). Check the
  subscriber's `GET /subscriptions/{id}/usage`. Then admin calls
  `POST /admin/subscription-plans/{id}/apply-to-existing` with `confirm: true`. Re-check usage.
- **Expected:** after the plain edit, the subscriber's limit is still `10` (unchanged). After
  `apply-to-existing`, it becomes `50`. Both actions appear in `GET /admin/audit-logs`
  (`plan.updated` then `plan.applied_to_existing`).
- **Result:** ☐ pass ☐ fail

### QA-9-2 · Suspend revokes access immediately (US-ADM-07)
- **Pre:** an active seller with a valid bearer token.
- **Steps:** as that seller, confirm any authenticated request succeeds. Admin
  `POST /admin/accounts/{id}/suspend {confirm:true}`. Immediately retry the seller's original
  request with the **same** token.
- **Expected:** suspend → 200, `account.suspended` audit row. The seller's next request with the
  old token → **401** (token deleted, not just a status flag). `GET /products` for that seller's
  products → the products have disappeared from search (existing `scopeBuyerVisible` behavior).
- **Result:** ☐ pass ☐ fail

### QA-9-3 · Assisted onboarding produces a normal account (US-ADM-10)
- **Steps:** admin `POST /admin/businesses` with a new phone + business profile. Log in as that
  phone/password via the normal `POST /auth/login`. Use the resulting token against
  `GET /products/mine`.
- **Expected:** 201 on onboarding, `supplier.onboarded` audit row, `onboarded_by_admin: true` on
  the business. Login succeeds immediately (no OTP, no account-type-selection step — already
  `active` with `account_type` set). `GET /products/mine` succeeds (200, empty list) exactly as
  it would for a self-registered seller with no products yet.
- **Result:** ☐ pass ☐ fail

### QA-9-4 · Report queue end to end (US-ADM-08)
- **Steps:** buyer reports an inquiry (`POST /inquiries/{id}/reports`). Admin
  `GET /admin/reports` (sees it, `status: open`, both parties shown). Admin
  `POST /admin/reports/{id}/resolve` with no `note` → expect 400. Retry with a note → 200.
  Admin retries resolve on the same report → expect 409.
- **Expected:** as described; the resolved report drops to the bottom of a subsequent
  `GET /admin/reports` (open-first ordering); `report.resolved` audit row with the note.
- **Result:** ☐ pass ☐ fail

### QA-9-5 · Deactivating a taxonomy term takes effect with no deploy (US-ADM-03)
- **Steps:** admin deactivates a fabric type (`PATCH /admin/taxonomy/fabric-types/{id} {is_active:false}`).
  Immediately: (a) `GET /taxonomy/fabric-types` (public), (b) try `POST /products` using that
  fabric type's id.
- **Expected:** (a) the term is gone from the public list; (b) product creation is rejected
  (422, `fabric_type_id` fails the active-only exists rule) — both with no deploy, no cache
  clear, immediately after the PATCH.
- **Result:** ☐ pass ☐ fail

## 5. BR-ADM-01 audit-completeness sweep (T11)

Every `AuditLog::record()` call site in the codebase, and the test asserting its `audit_logs`
row:

| `AuditAction` | Write path | Test |
|---|---|---|
| `verification.approved` / `.rejected` | `Verification\Actions\DecideVerificationRequest` | `AdminVerificationReviewTest`, `VerificationRequestPanelTest` (Phase 1) |
| `product.approved` / `.rejected` / `.hidden` / `.edits_requested` | `Catalog\Actions\DecideProductReview` | `AdminProductReviewTest`, `ProductReviewPanelTest` |
| `taxonomy.created` / `.updated` / `.deactivated` | `Taxonomy\Actions\ManageTaxonomyTerm` | `AdminTaxonomyTest`, `TaxonomyPanelTest` |
| `plan.created` / `.updated` / `.applied_to_existing` | `Subscriptions\Actions\ManageSubscriptionPlan` | `AdminSubscriptionPlanTest`, `SubscriptionPlanPanelTest` |
| `featured.placed` / `.removed` | `Search\Actions\ManageFeaturedPlacement` | `AdminFeaturedPlacementTest`, `FeaturedPlacementPanelTest` |
| `banner.created` / `.updated` / `.removed` | `Admin\Actions\ManageBanner` | `BannerPanelTest` (Filament-only, per T6) |
| `account.suspended` / `.reactivated` | `Admin\Actions\SuspendAccount` / `ReactivateAccount` | `AdminAccountModerationTest`, `UserModerationPanelTest` |
| `report.resolved` | `Inquiries\Actions\ResolveReport` | `AdminReportTest`, `ReportPanelTest` |
| `supplier.onboarded` | `Admin\Actions\OnboardSupplier` | `AdminBusinessOnboardingTest`, `SupplierOnboardingPanelTest` |

Every call site passes an `AuditAction` enum case — no admin write path anywhere in the codebase
passes a raw string literal to `AuditLog::record()` (grep-verified across `Modules/`). Immutability
(`entries_cannot_be_updated`/`entries_cannot_be_deleted`, `AuditLogTest`, Phase 1) re-run clean.
