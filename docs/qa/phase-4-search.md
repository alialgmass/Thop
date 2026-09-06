# Phase 4 QA — Search

Module: `Modules/Search` (reads `Modules/Catalog` tables + resources). Spec: issue #12 ·
US-SRC-01..11 · BR-SRC-01/02 · PRF-NFR-01 · §13 Search Architecture.

> **Catalog dependency — Partial / Phase 3 Pending.** Search queries the `products`,
> `product_media`, `product_price_tiers` tables and reuses `ProductCardResource` /
> `ProductDetailResource` from the Catalog module. Those tables + resources exist; **product
> write/lifecycle (Phase 3) does not.** All Phase 4 tests build products via factory. Real
> catalog data quality is unverifiable until Phase 3.

## 1. Status summary

| Axis | State |
|---|---|
| Implementation | ✅ `SearchNormalizer`, `ProductSearchService`, `SupplierSearchService`, `FeaturedRanker`, `ZeroResultLogger`; 4 public endpoints (`optional.sanctum`) |
| Automated Tests | ✅ **37 passing** (88 assertions) — ProductSearch 19, SupplierSearch 5, SearchNormalizer 13 (data-driven) |
| Manual QA | ⬜ **NOT RUN** — cases in §4 |
| Postman Coverage | ✅ 4/4 endpoints with filter/sort/pagination examples |
| Documentation | ✅ `docs/API_REFERENCE.md` §3 items 25–28 |
| **Verification Status** | **DOCUMENTED** — PRF-NFR-01 (performance) not verified; FULLTEXT path never tested |

## 2. Requirements traceability

| Req | Acceptance criterion | Automated test(s) | Verdict |
|---|---|---|---|
| US-SRC-01 | Spelling-variant term → reasonable fuzzy matches (AR + EN) | `ProductSearchTest::free_text_matches_a_common_spelling_variant`, `english_synonym_matches`; `SearchNormalizerTest` (11 cases: alef/hamza, taa-marbuta, alef-maqsura, tashkeel, synonyms) | 🟡 (SQLite `LIKE` path only — MySQL FULLTEXT untested, §4-1) |
| US-SRC-02 | Multiple filters → intersection, paginated | `ProductSearchTest::filters_combine_as_an_intersection`, `color_and_width_filters_narrow…`, `moq_max_filter_includes_null_moq…`, `availability_filter_excludes_out_of_stock…`, `an_out_of_range_page_returns_an_empty_list…` | ✅ |
| US-SRC-03 | Sort re-orders without losing filters | `ProductSearchTest::sort_newest_returns_the_most_recent_first`, `sort_by_price_orders_ascending_and_keeps_filters`, `sort_supplier_rating_degrades_to_verified_first` | ✅ |
| US-SRC-04 | Browse by fabric type | `ProductSearchTest::filters_combine_as_an_intersection` (fabric_type filter), `an_unknown_filter_id_yields_an_empty_result_not_an_error` | ✅ |
| US-SRC-05 | Product detail: full specs, images, supplier + badge, CTAs | `ProductSearchTest::the_public_product_detail_hides_internal_fields_and_404s_for_non_visible` | ✅ |
| US-SRC-06 | Navigate to supplier's full catalog | `ProductSearchTest::the_supplier_catalog_is_scoped_and_filterable` | ✅ |
| US-SRC-07 | Supplier search by governorate / specialty / verification | `SupplierSearchTest::it_filters_by_governorate_and_verification_status`, `it_matches_specialty_and_free_text_on_company_name`, `customers_never_appear…`, `suspended_suppliers_are_excluded` | ✅ |
| US-SRC-10 / BR-SRC-01 | Featured items ranked higher AND labeled "Featured" | `ProductSearchTest::featured_products_outrank_a_newer_non_featured_product`, `the_featured_boost_disappears_when_the_subscription_lapses`; `SupplierSearchTest::a_featured_supplier_is_ranked_first_and_flagged` | ✅ |
| US-SRC-11 | Zero-result term logged, friendly empty state | `ProductSearchTest::a_zero_result_search_is_logged_and_returns_a_friendly_empty_state`, `a_matched_search_is_not_logged` | ✅ |
| BR-SRC-02 | Only published, non-hidden, non-deleted, non-suspended-owner products | `ProductSearchTest::it_returns_only_published_visible_products`, `products_from_a_suspended_supplier_are_hidden` | ✅ |
| PRF-NFR-01 | <2s p95 on a 100k-product catalog | — | ⬜ **no load test (Phase 10)** |

## 3. Coverage gaps

| Gap | Nature | Mitigation |
|---|---|---|
| **MySQL FULLTEXT relevance & the `search_text` index** | Tests run on in-memory SQLite (`phpunit.xml`), which uses the `LIKE` fallback. The FULLTEXT migration is a no-op on SQLite. So relevance ordering, stemming, and the composite indexes are **never exercised by CI**. | Manual case §4-1 on a MySQL instance. Consider a MySQL CI lane. |
| **PRF-NFR-01 performance** | No synthetic 100k-row catalog, no p95 latency assertion. | Manual/CI case §4-2 (Phase 10). |
| **`sort=supplier_rating`** | Degrades to "verified first, then newest" — there is no rating system in R1 (Implementation Assumption). | Acceptable; note for R-later when ratings land (Open Decision #10). |
| **Supplier "specialty" filter** | Maps to the free-text `business_accounts.activity` via `search_text`, not a controlled taxonomy (Implementation Assumption). | Confirm the mobile client's filter UI matches (free text vs picklist). |
| **Featured weighting** | Fixed in code (`FeaturedRanker::BOOST_POSITIONS = 12`), boolean entitlement only; `search_priority` graded tiers not wired; not applied to price sorts. | Admin-tunable weighting is a later enhancement. |
| **Unverified sellers' products in search** (Open Decision #5) | Currently **visible**, distinguished only by the absent badge. `Product::scopeBuyerVisible` is written so flipping to "verified only" is one line. | Product decision. |
| **Zero-result "low-result" threshold** | Only *exactly zero* is logged; spec mentions a low-result threshold, left at 0 for this phase. | Revisit with the liquidity dashboard (Phase 9). |

## 4. Manual QA test cases

### QA-4-1 · Full-text search quality on MySQL (US-SRC-01, §13)
- **Pre:** MySQL 8 (`DB_CONNECTION=mysql`), `migrate:fresh --seed`, ~200 products across fabric
  types with Arabic + English names, some with tashkeel / spelling variants.
- **Steps:** search `قطنيه`, `قطنية`, `coton`, `كريب`, `krep`, `جينز`. For each, check the top
  results and their order vs a plain `LIKE`. Combine a term with 2–3 `filters[...]`.
- **Expected:** variant queries return the intended products near the top; FULLTEXT ordering is
  at least as good as `LIKE`; filters still intersect; pagination `meta.total` is correct.
- **Result:** ☐ pass ☐ fail — notes:

### QA-4-2 · Search latency at scale (PRF-NFR-01) — Phase 10
- **Pre:** MySQL; seed ~100k products (script). Warm caches.
- **Steps:** run a load tool (k6/ab) against `GET /products` with a mix of term-only,
  filter-only, and term+filter+sort requests, ~50 concurrent, 5 min.
- **Expected:** p95 < 2s; no N+1 (check Telescope/slow-query log); FULLTEXT + composite indexes
  actually used (`EXPLAIN`).
- **Result:** ☐ pass ☐ fail — p95:

### QA-4-3 · Featured is always visible, never silent (BR-SRC-01, US-SRC-10)
- **Pre:** two near-identical products, one from a seller whose plan grants `featured_products`,
  one not; the featured one deliberately older.
- **Steps:** `GET /products?sort=relevance` and `?sort=newest`. Note positions and the
  `featured` flag. Then cancel the featured seller's subscription; repeat.
- **Expected:** with the entitlement — featured product boosted within the page (≤12 positions),
  `featured: true`. After cancellation — no boost, `featured: false`. Never a boost without the flag.
- **Result:** ☐ pass ☐ fail

### QA-4-4 · Non-visible products truly never surface (BR-SRC-02)
- **Steps:** create products in each state: draft, pending_review, published, hidden,
  unavailable, soft-deleted; and one published product whose owner is suspended. Search broadly;
  hit `GET /products/{id}` for each.
- **Expected:** only the `published` + active-owner product appears in the list; every other
  `GET /products/{id}` returns **404** (not 403, not a stub).
- **Result:** ☐ pass ☐ fail

### QA-4-5 · Zero-result logging survives a logging failure (US-SRC-11)
- **Steps:** search a nonsense term as a guest, then as a signed-in user → check `search_logs`
  (term, result_count 0, user_id). Then simulate a `search_logs` write failure (e.g. revoke
  insert, or rename the table in staging) and search again.
- **Expected:** rows written with correct attribution; a matched search writes nothing; a
  logging failure does **not** break the search response (still 200 with results/empty state).
- **Result:** ☐ pass ☐ fail

## 5. Spec ⇄ implementation notes

- `GET /api/v1/products` and `/businesses` were **repointed** to Search (public discovery); the
  seller's own product list moved to `GET /products/mine` (Phase 3). Documented deviation.
- `GET /businesses/{id}/catalog` moved from Catalog to Search and gained the full
  filter/sort/pagination contract.
- Search endpoints use `optional.sanctum` — a token is accepted but never required, used only
  for zero-result attribution.
