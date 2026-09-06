# Phase 5 QA — Favorites + Comparison

Modules: `Modules/Favorites`, `Modules/Comparison`. Spec: issue #13 · US-SRC-08/09,
US-BUY-02/05 · BR-FAV-01, BR-CMP-01 · §34/§35.

> Both features operate on Products (Catalog tables — **Partial / Phase 3 Pending**, factory
> data only) and Business Accounts. The favorites/comparison logic itself is complete.

## 1. Status summary

| Axis | State |
|---|---|
| Implementation | ✅ Polymorphic `favorites` (unique index), `FavoritableType` enum, 3 EP; on-demand `GET /compare` (no table), 4-item cap |
| Automated Tests | ✅ **15 passing** — Favorites 9 (21 assn), Comparison 6 (15 assn) |
| Manual QA | ⬜ **NOT RUN** — cases in §4 |
| Postman Coverage | ✅ 4/4 endpoints |
| Documentation | ✅ `docs/API_REFERENCE.md` §3 items 29–32 |
| **Verification Status** | **DOCUMENTED** — one minor envelope bug (D6) |

## 2. Requirements traceability

| Req | Acceptance criterion | Automated test(s) | Verdict |
|---|---|---|---|
| US-SRC-08 | Favorite a product or a supplier | `FavoriteTest::a_user_saves_a_product`, `a_user_saves_a_supplier` | ✅ |
| US-SRC-08 / BR-FAV-01 | Favoriting the same item twice → no duplicate row | `FavoriteTest::favoriting_the_same_item_twice_does_not_create_a_duplicate` (200, not 201; 1 row) | ✅ |
| US-SRC-08 | Remove someone else's favorite → 403 | `FavoriteTest::removing_someone_elses_favorite_is_forbidden` | ✅ |
| US-SRC-08 | List scoped to current user, filterable by type | `FavoriteTest::the_list_is_scoped_to_the_current_user_and_filterable_by_type` | ✅ |
| US-BUY-02 | Save suppliers to a private list (same pattern) | `FavoriteTest::a_user_saves_a_supplier`, `…filterable_by_type` (`?type=supplier`) | ✅ |
| — | Favoriting a missing target → 404 | `FavoriteTest::favoriting_a_missing_target_is_rejected` | 🟡 (returns 404 but `custom_code` 2000 — D6) |
| — | Unknown type → validation error | `FavoriteTest::an_unknown_type_is_a_validation_error` (400) | ✅ |
| — | Guests cannot use favorites | `FavoriteTest::guests_cannot_use_favorites` (401) | ✅ |
| US-SRC-09 / BR-CMP-01 | Compare up to 4; 5th rejected with a clear message | `ComparisonTest::comparing_five_items_is_rejected_with_a_clear_message` (400, message names "4") | ✅ |
| US-SRC-09 | Products compared side-by-side | `ComparisonTest::it_compares_products_side_by_side` | ✅ |
| US-SRC-09 | Suppliers compared side-by-side | `ComparisonTest::it_compares_suppliers` | ✅ |
| US-SRC-09 | Computed on-demand, no storage table | (design — `GET /compare` takes ids; no migration) confirmed by code | ✅ |
| US-BUY-05 | Price comparison across suppliers; handle missing numeric price | `ComparisonTest::it_compares_products_side_by_side` (asserts `price_on_contact: true` on one item) | 🟡 (see §3) |
| — | Non-visible / suspended items dropped → `missing_ids` | `ComparisonTest::non_visible_products_are_dropped_and_reported_as_missing`, `a_suspended_suppliers_profile_is_not_comparable` | ✅ |
| — | Guests cannot compare | `ComparisonTest::guests_cannot_compare` (401) | ✅ |

## 3. Coverage gaps

| Gap | Nature | Mitigation |
|---|---|---|
| **`POST /favorites` 404 carries `custom_code: 2000`** (D6) | Minor bug — a not-found response should be `status:false` with an error code, not the success default. | One-line fix on the not-found branch. Manual case §4-2 confirms. **Fixed in this pass** — see §5. |
| **Price comparison with genuinely mixed price shapes** (US-BUY-05, Open Decision #3) | Only one test touches `price_on_contact`. No case for "3 suppliers, 2 numeric + 1 on-contact, sorted/aligned". | Manual case §4-3. The mobile client's comparison UI must handle `price: null` + `price_on_contact: true`. |
| **No toggle-by-target endpoint** | `DELETE` takes a favorite id only (per spec §11.1). The client must track favorite ids or re-list. | By design; confirm with the client team. |
| **`SupplierCardResource` lives in `Modules/Search`** not `Modules/Businesses` | Minor architectural inconsistency with `ProductCardResource` (in Catalog). Deferred. | Cosmetic. |
| **Comparison item shape asymmetry** | Products return `ProductDetailResource` (rich); suppliers return `SupplierCardResource` (thin — id, name, activity, governorate, verified, featured). | Confirm the supplier comparison view has enough fields; may need a `SupplierDetailResource`. |

## 4. Manual QA test cases

### QA-5-1 · Idempotent favorite + race (BR-FAV-01)
- **Steps:** `POST /favorites {type:product, id:X}` twice quickly. Then fire ~5 parallel
  identical POSTs (e.g. `xargs -P5`).
- **Expected:** first → 201; all others → 200, same `favorite.id`; exactly **one** row in
  `favorites` for `(user, product, X)`. No 500s from the unique-index race.
- **Result:** ☐ pass ☐ fail

### QA-5-2 · Favoriting a non-existent / non-visible target (D6)
- **Steps:** `POST /favorites {type:product, id:99999999}`. `POST /favorites {type:supplier,
  id:<suspended supplier>}`.
- **Expected:** HTTP **404**, `status: false`, and (after the D6 fix) a **4xx `custom_code`**
  (not 2000), localized "target not found" message. No row created.
- **Result:** ☐ pass ☐ fail

### QA-5-3 · Price comparison with mixed price shapes (US-BUY-05)
- **Pre:** 4 products for the "same fabric spec" — 3 with numeric `price` + `currency`, 1 with
  `price_on_contact: true` (`price: null`).
- **Steps:** `GET /compare?type=product&ids=a,b,c,d`. Inspect each item's `price`,
  `price_on_contact`, `currency`, `price_tiers`.
- **Expected:** all 4 in `items`; the on-contact one has `price: null, price_on_contact: true`;
  the client can render "Price on contact" without a crash or a "0" price.
- **Result:** ☐ pass ☐ fail

### QA-5-4 · Comparison cap + de-dup + missing ids (BR-CMP-01)
- **Steps:** `GET /compare?type=product&ids=1,1,2,3` (dup) → then `?ids=1,2,3,4,5` → then
  `?ids=1,2,900` (900 not visible).
- **Expected:** dup collapses to 3 items; 5 ids → **400** with a message naming the limit "4";
  `900` comes back in `missing_ids`, the rest in `items`.
- **Result:** ☐ pass ☐ fail

### QA-5-5 · Favorites list `item` payload matches search cards
- **Steps:** favorite a product and a supplier; `GET /favorites`, then `?type=product`,
  then `?type=supplier`.
- **Expected:** newest first; each entry's `item` is the same shape as the corresponding
  search card (`ProductCardResource` / `SupplierCardResource`); type filter works.
- **Result:** ☐ pass ☐ fail

## 5. Spec ⇄ implementation notes

- Comparison returns a `missing_ids` helper array — extra over the spec, accepted (documented).
- Comparison `max:4` → **HTTP 400** with a clear message (spec: "blocked with a clear limit
  message"). ✅
- **D6 fix applied in this pass:** `FavoriteController@store` now returns
  `custom_code 4040` on the target-not-found branch instead of the default 2000. See the
  Phase-5 change note in the final report.
