# THOB — Implementation-Ready Product Specification
**From SRS v1.0 (27 Aug 2026) + Product Deck → User Stories, Architecture, Data Model, APIs**

Source documents (primary source of truth):
1. `THOB_SRS_AR_v1.0.pdf` — SRS (Arabic), authoritative for functional/non-functional requirements, releases, business rules.
2. `abdullah_app.pdf` — Product Deck, authoritative for business/product context, positioning, MVP rationale.

This document does not add, remove, or re-scope any SRS requirement. Every requirement ID from the SRS is preserved and mapped. Where the SRS is ambiguous or silent, this is called out explicitly as an **Implementation Assumption** or an **Open Decision** (SRS Appendix D) — never silently resolved.

---

## 0. Document Conventions

- Requirement IDs use the SRS pattern: `NN-MODULE-FR` (functional) and `NN-CATEGORY-NFR` (non-functional).
- Priority: **M**ust / **S**hould / **C**ould (SRS §1.2).
- Releases (SRS §1.2, §2.7):
  | Release | Definition (SRS) |
  |---|---|
  | R1 | MVP — Importer ↔ Wholesaler loop |
  | R2 | Add Retailers + in-app Orders |
  | R3 | Open platform to End Customer |
  | R4 | Online payment, shipping, tracking, advanced services |
- Actors: Importer/Supplier (المستورد), Wholesaler/Distributor (تاجر الجملة), Retailer/Shop (تاجر التجزئة), End Customer (العميل النهائي), Admin, System.
- Any figure not stated in the SRS (pricing, numeric plan limits) is marked **"Price/limit not specified in source document."** per SRS Appendix D items #1–#2. This is never invented.

---

## 1. Release Strategy (Authoritative — SRS §2.7)

The SRS launch strategy answers the "chicken-and-egg" problem explicitly and must not be reordered:

| Phase | Release | Goal | Segment added |
|---|---|---|---|
| 01 — Build Supply | R1 | Prove importers will list goods | Importers |
| 02 — Activate Demand | R1 | Prove wholesalers will join to find that goods | Wholesalers/Distributors |
| 03 — Expand the Marketplace | R2 | Add retail layer (buy + sell) | Retailers |
| 04 — Open to Consumers | R3 | Open to the public | End Customers |
| 05 — Transact | R4 | Move the transaction inside the platform | Everyone |

**R1 must prove, and only prove (SRS §2.7, MVP acceptance §7.1):** Importer registers → verified → builds profile → publishes catalog → Wholesaler discovers/searches → finds supplier → sends Inquiry/RFQ → receives Quotation → chats → relationship continues **outside the app** (SRS §2.5 constraint #4: "Deal → Contact → Discovery → App completes the deal outside the app in R1").

**R1 explicitly excludes** (SRS §2.5 constraint #4, §4.5 note "مساحة التأجيل"): payment gateway, wallet, installments, in-app checkout, shipment tracking. FR-ORD-07/08/09 are deliberately deferred to R4. This is a business decision quoted directly in the SRS, not an architectural simplification Claude is introducing.

R1 **does** include paid Importer subscriptions (SUB-FR-01..08) — the SRS explicitly states subscriptions are excluded from the R1 payment deferral because they are the revenue mechanism (SRS §2.5 constraint #5).

---

## 2. Functional Requirement Traceability Matrix

Full list of every functional requirement in the SRS §4, mapped to release, priority, and the User Story IDs that implement it (User Stories are in Section 4). Nothing is left unrepresented.

### 4.1 Accounts & Identity (ACC)
| Req ID | Requirement (summary) | Release | Priority | User Story |
|---|---|---|---|---|
| ACC-FR-01 | Register with phone verified via OTP; email optional | R1 | M | US-ACC-01 |
| ACC-FR-02 | Choose one account type at registration: Importer/Wholesaler/Retailer/Customer | R1 | M | US-ACC-02 |
| ACC-FR-03 | Business accounts capture company name, activity, governorate, address, contact person, channels | R1 | M | US-ACC-03 |
| ACC-FR-04 | Business account uploads verification docs (commercial register, tax card) for admin review | R1 | M | US-ACC-04 |
| ACC-FR-05 | Verified account shows "Verified Supplier/Activity" badge platform-wide | R1 | M | US-ACC-05 |
| ACC-FR-06 | End Customer account created via phone verification only, no documents | R3 | M | US-ACC-06 |
| ACC-FR-07 | Login/logout, session persistence, access recovery | R1 | M | US-ACC-07 |
| ACC-FR-08 | Business account supports sub-users (e.g., sales staff) with limited permissions | R2 | S | US-ACC-08 |
| ACC-FR-09 | User can request account deletion; processed per retention policy §5.3 | R1 | M | US-ACC-09 |
| ACC-FR-10 | Account type change with admin approval (e.g., wholesaler becomes importer) | R2 | C | US-ACC-10 |

### 4.2 Seller Profile & Catalog (SEL)
| Req ID | Requirement | Release | Priority | User Story |
|---|---|---|---|---|
| SEL-FR-01 | Importer creates company profile (data, logo, description, governorate, contact channels) | R1 | M | US-SEL-01 |
| SEL-FR-02 | Seller adds products to a catalog forming the "digital catalog" | R1 | M | US-SEL-02 |
| SEL-FR-03 | Seller uploads one or more images per product | R1 | M | US-SEL-03 |
| SEL-FR-04 | Seller uploads a short video per product | R2 | S | US-SEL-04 |
| SEL-FR-05 | Seller defines per product: type, material, color, width, price OR "price on contact", available quantity | R1 | M | US-SEL-05 |
| SEL-FR-06 | Seller defines MOQ and quantity-based price tiers | R1 | S | US-SEL-06 |
| SEL-FR-07 | Seller can edit, duplicate, hide, delete a product | R1 | M | US-SEL-07 |
| SEL-FR-08 | Seller can mark product "currently unavailable" without deleting | R1 | M | US-SEL-08 |
| SEL-FR-09 | Bulk product upload via template file | R1 | S | US-SEL-09 |
| SEL-FR-10 | System enforces product count limit per subscription plan, notifies at limit with upgrade prompt | R1 | M | US-SEL-10 |
| SEL-FR-11 | New/edited products enter a review queue before going public, per admin config | R1 | M | US-SEL-11 |
| SEL-FR-12 | Retailer creates a shop profile and lists own products for customers | R3 | M | US-SEL-12 |
| SEL-FR-13 | Seller organizes products into custom groups/categories | R2 | C | US-SEL-13 |

### 4.3 Search, Filtering & Discovery (SRC)
| Req ID | Requirement | Release | Priority | User Story |
|---|---|---|---|---|
| SRC-FR-01 | Text search in Arabic & English, tolerant of common fabric-name spelling variants | R1 | M | US-SRC-01 |
| SRC-FR-02 | Filter by: fabric type, color, material, width, price range, governorate, supplier, availability, MOQ | R1 | M | US-SRC-02 |
| SRC-FR-03 | Sort by: most relevant, price, newest, supplier rating | R1 | M | US-SRC-03 |
| SRC-FR-04 | Browse by fabric type (cotton, linen, crepe, denim, satin, etc.) | R1 | M | US-SRC-04 |
| SRC-FR-05 | Product detail screen: full specs, images, supplier identity, "Contact" and "Request Quotation" actions | R1 | M | US-SRC-05 |
| SRC-FR-06 | From product, navigate to supplier profile and browse full catalog | R1 | M | US-SRC-06 |
| SRC-FR-07 | Search suppliers directly, filter by governorate, specialty, verification status | R1 | M | US-SRC-07 |
| SRC-FR-08 | Save products and suppliers to a favorites list | R1 | M | US-SRC-08 |
| SRC-FR-09 | Select up to 4 products or suppliers and compare side-by-side | R1 | M | US-SRC-09 |
| SRC-FR-10 | Featured/higher-plan products & suppliers rank higher in search, clearly labeled | R1 | M | US-SRC-10 |
| SRC-FR-11 | System logs zero-result search terms, surfaces to admin as unmet-demand signal | R1 | S | US-SRC-11 |
| SRC-FR-12 | End Customer searches fabric and sees type, color, material, price, availability, nearest shop | R3 | M | US-SRC-12 |
| SRC-FR-13 | End Customer results ranked by distance from location | R3 | M | US-SRC-13 |

### 4.4 Inquiries, RFQs & Messaging (INQ)
| Req ID | Requirement | Release | Priority | User Story |
|---|---|---|---|---|
| INQ-FR-01 | Buyer sends inquiry to seller from product page or supplier profile | R1 | M | US-INQ-01 |
| INQ-FR-02 | Buyer sends structured RFQ specifying product, quantity, color, needed-by date | R1 | M | US-INQ-02 |
| INQ-FR-03 | Seller replies to RFQ with price, availability, offer validity period | R1 | M | US-INQ-03 |
| INQ-FR-04 | In-app messaging between buyer and seller, tied to inquiry/RFQ context | R1 | M | US-INQ-04 |
| INQ-FR-05 | System surfaces seller contact info (phone/WhatsApp) per plan/settings — R1 deals may close outside the app | R1 | M | US-INQ-05 |
| INQ-FR-06 | Every inquiry is logged as a Lead on the seller's account, shown in Lead management screen | R1 | M | US-INQ-06 |
| INQ-FR-07 | Seller marks lead status: New / In progress / Done / Not completed | R1 | S | US-INQ-07 |
| INQ-FR-08 | System enforces plan limits on inquiries sent (buyer) and received (seller) where applicable | R1 | M | US-INQ-08 |
| INQ-FR-09 | System detects spam/abusive messages, rate-limits, allows reporting by either party | R1 | M | US-INQ-09 |
| INQ-FR-10 | Retailer receives inquiries from End Customers | R3 | M | US-INQ-10 |

### 4.5 Orders (ORD)
| Req ID | Requirement | Release | Priority | User Story |
|---|---|---|---|---|
| ORD-FR-01 | Buyer creates an order on one or more products from a single seller, with quantity per item | R2 | M | US-ORD-01 |
| ORD-FR-02 | Order lifecycle: Created → Seller confirmed → Preparing → Shipped/Delivered → Completed, plus terminal Cancelled/Rejected | R2 | M | US-ORD-02 |
| ORD-FR-03 | Both parties see order history with full status timeline | R2 | M | US-ORD-03 |
| ORD-FR-04 | Seller confirms fully/partially or rejects with reason | R2 | M | US-ORD-04 |
| ORD-FR-05 | Order confirmation deducts available quantity for sellers who track inventory | R2 | S | US-ORD-05 |
| ORD-FR-06 | System generates a preliminary quotation document for confirmed order | R2 | S | US-ORD-06 |
| ORD-FR-07 | Online payment for orders | **R4** | M | US-PAY-01 |
| ORD-FR-08 | Shipment assignment and tracking | **R4** | M | US-SHP-01 |
| ORD-FR-09 | Wallet, installments, cash-on-delivery | **R4** | C | US-PAY-02 |

> **Deferral note (SRS §4.5):** ORD-FR-07 to 09 are intentionally deferred. R1 excludes payment gateway, wallet, installments, and shipping — the platform is testing one hypothesis first: will the trader actually use the platform to discover suppliers and buy from them? Only once Marketplace activity is confirmed does the roadmap open Tracking → Delivery → Payment → Order.

### 4.6 Subscriptions & Monetization (SUB)
| Req ID | Requirement | Release | Priority | User Story |
|---|---|---|---|---|
| SUB-FR-01 | Different subscription plans per account type: Importer, Wholesaler, Retailer | R1 | M | US-SUB-01 |
| SUB-FR-02 | Importer plans: Basic, Pro, Premium per Appendix A | R1 | M | US-SUB-02 |
| SUB-FR-03 | Entitlements enforced server-side: product limit, inquiry limit, search priority, featured placement, analytics depth, support level | R1 | M | US-SUB-03 |
| SUB-FR-04 | Seller sees current plan, usage vs. limits, renewal date, billing history | R1 | M | US-SUB-04 |
| SUB-FR-05 | Upgrade/downgrade/cancel; upgrade effective immediately, downgrade at end of paid term | R1 | M | US-SUB-05 |
| SUB-FR-06 | Monthly and annual billing with recurring collection | R1 | M | US-SUB-06 |
| SUB-FR-07 | Trial period and promotional grants for early-adopter importers, admin-issued | R1 | M | US-SUB-07 |
| SUB-FR-08 | On expiry, account moves to a restricted state: products hidden not deleted, renewal prompt | R1 | M | US-SUB-08 |
| SUB-FR-09 | Promotional products purchasable separately from the plan (featured supplier/product/homepage placement) | R2 | S | US-SUB-09 |
| SUB-FR-10 | End Customer account is free; no paid plan ever shown | R3 | M | US-SUB-10 |

### 4.7 Seller Analytics & Tools (ANL)
| Req ID | Requirement | Release | Priority | User Story |
|---|---|---|---|---|
| ANL-FR-01 | Seller sees basic analytics: profile views, product views, inquiry count over a chosen period | R1 | M | US-ANL-01 |
| ANL-FR-02 | Higher plans include advanced analytics: search terms that led to them, top-performing products, buyer distribution by governorate, view-to-inquiry conversion | R1 | M | US-ANL-02 |
| ANL-FR-03 | Lead management screen shows all inquiries with status and last activity | R1 | M | US-ANL-03 |
| ANL-FR-04 | Seller publishes time-bound offers/discounts on products | R1 | S | US-ANL-04 |
| ANL-FR-05 | Aggregated, anonymized market insights (demand trends by fabric type/region) for Premium accounts | R2 | C | US-ANL-05 |

### 4.8 Buyer Tools (BUY)
| Req ID | Requirement | Release | Priority | User Story |
|---|---|---|---|---|
| BUY-FR-01 | Buyer has a purchase/inquiry history screen | R1 | M | US-BUY-01 |
| BUY-FR-02 | Buyer saves suppliers to a private supplier list | R1 | M | US-BUY-02 |
| BUY-FR-03 | Buyer sets market alerts (fabric type/price threshold/supplier); notified on match | R1 | S | US-BUY-03 |
| BUY-FR-04 | Buyer sees a "recently arrived" section matching their interests | R1 | M | US-BUY-04 |
| BUY-FR-05 | Buyer compares prices for the same fabric spec across multiple suppliers | R1 | M | US-BUY-05 |

### 4.9 Notifications (NOT)
| Req ID | Requirement | Release | Priority | User Story |
|---|---|---|---|---|
| NOT-FR-01 | Push + in-app notifications for: new inquiry, RFQ reply, new message, order status change, subscription expiring, review result | R1 | M | US-NOT-01 |
| NOT-FR-02 | User configures notification preferences per category | R1 | M | US-NOT-02 |
| NOT-FR-03 | SMS or email for account/financial events that must not be missed | R1 | M | US-NOT-03 |
| NOT-FR-04 | Marketing notifications require explicit opt-in, separate from operational notifications | R1 | M | US-NOT-04 |

### 4.10 Admin & Control Panel (ADM)
| Req ID | Requirement | Release | Priority | User Story |
|---|---|---|---|---|
| ADM-FR-01 | Admin reviews verification requests, approves/rejects with reason logged | R1 | M | US-ADM-01 |
| ADM-FR-02 | Admin reviews products, can reject/hide/request edits | R1 | M | US-ADM-02 |
| ADM-FR-03 | Admin manages fabric taxonomy: types, materials, colors, units | R1 | M | US-ADM-03 |
| ADM-FR-04 | Admin manages subscription plans, pricing, entitlements, trial periods, manual grants | R1 | M | US-ADM-04 |
| ADM-FR-05 | Admin curates featured suppliers, featured products, homepage banners | R1 | M | US-ADM-05 |
| ADM-FR-06 | Marketplace-liquidity dashboard: active sellers, active products, active buyers, weekly inquiries, zero-result search terms | R1 | M | US-ADM-06 |
| ADM-FR-07 | Admin suspends or bans an account, action logged to audit log | R1 | M | US-ADM-07 |
| ADM-FR-08 | Admin handles reports/disputes via a ticket queue | R1 | S | US-ADM-08 |
| ADM-FR-09 | All admin actions logged to an immutable audit log (actor, timestamp, affected entity) | R1 | M | US-ADM-09 |
| ADM-FR-10 | Admin can onboard a supplier on their behalf ("assisted onboarding") during supply-building phase | R1 | M | US-ADM-10 |

### Interface & Data Requirements (also traced)
| Req ID | Requirement | Release | Priority | Story/Note |
|---|---|---|---|---|
| UI-FR-01 to 08 | Account-type chooser, RTL-first Arabic UI w/ EN toggle, per-role home/navigation, buyer home layout, product card, bottom nav, locale-aware number/date/currency formatting, confirm on destructive actions | R1 | M | US-UI-01..08 |
| HW-FR-01 | Camera/gallery access for product media | R1 | M | US-SEL-03 (dependency) |
| HW-FR-02 | Location permission requested only when a location feature is used (nearest shop) | R3 | M | US-LOC-01 |
| SI-FR-01 | Versioned API consumed by all clients, no business logic duplicated client-side | R1 | M | Architectural constraint, §7 |
| SI-FR-02 | OTP/SMS provider integration | R1 | M | US-ACC-01 dependency |
| SI-FR-03 | Payment provider integration for recurring subscription billing | R1 | M | US-SUB-06 dependency |
| SI-FR-04 | Push notification service integration | R1 | M | US-NOT-01 dependency |
| SI-FR-05 | Maps provider integration for shop location/distance | R3 | M | US-LOC-01 dependency |
| SI-FR-06 | XLSX/CSV catalog import/export | R2 | S | US-SEL-09 extension |
| CI-FR-01 | All client-server traffic over TLS 1.2+/HTTPS | R1 | M | NFR-SEC-01 |
| CI-FR-02 | Images/video served via CDN with adaptive resizing | R1 | M | NFR-PRF-03 |
| CI-FR-03 | Deep links to a product/supplier/shop for external sharing | R1 | S | US-SRC-14 |
| DAT-FR-01 | Personal data collected only to the extent it serves a declared purpose (Law 151/2020) | R1 | M | Security architecture §12 |
| DAT-FR-02 | Verification documents stored encrypted, access restricted to authorized admin roles, deleted per retention period | R1 | M | US-ACC-04 (security) |
| DAT-FR-03 | On account deletion, personal identifiers removed/anonymized, financial records retained anonymized as legally required | R1 | M | US-ACC-09 |
| DAT-FR-04 | User can request a copy of their personal data | R2 | S | US-ACC-11 |
| DAT-FR-05 | Seller-facing analytics are aggregated; never reveal identity of a buyer who hasn't initiated contact | R1 | M | US-ANL-01/02 (privacy rule) |

**Every FR above has a story ID.** No requirement is unrepresented in a release; where a requirement's own release is R2–R4, it is intentionally not built in R1 — this is the SRS's own release assignment, not an implementation choice.

---

## 3. Non-Functional Requirement Implementation Matrix

| NFR ID | Requirement (SRS §6) | Architecture Decision | Implementation | Test |
|---|---|---|---|---|
| PRF-NFR-01 | Search returns in <2s at p95 on 4G, catalog up to 100k products | MySQL full-text + composite indexes on (fabric_type, color, governorate, price); paginated, no N+1 | Indexed `products` table, query result caching for common filter combos | Load test w/ synthetic 100k-row catalog, p95 latency assertion |
| PRF-NFR-02 | Any home screen shows usable content in <3s on mid-tier device | Server-side pagination, lean initial payload (list view fields only), image lazy-load | API returns minimal `ProductCardResource`, full detail on demand | Mobile perf test on throttled network |
| PRF-NFR-03 | Product-card image ≤150KB, device-appropriate resolution | S3 + CDN with on-the-fly/pre-generated responsive variants | Laravel Filesystem (S3 driver) + CDN transform or pre-resize on upload via queued Job | Automated image-size assertion in CI |
| PRF-NFR-04 | 500 concurrent users at launch without degradation | Shared-hosting-compatible PHP-FPM tuning, opcode cache, DB connection pooling, query optimization | Standard Laravel + MySQL, no exotic infra | Load test simulating 500 concurrent sessions |
| SCL-NFR-01 | Horizontal scale to 10x launch load without redesign | Stateless Laravel app instances behind a load balancer when moving off shared hosting; sessions/cache externalized (DB or Redis) so any instance can serve any request | Config-driven session/cache driver swap, no server-affinity code | Scale-out drill in staging |
| SCL-NFR-02 | Catalog search layer scalable independently of the transactional DB | MySQL read replica or dedicated search-optimized indexes for R1; documented upgrade path to a dedicated search engine only if scale requires it | MySQL full-text now; **Future scalability option — not MVP infrastructure**: Meilisearch/OpenSearch behind the same Search module interface | Query-plan review under growth projections |
| SCL-NFR-03 | Unlimited media storage served via CDN | S3 (unlimited object storage) + CDN | `product_media` metadata in MySQL, binary in S3 | N/A (verify no local disk media storage) |
| AVL-NFR-01 | 99.5% monthly availability | Standard managed MySQL + PHP hosting with health checks; no single point of failure beyond what shared hosting allows | Uptime monitoring, alerting | Monthly uptime report |
| AVL-NFR-02 | Automated daily backup, retained 30 days, tested restore | `mysqldump`/managed-hosting backup + S3 lifecycle for media | Cron-triggered backup job; quarterly restore drill | Restore drill checklist |
| AVL-NFR-03 | Graceful degradation — a failed image upload must not lose entered product data | Product record saved independently of media upload; media upload failures surfaced as a retryable sub-step | Two-phase save: create Product (draft) → attach media async | Fault-injection test: kill upload mid-flow |
| AVL-NFR-04 | App caches last-viewed catalog data for read access on brief connectivity loss | Client-side cache (mobile team), API supports conditional GET / ETags | Mobile responsibility; backend provides cache-friendly responses | Client offline-mode test (mobile team scope) |
| SEC-NFR-01 | TLS 1.2+ everywhere, no credentials/tokens in URLs | Enforce HTTPS at web-server level; tokens in headers/body only | `ForceHttps` middleware, Sanctum bearer tokens | Automated header/URL scan |
| SEC-NFR-02 | Modern password hashing; OTP expires in 5 min with rate-limited attempts | `bcrypt`/`argon2` via Laravel Hash; OTP table with `expires_at`, attempt counter | Laravel `Hash`, custom OTP throttle | Unit test on OTP expiry & lockout |
| SEC-NFR-03 | Server-side authorization by role and resource ownership | Laravel Policies on every protected model | Policy classes per module | Authorization test suite (see §14) |
| SEC-NFR-04 | Client-supplied subscription entitlements are never trusted | All entitlement checks re-derived server-side from `subscriptions`/`subscription_entitlements` tables | Entitlement gate service, called from Policies/Form Requests | Test: forged client claim rejected |
| SEC-NFR-05 | Uploaded files validated by type and size, scanned before publish | Form Request MIME/size rules + queued AV/validation job before making media public | `image`/`mimes` validation rules, S3 private-then-promote pattern | Malicious-file upload test |
| SEC-NFR-06 | Protect against OWASP API & Mobile Top 10; security review before major releases | Standard Laravel hardening (CSRF/Sanctum, rate limiting, input validation, mass-assignment guards) | `Fortify`/`Sanctum`, `ThrottleRequests`, `$fillable` guards | Pre-release security checklist/pen-test |
| SEC-NFR-07 | Card data never touches THOB servers; PCI-DSS-compliant provider only | Payment provider hosted checkout/tokenization (R4 scope) | Payments module never stores PAN | Code review gate: no card-field persistence |
| USE-NFR-01 | First-time importer publishes first product within 10 minutes unaided | Guided onboarding flow, minimal required fields, bulk-template available | Onboarding wizard, inline field help | Usability test with time-to-first-publish metric |
| USE-NFR-02 | Full RTL functionality including mixed Arabic/Latin text | RTL-first layout, bidi-safe text rendering (mobile team); API returns raw text, no baked-in directionality | N/A backend; contract: return UTF-8 text as-is | RTL visual regression (mobile team) |
| USE-NFR-03 | Terminology matches actual Egyptian fabric-market usage, not literal translation | Taxonomy content curated by ops/admin (ADM-FR-03), not hardcoded | `fabric_taxonomy` table editable via Admin | Content review by domain expert |
| USE-NFR-04 | Error messages explain what happened and what to do, in active language | Localized validation messages, no raw exception text exposed | Laravel localized `messages.php` per error code | i18n message coverage test |
| USE-NFR-05 | Core buyer journeys reachable within 3 taps from home | Bottom nav + home shortcuts to Search/Favorites/Messages (mobile team) | API supports the shortcuts (featured, recent, search) | UX tap-count audit |
| MNT-NFR-01 | Versioned API, published version supported ≥6 months after deprecation notice | `/api/v1/` namespace, deprecation policy documented | Route versioning, changelog | Contract test suite pinned to v1 |
| MNT-NFR-02 | Business config (plans, entitlements, taxonomy, featured slots) editable from Admin without a code deploy | All of the above stored in DB, managed via Admin UI/API | `subscription_plans`, `fabric_taxonomy`, `featured_placements` tables | Admin CRUD test |
| MNT-NFR-03 | Automated tests cover auth, entitlement enforcement, search, inquiry flow | Feature test suite per module | PHPUnit/Pest feature tests | CI gate — see §14 Testing Strategy |
| MNT-NFR-04 | Structured logging and error monitoring from launch | Laravel logging channels + APM/error tracker (e.g., Sentry-class tool) | JSON log format, correlation IDs | Log-output verification |
| ACC-NFR-01 | Text respects OS font-scaling up to 200% without breaking layout | Mobile team responsibility; backend avoids fixed-length truncation in UI-bound strings | N/A backend | Mobile accessibility audit |
| ACC-NFR-02 | WCAG 2.1 AA color contrast | Mobile/design team | N/A backend | Design QA |
| ACC-NFR-03 | Tappable elements ≥44×44pt | Mobile team | N/A backend | Design QA |

NFRs are **not** converted into user stories per instruction §5 — they are architecture/test commitments cross-cutting the whole backlog.

---

## 4. User Story Backlog

Full-detail stories (complete acceptance criteria, dependencies, business rules, API/DB/notification/security notes) are given for the **R1 core MVP loop** — the chain the SRS says the MVP must prove: Registration → Verification → Profile → Publishing → Discovery → Inquiry/RFQ → Quotation → Chat. All remaining backlog items (R1 supporting stories, all of R2/R3/R4) are listed with Given/When/Then acceptance criteria in compact form in §4.9–§4.12; every one is still traceable to an ID in §2.

### 4.1 Registration & Verification (R1)

**US-ACC-01 — Register with phone + OTP**
Requirement Source: ACC-FR-01, SI-FR-02 · Release: R1 · Priority: Must · Actor: Importer/Wholesaler/Retailer/Customer

As a: new user
I want: to register with my phone number and verify it via OTP
So that: I have a trusted, spam-resistant account

Acceptance Criteria:
- Given a valid Egyptian phone number, When I submit registration, Then an OTP is sent via the SMS provider and expires in 5 minutes (SEC-NFR-02).
- Given a correct OTP within the window, When I submit it, Then my account is created in `pending_type_selection` state.
- Given 3 incorrect OTP attempts, When I try again, Then further attempts are rate-limited (SEC-NFR-02).
- Given the OTP provider fails, When I request an OTP, Then I see a localized, actionable error (USE-NFR-04) and can retry.
- Given a phone number already registered, When I try to register again, Then I'm routed to login instead.
- Email is optional and, if provided, is not itself a verification gate.

Dependencies: OTP/SMS provider (SI-FR-02).
Business Rules: BR-ACC-01 (one account per verified phone number).
API Impact: `POST /api/v1/auth/otp/request`, `POST /api/v1/auth/otp/verify`, `POST /api/v1/auth/register`.
Database Impact: `users`, `otp_requests` (phone, code_hash, expires_at, attempts).
Security Considerations: OTP never logged in plaintext (SRS §27 instruction); code stored hashed.
Testing Requirements: expiry, rate-limit, provider-failure, duplicate-phone paths.

**US-ACC-02 — Choose account type**
Requirement Source: ACC-FR-02 · Release: R1 · Priority: Must · Actor: All

As a: newly-verified user
I want: to choose exactly one account type (Importer/Wholesaler/Retailer/Customer)
So that: the app shows me the correct role-specific experience

Acceptance Criteria:
- Given a verified account with no type set, When I open the app, Then I see the account-type chooser (UI-FR-01) with bilingual icon+description per type.
- Given I select a business type (Importer/Wholesaler/Retailer), When I confirm, Then I'm routed into the business-profile flow (US-ACC-03).
- Given I select Customer, When I confirm, Then no business profile or documents are requested (ACC-FR-06, R3).
- Account type, once set, cannot be self-changed without admin approval (ACC-FR-10).

Dependencies: US-ACC-01.
Business Rules: BR-ACC-02 (one type per account, admin-mediated change only).
Database Impact: `users.account_type`.

**US-ACC-03 — Business account profile capture**
Requirement Source: ACC-FR-03 · Release: R1 · Priority: Must · Actor: Importer/Wholesaler/Retailer

As a: business-account user
I want: to enter company name, activity, governorate, address, contact person, and contact channels
So that: buyers/admin can identify and reach my business

Acceptance Criteria:
- Given required fields are incomplete, When I try to submit, Then I see field-level validation errors (see §11 Validation Catalogue).
- Given valid data, When I submit, Then a `BusinessAccount` record is created linked to my `User`, in `unverified` status.
- Given I revisit later, When I open my profile, Then previously entered data is editable.

Database Impact: `business_accounts` (user_id FK, company_name, activity, governorate_id FK, address, contact_person, contact_channels JSON‑for‑the‑channel‑list‑only-not-relational-data).
Business Rules: BR-ACC-03 (governorate must reference `governorates` taxonomy, not free text) — **Implementation Assumption**, SRS does not specify whether governorate is a controlled list; treated as a taxonomy reference for search-filter consistency with SRC-FR-02.

**US-ACC-04 — Upload verification documents**
Requirement Source: ACC-FR-04, DAT-FR-02 · Release: R1 · Priority: Must · Actor: Importer/Wholesaler/Retailer

As a: business-account user
I want: to upload my commercial register and tax card
So that: admin can verify my business and grant me the verified badge

Acceptance Criteria:
- Given I upload a file, When it's submitted, Then it's validated for MIME type and size (SEC-NFR-05) before acceptance.
- Given a successful upload, When stored, Then the file is written to a **private** S3 location, never a predictable public URL (§40 File Security).
- Given the account has pending documents, When admin reviews, Then the account enters `verification_pending`, then `verified`/`rejected` (US-ADM-01).
- Given documents are rejected, When I check status, Then I see the admin-provided rejection reason (ADM-FR-01) and can re-upload.
- Exact mandatory document list is an **Open Decision** (SRS Appendix D, item #5) — the SRS names "commercial register, tax card" as examples but does not close whether unverified suppliers may publish at all. Implementation stores document *type* as a taxonomy so this can change without a code deploy (MNT-NFR-02).

Database Impact: `verification_requests`, `verification_documents` (encrypted at rest, DAT-FR-02).
Security Considerations: only `admin` role policies may generate a signed, time-limited download URL for these documents (§40).

**US-ACC-05 — Verified badge display**
Requirement Source: ACC-FR-05 · Release: R1 · Priority: Must · Actor: System

As a: buyer browsing the platform
I want: to see a "Verified" badge on verified businesses
So that: I can trust who I'm dealing with

Acceptance Criteria:
- Given `business_accounts.verification_status = verified`, When the profile/product card is rendered anywhere in the app, Then the badge is included in the API resource.
- Given verification is later revoked (ADM-FR-07 suspension), When rendered, Then the badge disappears immediately (no caching beyond TTL).

API Impact: `verified: boolean` field on `SupplierResource` and `ProductResource`.

### 4.2 Seller Profile & Catalog (R1)

**US-SEL-01 — Create company profile**
Requirement Source: SEL-FR-01 · Release: R1 · Priority: Must · Actor: Importer

As an: importer
I want: to create a company profile with logo, description, governorate, and contact channels
So that: buyers discovering me see a credible business identity

Acceptance Criteria:
- Given required company fields, When saved, Then the profile is visible to buyers only once the account is at least `pending_review`/created (visibility itself does not require verification unless Appendix D #5 is resolved to require it — flagged as **Open Decision**).
- Given no logo uploaded, When profile renders, Then a placeholder is shown (no crash/blank state).

Database Impact: `business_profiles` (or fields on `business_accounts` — see §7 schema notes on this modeling choice).

**US-SEL-02 — Add product to catalog**
Requirement Source: SEL-FR-02 · Release: R1 · Priority: Must · Actor: Importer

As an: importer
I want: to add a product with its fabric attributes
So that: it becomes part of my digital catalog and is discoverable

Acceptance Criteria:
- Given all mandatory product fields (§5.2 SRS — type, material, color, width, price-or-contact, quantity, governorate, ≥1 image), When I submit, Then the product is created in `draft` or `pending_review` status per SEL-FR-11.
- Given a mandatory field is missing, When I submit, Then I get field-specific errors (Validation Catalogue §11).
- Given the seller's plan product limit is reached (SEL-FR-10), When I try to add another, Then I'm blocked with an upgrade prompt — **not** silently allowed then hidden.
- Given the product is saved, When I view my catalog, Then it appears with its current lifecycle status visible only to me until published.

Dependencies: US-ACC-05 verification status (affects visibility per Open Decision #5), US-SUB-03 (limit enforcement).
Business Rules: BR-SEL-01 (product-count limit enforced server-side, never client-trusted — SEC-NFR-04); BR-SEL-02 (review gate per SEL-FR-11, admin-configurable).
Database Impact: `products` (see §7.3 schema).
Notifications: `product_submitted` to admin queue (see §10).

**US-SEL-03 / 04 — Product images / video**
Requirement Source: SEL-FR-03 (R1, M), SEL-FR-04 (R2, S) · Actor: Importer

As a: seller
I want: to attach one or more images (R1) and a short video (R2) to a product
So that: buyers can visually evaluate the fabric (SRS §2.5: "fabric is bought visually")

Acceptance Criteria:
- Given an image upload, When validated (MIME/size, SEC-NFR-05), Then it's stored in S3 and a `product_media` row is created with `type=image`.
- Given at least one image is required (§5.2), When a seller tries to publish with zero images, Then publish is blocked.
- Given a video (R2), When uploaded, Then it is validated for type/size and does not block image-only publishing paths built in R1.
- Given an upload fails mid-way, When I retry, Then the product's other data is untouched (AVL-NFR-03).

API Impact: `POST /api/v1/products/{id}/media`.
Database Impact: `product_media` (product_id FK, disk, key/path, mime_type, size, original_name).

**US-SEL-05 — Define product attributes**
Requirement Source: SEL-FR-05 · Release: R1 · Priority: Must · Actor: Importer

As a: seller
I want: to specify type, material, color, width, price (or "price on contact"), and available quantity
So that: buyers can search and filter accurately

Acceptance Criteria:
- Given "price on contact" is chosen, When the product is shown, Then no numeric price is displayed, and the "Contact" CTA is emphasized (aligns with SRS Appendix D #3 — this remains an open pricing-disclosure decision at the *policy* level, but the schema must support both states now, per SRS §5.2 "نعم (أحدهما)").
- Given type/material/color reference the taxonomy, When invalid values are submitted, Then validation rejects them (must reference `fabric_taxonomy`).
- Given width is submitted, When saved, Then it is stored as number + unit (cm, per §5.2).

Database Impact: `products.price` nullable, `products.price_on_contact` boolean, `products.fabric_type_id`/`material_id`/`color_id` FKs to taxonomy, `products.width_cm`, `products.quantity_available`.
Business Rules: BR-SEL-03 (exactly one of price/"price on contact" must be set).

**US-SEL-06 — MOQ and quantity price tiers**
Requirement Source: SEL-FR-06 · Release: R1 · Priority: Should · Actor: Importer

Given a seller sets an MOQ, When a buyer views the product, Then MOQ is shown and factored into RFQ defaults (US-INQ-02). Given quantity tiers are set, When displayed, Then they are shown as a simple price break table — **Implementation Assumption**: SRS does not define tier structure beyond "quantity-based price tiers"; modeled as `product_price_tiers(product_id, min_qty, unit_price)`.

**US-SEL-07 / 08 — Edit, duplicate, hide, delete, mark unavailable**
Requirement Source: SEL-FR-07, SEL-FR-08 · Release: R1 · Priority: Must · Actor: Importer (owner only)

Acceptance Criteria:
- Given I own the product, When I edit it, Then changes may re-enter the review queue per SEL-FR-11 (admin-configurable whether edits require re-review).
- Given I don't own the product, When I attempt any of edit/duplicate/hide/delete, Then I get 403 (ownership check, §21 Authorization).
- Given I delete a product, When confirmed (UI-FR-08 destructive-action confirmation), Then it's soft-deleted (business data retained per SRS data-minimization intent, not hard-deleted, to preserve inquiry/order history integrity).
- Given I mark "unavailable", When buyers browse, Then it's excluded from default search results but still visible on my catalog and via direct link, clearly labeled.

Authorization: Policy `ProductPolicy@update/delete` checks `product.business_account_id === $user->businessAccount->id`.

**US-SEL-09 — Bulk upload**
Requirement Source: SEL-FR-09 · Release: R1 · Priority: Should · Actor: Importer

As an: importer with many SKUs
I want: to upload products via a template file
So that: cataloging isn't blocked by slow manual entry (SRS §2.5 constraint #2 — "bulk upload is mandatory, not optional")

Acceptance Criteria:
- Given a downloaded template (XLSX/CSV), When I fill and upload it, Then each row is validated independently; valid rows are created, invalid rows are reported with row-level errors — the whole file is not rejected for one bad row.
- Given 500 rows, When processed, Then processing happens as a queued Job with a progress/result notification (avoids request timeout on shared hosting, §12 constraint).

API Impact: `POST /api/v1/catalog/bulk-import`, `GET /api/v1/catalog/bulk-import/{jobId}`.
Testing Requirements: partial-failure row reporting, large-file queued processing.

**US-SEL-10 — Plan product limit enforcement**
Requirement Source: SEL-FR-10 · Release: R1 · Priority: Must · Actor: System/Importer

Given a seller's active `Subscription` has an entitlement `product_limit`, When they attempt to create/activate a product beyond that count, Then the request is rejected server-side (SEC-NFR-04) with an upgrade-plan payload — this check happens in the Catalog module by querying the Subscriptions module's entitlement service, **not** by trusting a client flag.

**US-SEL-11 — Review queue**
Requirement Source: SEL-FR-11 · Release: R1 · Priority: Must · Actor: Admin/System

Given admin configuration requires review, When a product is created or edited, Then it enters `pending_review` and is excluded from public search until `admin` approves (US-ADM-02) — see Product lifecycle in §7.3.

### 4.3 Search & Discovery (R1)

**US-SRC-01 / 02 / 03 / 04 — Search, filter, sort, browse**
Requirement Source: SRC-FR-01..04 · Release: R1 · Priority: Must · Actor: Wholesaler (primary), all buyers

As a: wholesaler
I want: to search fabrics by free text (AR/EN, tolerant of spelling variants) and filter by type/color/material/width/price/governorate/supplier/availability/MOQ, sorted by relevance/price/newest/rating
So that: I can find the right fabric quickly instead of relying on word-of-mouth (SRS §2.1 "friction at every stage")

Acceptance Criteria:
- Given a search term with a common spelling variant, When searched, Then results include reasonable fuzzy matches (MySQL FULLTEXT + normalization table, not literal LIKE) — **Implementation Assumption**: exact fuzziness algorithm is not specified in the SRS; approach documented in §9 Search Architecture.
- Given multiple filters combined, When applied, Then results are the intersection, paginated (SRC-FR-02).
- Given a sort option, When selected, Then results re-order without losing active filters.
- Given zero results, When shown, Then the term is logged for SRC-FR-11 and a friendly empty state is shown (USE-NFR-04).
- Given only `published` and non-hidden products from `verified`-or-not sellers per current visibility policy, When searching, Then unpublished/hidden/deleted products never appear.

API Impact: `GET /api/v1/products?search=&filters[...]=&sort=&page=`.
Database Impact: FULLTEXT index on `products.name_ar/name_en` (or normalized search column), composite indexes on filter columns.

**US-SRC-05 / 06 — Product detail & supplier navigation**
Requirement Source: SRC-FR-05, SRC-FR-06 · Release: R1 · Priority: Must

Given a product is selected, When the detail screen loads, Then it shows full spec, images, supplier identity+badge, and "Contact"/"Request Quotation" CTAs (US-INQ-01/02). Given "view supplier" is tapped, When navigated, Then the full supplier catalog is shown, filtered to that supplier's published products.

**US-SRC-07 — Supplier search**
Requirement Source: SRC-FR-07 · Release: R1 · Priority: Must

Given a buyer searches suppliers directly, When filtered by governorate/specialty/verification, Then only business accounts (not customers) matching are returned.

**US-SRC-08 — Favorites**
Requirement Source: SRC-FR-08 (also BUY-FR-02, §34) · Release: R1 · Priority: Must

Given I favorite a product or supplier, When I favorite the same item twice, Then no duplicate record is created (unique constraint on `favorites(user_id, favoritable_type, favoritable_id)`). Given I don't own the favorite, When I try to remove someone else's, Then 403.

**US-SRC-09 — Compare up to 4**
Requirement Source: SRC-FR-09 (also §35 Comparison) · Release: R1 · Priority: Must

Given I select products/suppliers to compare, When I try to add a 5th, Then I'm blocked with a clear limit message — **not** a generic comparison engine, just a bounded side-by-side spec/price view per §35 instruction.

**US-SRC-10 — Featured ranking**
Requirement Source: SRC-FR-10 · Release: R1 · Priority: Must · Actor: System

Given a seller's plan includes featured placement (Appendix A), When search results are ranked, Then their items get a documented ranking boost and are visually labeled "Featured" — labeling is mandatory per SRS wording ("يوسم هذا الظهور بوضوح"), not a silent boost.

**US-SRC-11 — Zero-result logging**
Requirement Source: SRC-FR-11 · Release: R1 · Priority: Should

Given a search returns 0 results, When logged, Then it appears in the Admin liquidity dashboard (US-ADM-06) as unmet demand — no PII beyond the search string is required.

### 4.4 Inquiries, RFQ, Quotation & Chat (R1)

**US-INQ-01 — Send inquiry**
Requirement Source: INQ-FR-01 · Release: R1 · Priority: Must · Actor: Wholesaler

As a: buyer
I want: to send an inquiry to a seller from a product page or supplier profile
So that: I can start a conversation about a fabric I'm interested in

Acceptance Criteria:
- Given I tap "Contact" on a product/supplier, When I send a message, Then an `Inquiry` is created linking buyer, seller, and (optionally) the product, in `new` lead status (INQ-FR-06).
- Given the seller's inbound-inquiry plan limit is reached (INQ-FR-08), When I try to send, Then I get a clear message that the seller is currently unreachable via this channel — the buyer is never silently dropped.
- Given rate limits/spam heuristics flag the message (INQ-FR-09), When submitted, Then it's held/flagged rather than silently delivered, and both parties retain the ability to report abuse.

Dependencies: US-ACC-05 (identity), US-SEL-02 (product context, optional).
Business Rules: BR-INQ-01 (every inquiry = one Lead record); BR-INQ-02 (plan limits apply to both sender and receiver where defined).
Database Impact: `inquiries` (buyer_id, seller_business_id, product_id nullable, status, lead_status).
Notifications: `new_inquiry` → seller (NOT-FR-01).

**US-INQ-02 — Send structured RFQ**
Requirement Source: INQ-FR-02 · Release: R1 · Priority: Must · Actor: Wholesaler

As a: buyer
I want: to send a structured RFQ specifying product, quantity, color, and needed-by date
So that: the seller can quote accurately instead of a free-text back-and-forth

Acceptance Criteria:
- Given I open "Request Quotation" on a product, When I fill quantity/color/needed-by-date and submit, Then an `RFQ` record is created linked to an `Inquiry` thread.
- Given required RFQ fields are missing, When submitted, Then validation blocks it (§11).
- Given MOQ is defined on the product (SEL-FR-06), When quantity is below MOQ, Then the buyer is warned but not necessarily blocked — **Implementation Assumption**: SRS doesn't state whether sub-MOQ RFQs are disallowed or merely flagged; treated as a soft warning to avoid over-blocking early marketplace activity.

Database Impact: `rfqs` (inquiry_id FK, product_id, quantity, color_id, needed_by_date).
Notifications: `new_rfq` → seller.

**US-INQ-03 — Seller replies with quotation**
Requirement Source: INQ-FR-03 · Release: R1 · Priority: Must · Actor: Importer

As a: seller
I want: to reply to an RFQ with price, availability, and offer validity
So that: the buyer has a concrete, time-bound offer to act on

Acceptance Criteria:
- Given an open RFQ addressed to me, When I submit a quotation (price, availability, valid_until), Then a `Quotation` record is created and linked; the buyer is notified (`quotation_received`).
- Given the offer's `valid_until` passes, When viewed later, Then it's shown as expired, not actionable as current pricing.
- Given I'm not the RFQ's target seller, When I try to quote, Then 403.

Database Impact: `quotations` (rfq_id FK, price, availability_note, valid_until).

**US-INQ-04 — In-app chat tied to inquiry/RFQ**
Requirement Source: INQ-FR-04 · Release: R1 · Priority: Must · Actor: Buyer/Seller

As a: buyer or seller
I want: to message within the context of my inquiry/RFQ
So that: negotiation history stays attached to the right business context

Acceptance Criteria (full detail continues in §4.5 Chat/Pusher Architecture stories US-CHT-01..09):
- Given an inquiry exists, When either party opens it, Then a `Conversation` scoped to that inquiry is available.
- Given I am neither the buyer nor the seller on that inquiry, When I try to access the conversation, Then 403 (conversation authorization, §17/§18).

**US-INQ-05 — Contact info visibility**
Requirement Source: INQ-FR-05 · Release: R1 · Priority: Must

Given the seller's plan/settings allow it, When a buyer views the seller profile, Then phone/WhatsApp is shown — this is a deliberate R1 acknowledgment that deals may complete outside the app (SRS §2.5). Given the plan/settings don't allow it, When viewed, Then contact info is withheld and in-app messaging is the only channel.

**US-INQ-06 / 07 — Lead logging & status**
Requirement Source: INQ-FR-06, INQ-FR-07 · Release: R1 · Priority: Must/Should

Given any inquiry is created, When logged, Then it appears in the seller's Lead Management screen (§36) with status New by default; the seller can move it through New → In progress → Done → Not completed. No other statuses are invented (SRS gives exactly these four).

**US-INQ-08 — Plan limits on inquiries**
Requirement Source: INQ-FR-08 · Release: R1 · Priority: Must

Enforced server-side identically to US-SEL-10's pattern — entitlement check before creating the `Inquiry`/`RFQ` record, on both sender-side (buyer plan, if the SRS's buyer plans define such a limit — **Open Decision #2**, numeric limits not specified) and receiver-side (seller plan).

**US-INQ-09 — Spam/abuse detection & reporting**
Requirement Source: INQ-FR-09 · Release: R1 · Priority: Must

Given a user sends messages above a rate threshold, When detected, Then further sends are throttled (429) with a clear cooldown message. Given either party reports a conversation/message, When submitted, Then it creates an admin ticket (US-ADM-08) referencing the message content and both parties — exact abuse-detection heuristics are an **Implementation Assumption** (rate + basic content flags); the SRS specifies the requirement but not the algorithm.

### 4.5 Chat (Pusher) — Full Story Set (R1)

Architecture recap (detailed further in §8): Pusher is **not** the source of truth. MySQL stores every message. Flow: Client → Laravel Chat module → authenticate sender → authorize conversation access → validate message → persist to MySQL → broadcast via Pusher → recipient. If Pusher is unavailable, the message is still stored and retrievable via the API.

**US-CHT-01 — Open/create a conversation**
Given a buyer and seller have an Inquiry/RFQ, When either opens "Chat", Then a `Conversation` is created if none exists for that inquiry, or the existing one is returned (idempotent).

**US-CHT-02 — Conversation authorization**
Given a user requests a conversation, When authorizing, Then access is granted only if `user.id` is the buyer or the seller-side business owner/sub-user (ACC-FR-08) on the linked inquiry. Given Pusher's private-channel auth endpoint is called, When it validates the user against the conversation, Then it returns 403 for non-participants (this is the actual enforcement point — Pusher channel auth must call back into Laravel's authorization, never trust the client-declared channel name).

**US-CHT-03 — Send message**
Given I'm authorized on the conversation, When I send a message, Then it is validated (non-empty, length limit), persisted to `messages`, and a Pusher event is broadcast to the conversation's private channel.

**US-CHT-04 — Receive realtime message**
Given the recipient is subscribed to the Pusher private channel, When a message is broadcast, Then it appears without polling. Given the recipient is offline/app backgrounded, When they return, Then §4.6 message persistence/pagination fills the gap (Pusher is not relied on for delivery guarantees).

**US-CHT-05 — Message persistence & Pusher-outage fallback**
Given Pusher is temporarily unavailable, When a message is sent, Then it is still saved to MySQL and returned via `GET /api/v1/conversations/{id}/messages` — the broadcast step failing must never fail the send.

**US-CHT-06 — Load history & pagination**
Given a conversation has many messages, When history is requested, Then it's paginated (cursor or page-based) oldest/newest as the client requests, never loading the full thread in one response.

**US-CHT-07 — Read/unread state & unread count**
Given messages arrive, When the recipient hasn't opened the conversation, Then they're `unread`; opening the conversation marks them `read` and the badge count decreases. Given the home/messages screen loads, When rendered, Then it shows a total unread count across conversations.

**US-CHT-08 — Rate limiting**
Given a user sends messages rapidly, When above the threshold, Then further messages in that window are rejected with 429 (shared with US-INQ-09's abuse controls).

**US-CHT-09 — Reporting abusive content**
Given a message is abusive, When reported, Then it creates an admin ticket referencing the message ID, conversation, and both parties (US-ADM-08).

Pusher implementation notes: private channels named `conversation.{id}`; Laravel broadcasting auth route (`/broadcasting/auth`) enforces the same Policy as the REST endpoints — **one authorization source**, not duplicated logic between REST and broadcast auth.

### 4.6 Subscriptions (R1)

**US-SUB-01 / 02 — Plan catalog per account type**
Requirement Source: SUB-FR-01, SUB-FR-02 · Release: R1 · Priority: Must

Given the account type is Importer, When plans are listed, Then only Basic/Pro/Premium (Appendix A) are offered — Wholesaler and Retailer plans are separate catalogs per §2.3.2/§2.3.3 (their exact tiers are not broken into named tiers in the SRS the way Importer's are; the SRS lists **capabilities**, not tier names, for these two — see Appendix A transcription in §6 below). **Price not specified in source document** for any plan (Appendix D #1).

**US-SUB-03 — Server-side entitlement enforcement**
Requirement Source: SUB-FR-03, SEC-NFR-04 · Release: R1 · Priority: Must

Given any action gated by plan (product limit, inquiry limit, search priority, featured placement, analytics depth, support level), When attempted, Then the check is performed via `$business->subscription()->can('capability_key')` resolved entirely server-side against `subscription_entitlements` — never against a client-sent plan name. See §11 Entitlement Architecture.

**US-SUB-04 — View plan/usage/renewal/billing history**
Given an active subscription, When I open "My Subscription", Then I see plan name, usage counters vs. limits, renewal date, and invoice history.

**US-SUB-05 — Upgrade/downgrade/cancel**
Given I upgrade, When confirmed, Then new entitlements apply immediately. Given I downgrade or cancel, When confirmed, Then current entitlements remain until the paid period ends, then the plan changes (SUB-FR-05) — this must not silently truncate a period the buyer already paid for.

**US-SUB-06 — Recurring billing**
Given monthly or annual billing, When the period elapses, Then the payment provider is charged via webhook-driven recurring billing; failure triggers `payment_failed` handling distinct from product-order payments (kept isolated per §28 instruction, even though product payment itself is R4).

**US-SUB-07 — Trial & promotional grants**
Given admin grants a trial/promo to an importer, When applied, Then entitlements activate without requiring a payment method, for the admin-specified duration.

**US-SUB-08 — Expiry → restricted state**
Given a subscription lapses, When processed, Then the account's products are hidden (not deleted) and the seller is prompted to renew — matches SEL-FR-07/08's non-destructive pattern.

**US-SUB-09 — Standalone promotional products (R2)** and **US-SUB-10 — Free Customer account, no paid plan shown (R3)** are listed in full in §4.11/4.12.

### 4.7 Analytics, Buyer Tools, Notifications, Admin (R1) — compact acceptance criteria

**US-ANL-01** Given a date range, When a seller opens Analytics, Then profile views/product views/inquiry count for that range are shown (basic tier, all plans).
**US-ANL-02** Given a Pro/Premium plan, When Analytics is opened, Then search terms, top products, buyer distribution by governorate, and view→inquiry conversion are additionally shown — gated by the same entitlement service as US-SUB-03.
**US-ANL-03** Given any inquiry status, When the Leads screen loads, Then all inquiries show status + last activity, filterable.
**US-ANL-04** Given a seller creates a time-bound offer, When the end date passes, Then the offer auto-expires and reverts pricing display.

**US-BUY-01** Given past inquiries/RFQs, When I open history, Then I see them chronologically with status.
**US-BUY-02** Given I save a supplier, When duplicated, Then no duplicate row is created (same pattern as US-SRC-08).
**US-BUY-03** Given I set a market alert (fabric type/price/supplier), When a matching product is published, Then I receive a notification (`market_alert_match` — **Implementation Assumption**: not a named event in NOT-FR-01's list, but required by BUY-FR-03; added to the Notification Matrix §10).
**US-BUY-04** Given products published in the last N days matching my interests, When I open "Recently arrived", Then they're listed — **Implementation Assumption**: "recent" window not specified by SRS; defaulted to 7 days, configurable via Admin (MNT-NFR-02).
**US-BUY-05** Given the same fabric spec exists across suppliers, When I compare, Then prices are shown side-by-side (shares implementation with US-SRC-09).

**US-NOT-01** Given a triggering event (new inquiry, RFQ reply, new message, order status change, subscription expiring, review result), When it occurs, Then push + in-app notification is sent per the Notification Matrix (§10).
**US-NOT-02** Given notification categories, When a user edits preferences, Then future notifications respect them.
**US-NOT-03** Given an account/financial event (e.g., password reset, subscription payment failure), When it occurs, Then SMS/email is sent regardless of push preference (these are never optional-out, per SRS wording "ال يصح تفويتها").
**US-NOT-04** Given a marketing notification, When sent, Then it only reaches users who explicitly opted in, and is technically distinct from operational notifications (separate preference flag, separate suppression list).

**US-ADM-01** Given a pending verification request, When admin approves/rejects, Then status updates and reason is logged; on reject, the business is notified with the reason.
**US-ADM-02** Given a pending-review product, When admin approves/rejects/requests edits, Then the product's visibility updates accordingly and the seller is notified.
**US-ADM-03** Given admin edits fabric types/materials/colors/units, When saved, Then changes are immediately reflected in product-creation forms and search filters (MNT-NFR-02).
**US-ADM-04** Given admin edits a plan's price/entitlements/trial length, When saved, Then it applies to new subscriptions; existing subscriptions follow US-SUB-05's non-retroactive-shrink rule unless admin explicitly forces it (destructive action, needs confirmation per UI-FR-08 pattern).
**US-ADM-05** Given admin selects featured suppliers/products/banners, When saved, Then they appear in the designated homepage/search slots (US-SRC-10).
**US-ADM-06** Given the liquidity dashboard, When opened, Then it shows active sellers/products/buyers, weekly inquiries, and zero-result search terms — the SRS's own definition of marketplace health (§7 "Enough Supply + Enough Buyers = Marketplace Liquidity").
**US-ADM-07** Given admin suspends/bans an account, When confirmed (destructive action), Then the account loses access immediately and an audit log entry is created.
**US-ADM-08** Given a report/dispute, When filed, Then it enters a ticket queue admin can investigate and resolve.
**US-ADM-09** Given any admin action covered by ADM-FR-01..08, When performed, Then it's written to an **immutable** audit log (append-only table, no update/delete permitted at the application layer).
**US-ADM-10** Given the supply-building phase, When ops staff onboard a supplier on their behalf, Then the resulting account is flagged `onboarded_by_admin=true` for traceability, and behaves identically to a self-registered account thereafter.

### 4.8 UI, Interface & Data-Protection Stories (R1)

**US-UI-01..08** map directly to UI-FR-01..08 (account-type chooser, RTL-first with EN toggle, per-role navigation, buyer home layout, product card fields, bottom nav, locale-aware formatting, destructive-action confirmation) — these are primarily mobile/frontend stories; backend's obligation is to (a) return `direction`-agnostic UTF-8 text, (b) return locale-aware formatted values only where the client can't derive them (e.g., pre-formatted currency string alongside raw numeric value), and (c) require an explicit `confirm=true` parameter on destructive endpoints (delete product, cancel subscription, delete account) as a server-side backstop to the client's confirmation UI.

**US-ACC-09 — Account deletion**
Requirement Source: ACC-FR-09, DAT-FR-03 · Release: R1 · Priority: Must

Given a user requests deletion, When confirmed, Then personal identifiers are removed/anonymized while financial records legally required are retained in anonymized form (DAT-FR-03) — implemented as a queued `AnonymizeUserData` Job, not an immediate hard delete, so financial-retention rules can be honored.

**US-DAT-01 (DAT-FR-01/05)** — Data minimization & aggregated analytics privacy are cross-cutting rules enforced in Policies/Resources rather than a standalone feature; captured as Business Rules BR-DAT-01/02 in §6.

### 4.9 R2 — Retailer + In-App Orders

**US-ACC-08 — Sub-users** Given a business account, When it invites a sub-user with limited permissions (e.g., "sales staff — catalog only"), Then that sub-user can act within scope but not manage subscriptions/verification.
**US-ACC-10 — Account type change** Given a wholesaler requests to become an importer, When admin approves, Then the account type changes; history of the change is retained (audit log).
**US-SEL-04 — Product video** (see §4.2, already detailed).
**US-SEL-12 — Retailer shop profile & own listings** Given a retailer, When they create a shop profile, Then it behaves like a supplier profile for the retailer's own products, distinct from their buyer-side supplier-discovery activity (dual role, SRS §2.3.3).
**US-SEL-13 — Custom product groups** Given a seller, When grouping products into self-defined categories, Then groups are private organizational tags, not part of the public taxonomy.
**US-SUB-09 — Standalone promotional products** Given a seller purchases "featured supplier"/"featured product"/homepage placement separately from their plan, When purchased, Then the promotion activates for its purchased duration independent of the underlying plan tier.
**US-ANL-05 — Aggregated market insights (Premium, C)** Given a Premium account, When Market Insights is opened, Then anonymized, aggregated demand trends by fabric type/region are shown — never buyer-identifying data (DAT-FR-05).
**US-SI-06 — XLSX/CSV catalog import/export** Given a seller exports their catalog, When downloaded, Then it matches the same template used for bulk import (US-SEL-09), enabling edit-and-reimport workflows.

**Orders (R2) — full acceptance criteria:**

**US-ORD-01 — Create order**
Given one or more products from a single seller, When I create an order specifying quantity per item, Then an `Order` + `OrderItem`s are created in `created` status. Given products span multiple sellers, When I try to check out as one order, Then the system splits into one order per seller (ORD-FR-01 says "from a single seller" — **Implementation Assumption**: multi-seller carts split automatically rather than being blocked, to avoid a dead-end UX; flagged as an assumption since the SRS doesn't address multi-seller carts).

**US-ORD-02 — Order lifecycle**
Given an order exists, When its status changes, Then it strictly follows: `created → seller_confirmed → preparing → shipped_or_delivered → completed`, with `cancelled`/`rejected` as terminal states reachable from earlier steps only (ORD-FR-02). No skipped-state transitions are permitted at the API layer.

**US-ORD-03 — Order history & timeline**
Given an order, When either party views it, Then the full status timeline (with timestamps) is shown identically to both parties (no asymmetric information).

**US-ORD-04 — Seller confirms/partially confirms/rejects**
Given a new order, When the seller responds, Then they can fully confirm, partially confirm specific items/quantities, or reject with a mandatory reason — partial confirmation updates `OrderItem.confirmed_quantity` independently per line.

**US-ORD-05 — Inventory deduction (S)**
Given a seller tracks inventory, When an order is confirmed, Then `products.quantity_available` is decremented by the confirmed quantity; sellers who don't track inventory (price-on-contact style catalogs) are unaffected.

**US-ORD-06 — Preliminary quotation document (S)**
Given an order is confirmed, When generated, Then a PDF/structured quotation document is produced summarizing items, quantities, and agreed price for the buyer's records.

Dependencies for all Order stories: Subscriptions (plan may gate order volume — not specified, flagged), Catalog (product/pricing source of truth), Notifications (`order_created`, `order_updated`).
Security Considerations: ownership check — only the order's buyer or the seller's business may view/act on it; sub-users (ACC-FR-08) need explicit order-management permission.

### 4.10 R3 — End Customer

**US-ACC-06 — Customer registration (phone-only)** covered in §4.1.
**US-SEL-12 — Retailer shop for customers** (already listed, R3-facing side).
**US-SRC-12 / 13 — Customer search & distance ranking**
Given a customer searches, When results render, Then they show type/color/material/price/availability/**nearest shop**, ranked by distance from the customer's current location (requires HW-FR-02 permission flow, US-LOC-01).
**US-LOC-01 — Location permission & nearby search**
Given a customer opens a feature requiring location (nearest shop), When first triggered, Then the OS location permission is requested at that moment — never at app launch or account creation (HW-FR-02, §31 instruction). Given permission is denied, When search runs, Then results are shown without distance ranking rather than blocking the feature entirely — **Implementation Assumption**, since SRS doesn't specify the denied-permission fallback.
**US-INQ-10 — Retailer receives customer inquiries** Shares the Inquiry model (US-INQ-01) with `seller_business.account_type = retailer` and `buyer.account_type = customer`.
**US-SUB-10 — Free customer plan** Given a customer account, When any monetization surface renders, Then no subscription/paywall UI is ever shown to this account type.

### 4.11 R4 — Payments, Shipping & Advanced Services

**US-PAY-01 — Online order payment**
Requirement Source: ORD-FR-07 · Release: R4 · Priority: Must

As a: buyer with a confirmed order
I want: to pay online through a compliant payment provider
So that: the transaction completes inside the platform

Acceptance Criteria:
- Given a confirmed order, When I choose to pay online, Then I'm redirected to/embedded in the payment provider's hosted flow — **card data never touches THOB servers** (SEC-NFR-07).
- Given the provider sends a webhook, When received, Then it is signature-verified before the order's payment status is updated (no trusting unauthenticated webhook payloads).
- Given payment succeeds, When processed, Then `payment_success` notification fires and order proceeds toward `preparing`.
- Given payment fails, When processed, Then `payment_failed` fires and the order remains actionable (retry payment), not silently stuck.
- Subscription billing (US-SUB-06) and product-order payment are handled by the same Payments module but kept logically isolated (§28 instruction) — distinct webhook handlers, distinct reconciliation reports, sharing only the provider-integration client.

Database Impact: `payments` (payable_type/payable_id polymorphic — order or subscription; provider_reference; status; amount; currency).
Security Considerations: PCI-DSS-compliant provider only; webhook signature verification; idempotent webhook handling (replay-safe).

**US-PAY-02 — Wallet / installments / pay-on-completion (C)**
Requirement Source: ORD-FR-09 · Release: R4 · Priority: Could

Given the "Could" priority, this is built only if capacity allows and is explicitly out of scope for R4's Must-have acceptance. If built: wallet balance is a ledger (`wallet_transactions`), never a mutable single balance field, to keep an auditable trail.

**US-SHP-01 — Shipment assignment & tracking**
Requirement Source: ORD-FR-08, §30 · Release: R4 · Priority: Must

As a: seller (or platform-assigned carrier)
I want: to assign shipping and track the shipment
So that: the buyer knows the delivery status

Acceptance Criteria:
- Given an order is `preparing`, When shipping is assigned, Then a `Shipment` record is created with method and (if applicable) a tracking number.
- Given the carrier/provider updates status, When received, Then the Shipment's status updates and `shipment_updated` notification fires to the buyer.
- This is a shipping **integration**, not a logistics platform THOB owns (§30 instruction) — no owned fleet, no route optimization.

Database Impact: `shipments` (order_id FK, method, tracking_number, status, carrier_reference).

**US-SUB-09-cont — Market Insights "لاحقًا" for Premium**
The Appendix A table marks "Market Insights" for Premium as "لاحقًا" (later/future) even within Premium — treated as an R4+/roadmap capability, not guaranteed at R2 alongside the rest of Premium's entitlements. This distinction is preserved rather than flattened into "Premium = all features now."

All R4 payment/shipping stories share one architectural rule from §28/§30: **release-gated feature flags**, not code that silently activates — a `feature('online_payment')` check prevents R4 functionality from being reachable before its release, even if the module code exists in the monolith ahead of time.

---

## 5. Business Rules Catalogue

| ID | Rule | Source | Release | Actors | Module |
|---|---|---|---|---|---|
| BR-ACC-01 | One account per verified phone number | ACC-FR-01 | R1 | All | Auth |
| BR-ACC-02 | Exactly one account type per user; change requires admin approval | ACC-FR-02, ACC-FR-10 | R1/R2 | All | Users |
| BR-ACC-03 | Governorate is a controlled taxonomy reference, not free text (**Implementation Assumption**) | ACC-FR-03 | R1 | Business accounts | Businesses |
| BR-SEL-01 | Product-count limit is enforced server-side against the active subscription's entitlement, never client-supplied | SEL-FR-10, SEC-NFR-04 | R1 | Importer/Wholesaler/Retailer | Catalog/Subscriptions |
| BR-SEL-02 | New and edited products enter admin review before public visibility, per admin-configurable toggle | SEL-FR-11 | R1 | Admin/System | Catalog |
| BR-SEL-03 | A product has exactly one of {numeric price, "price on contact"} — never both, never neither | SEL-FR-05 | R1 | Importer | Catalog |
| BR-SEL-04 | Deleting a product is a soft-delete; historical inquiries/orders referencing it remain intact | SEL-FR-07 | R1 | Importer | Catalog |
| BR-SUB-01 | Client-supplied plan/entitlement claims are never trusted; all checks resolve server-side | SUB-FR-03, SEC-NFR-04 | R1 | System | Subscriptions |
| BR-SUB-02 | Upgrades apply immediately; downgrades/cancellations apply at the end of the current paid term | SUB-FR-05 | R1 | Importer/Wholesaler/Retailer | Subscriptions |
| BR-SUB-03 | On expiry, seller products are hidden, never deleted | SUB-FR-08 | R1 | System | Subscriptions/Catalog |
| BR-INQ-01 | Every inquiry creates exactly one Lead record on the seller's account | INQ-FR-06 | R1 | System | Inquiries |
| BR-INQ-02 | Inquiry/RFQ volume limits are enforced per the applicable plan, on sender and/or receiver side as defined by that plan | INQ-FR-08 | R1 | System | Inquiries/Subscriptions |
| BR-SRC-01 | Featured ranking boosts are always visually labeled; never an unlabeled ranking manipulation | SRC-FR-10 | R1 | System | Search |
| BR-SRC-02 | Only `published`, non-hidden, non-deleted products from the seller's own active catalog appear in default search | SRC-FR-01..04, SEL-FR-08/11 | R1 | System | Search |
| BR-FAV-01 | A user cannot create duplicate favorite records for the same target | SRC-FR-08, §34 | R1 | Buyer | Favorites |
| BR-CMP-01 | Comparison is capped at 4 items (products or suppliers) | SRC-FR-09, §35 | R1 | Buyer | Comparison |
| BR-ADM-01 | Every admin action against a user/product/subscription/report is written to an immutable audit log | ADM-FR-09 | R1 | Admin | Admin |
| BR-ORD-01 | Order state machine only allows the transitions defined in ORD-FR-02; no skipped or reverse transitions except to the terminal cancelled/rejected states | ORD-FR-02 | R2 | System | Orders |
| BR-ORD-02 | Inventory is only decremented for sellers who opt into inventory tracking | ORD-FR-05 | R2 | System | Orders/Catalog |
| BR-PAY-01 | Card data is never persisted or transmitted through THOB servers | SEC-NFR-07 | R4 | System | Payments |
| BR-PAY-02 | Subscription billing and order payment are processed through logically separate flows sharing only the provider client | §28 instruction | R1/R4 | System | Payments |
| BR-LOC-01 | Location permission is requested only at the moment a location-dependent feature is used | HW-FR-02, §31 | R3 | Customer | Locations |
| BR-DAT-01 | Personal data is collected only to the extent it serves a stated purpose | DAT-FR-01 | R1 | System | All |
| BR-DAT-02 | Seller-facing analytics never reveal the identity of a buyer who has not initiated contact | DAT-FR-05 | R1 | System | Analytics |
| BR-DAT-03 | On account deletion, personal identifiers are anonymized; financial records legally required are retained in anonymized form | DAT-FR-03 | R1 | System | Users |

---

## 6. Subscription / Entitlement Catalogue (SRS Appendix A — transcribed, not invented)

### Importer Plans

| Capability | Basic | Pro | Premium |
|---|---|---|---|
| Company Profile | ✓ | ✓ | ✓ |
| Product Catalog | ✓ | ✓ | ✓ |
| Product limit | Limited | Large/unlimited per policy | Large/unlimited per policy |
| Receive inquiries | ✓ | ✓ (higher cap) | ✓ (highest cap) |
| Contact Information | ✓ | ✓ | ✓ |
| Search priority | — | ✓ | ✓ Highest |
| Featured Products | — | ✓ | ✓ |
| Featured Supplier Profile | — | ✓ | ✓ |
| Featured Placement (homepage) | — | — | ✓ |
| Promotions/Campaigns | — | Offers & promotions | Full campaigns |
| Analytics | Basic | Advanced | Advanced |
| Leads Management | — | ✓ | ✓ |
| Verified Badge | On verification | On verification | On verification |
| Dedicated Account Support | — | — | ✓ |
| Market Insights | — | — | Later (roadmap, not guaranteed at launch) |

**Note:** "Limited" / "Large/unlimited per policy" are the SRS's own wording — exact numeric caps are **not specified** (Appendix D, Open Decision #2). Do not hardcode numbers; store as admin-editable `subscription_entitlements.value` (MNT-NFR-02).

### Wholesaler Subscription Capabilities (single tier described in SRS — no named sub-tiers given)
Advanced Search · Advanced Filters · Supplier Discovery · Compare Suppliers · Price Comparison · Save Suppliers · Request Quotations · Purchase History · Market Alerts · New Arrivals.

### Retailer Subscription Capabilities (single tier described in SRS — highest value per §2.3.3, no named sub-tiers given)
Store Profile · Add Products · Supplier Discovery · Receive Customer Inquiries · Featured Store/Products · Better Visibility · Analytics · Promotions · Supplier Tools.

### End Customer
Free account. Search, browse, availability, nearest shop, contact. **No paid plan is ever presented** (SUB-FR-10).

**Pricing for every plan and billing cycle: "Price not specified in source document."** (Appendix D #1). The trial period length for founding importers is likewise unspecified — admin-configurable per SUB-FR-07.

---

## 7. Validation Catalogue (excerpt — full matrix generated per module during build)

| Field | Module | Required | Type | Constraints | Release | Source |
|---|---|---|---|---|---|---|
| phone | Users | Yes | string | E.164-normalized, unique | R1 | ACC-FR-01 |
| email | Users | No | string | valid email format | R1 | ACC-FR-01 |
| account_type | Users | Yes | enum | importer\|wholesaler\|retailer\|customer | R1 | ACC-FR-02 |
| company_name | BusinessAccount | Yes (business) | string | max 255 | R1 | ACC-FR-03 |
| governorate_id | BusinessAccount | Yes (business) | FK | must exist in taxonomy | R1 | ACC-FR-03 (Implementation Assumption) |
| verification_document | VerificationDocument | Yes (business) | file | mime: pdf/jpg/png; max size — **Implementation Assumption**, size ceiling not specified, defaulted to 10MB, admin-configurable | R1 | ACC-FR-04, SEC-NFR-05 |
| product.fabric_type_id | Product | Yes | FK | must exist in taxonomy | R1 | SEL-FR-05 |
| product.material | Product | Yes | FK + free text | taxonomy ref, free-text supplement per §5.2 | R1 | SEL-FR-05 |
| product.color_id | Product | Yes | FK, multi-value | taxonomy ref, multiple allowed | R1 | §5.2 |
| product.width_cm | Product | Yes | numeric | unit = cm | R1 | §5.2 |
| product.weight_gsm | Product | No | numeric | — | R1 | §5.2 |
| product.price | Product | Conditional | numeric | required unless price_on_contact=true | R1 | BR-SEL-03 |
| product.moq | Product | No | numeric | — | R1 | SEL-FR-06 |
| product.quantity_available | Product | Yes | numeric | — | R1 | §5.2 |
| product.images | ProductMedia | Yes | file[] | ≥1 image required to publish | R1 | §5.2 |
| product.video | ProductMedia | No | file | R2 | SEL-FR-04 |
| product.status | Product | Yes | enum | draft\|pending_review\|published\|hidden\|unavailable\|rejected | R1 | §5.2 |
| rfq.quantity | RFQ | Yes | numeric | — | R1 | INQ-FR-02 |
| rfq.needed_by_date | RFQ | Yes | date | must be today or future | R1 | INQ-FR-02 (Implementation Assumption on "future only") |
| quotation.valid_until | Quotation | Yes | date | must be future at creation | R1 | INQ-FR-03 |
| order_item.quantity | OrderItem | Yes | numeric | > 0 | R2 | ORD-FR-01 |

---

## 8. Authorization Matrix

C=Create, R=Read, U=Update, D=Delete, Ap=Approve, Rj=Reject, Mg=Manage. "Own" = only own resources.

| Resource | Importer | Wholesaler | Retailer | Customer | Admin |
|---|---|---|---|---|---|
| Own Business Profile | CRUD (own) | CRUD (own) | CRUD (own) | — | RU |
| Products | CRUD (own) | — (buyer only) | CRUD (own, R3+) | — | R, Ap, Rj, U(hide) |
| Verification Request | C (own), R (own) | C (own), R (own) | C (own), R (own) | — | R, Ap, Rj |
| Favorites | CRUD (own) | CRUD (own) | CRUD (own) | CRUD (own) | — |
| Inquiries | R/U (own, as seller) | C, R (own, as buyer) | C/R/U (own, both roles) | C (own, R3) | R (moderation) |
| RFQs | R/U (own, as seller) | C, R (own) | C/R (own) | — | R |
| Quotations | C, R (own) | R (own) | C, R (own) | — | R |
| Conversations/Messages | CRU (own, participant only) | CRU (own) | CRU (own) | CRU (own, R3) | R (on report only) |
| Orders (R2+) | RU (own, as seller) | C, R (own, as buyer) | C/R/U (own, both roles) | C, R (own, R3) | R, Mg (dispute) |
| Payments (R4) | R (own) | R (own) | R (own) | R (own) | R, Mg |
| Shipping (R4) | RU (own, as seller) | R (own) | RU (own) | R (own) | R, Mg |
| Analytics | R (own) | R (own) | R (own) | — | R (aggregate) |
| Subscriptions | CRUD (own) | CRUD (own) | CRUD (own) | — | Mg (plans/grants) |
| Admin Functions (taxonomy, featured, bans, audit) | — | — | — | — | Mg |

All checks implemented as Laravel Policies (`ProductPolicy`, `InquiryPolicy`, `ConversationPolicy`, `OrderPolicy`, `SubscriptionPolicy`, `AdminPolicy`), always re-validating: authenticated user → role → resource ownership → business ownership → subscription entitlement → admin override — never trusting a client-sent ID or role claim (§21 instruction, SEC-NFR-03/04).

---

## 9. Architecture Decision: Laravel Modular Monolith

**One Laravel application. One MySQL database. One deployment.** No microservices, no service-to-service HTTP calls, no separate deployable "services." External integrations (S3, Pusher, OTP/SMS, payment provider, maps provider, push notifications) are third-party APIs called from within the monolith — they are not internal services.

This is dictated by the shared-hosting constraint (§12 instruction) and is proportionate to actual load (500 concurrent users at launch, NFR-PRF-04) — nothing here justifies Kubernetes, Kafka, RabbitMQ, event sourcing, CQRS, GraphQL, or a mandatory Elasticsearch cluster. Where future scale might justify one of these, it is called out explicitly as a **Future Scalability Option**, never adopted now.

### 9.1 Module Boundaries

Modules represent business capabilities actually present in the SRS — not one module per table:

```
Modules/
├── Auth/            (registration, OTP, login/session — ACC-FR-01/07)
├── Users/            (user profile, account type, sub-users — ACC-FR-02/08/09/10)
├── Businesses/       (business account, profile — ACC-FR-03, SEL-FR-01/12)
├── Verification/     (documents, review — ACC-FR-04/05, DAT-FR-02)
├── Taxonomy/         (fabric types, materials, colors, units, governorates — ADM-FR-03)
├── Catalog/          (products, media, bulk import/export — SEL-FR-02..11, SI-FR-06)
├── Search/           (search/filter/sort, zero-result logging — SRC-FR-*)
├── Favorites/        (saved products/suppliers — SRC-FR-08, BUY-FR-02, §34)
├── Comparison/       (side-by-side compare, cap 4 — SRC-FR-09, §35)
├── Inquiries/         (inquiries, RFQs, quotations, leads — INQ-FR-*, §36)
├── Chat/             (conversations, messages, Pusher — INQ-FR-04, §17/18)
├── Subscriptions/     (plans, entitlements, billing — SUB-FR-*, §19/20)
├── Orders/           (orders, order items, lifecycle — ORD-FR-01..06, R2)
├── Payments/         (subscription billing + order payment, isolated flows — SI-FR-03, ORD-FR-07/09, R4)
├── Shipping/          (shipment, tracking — ORD-FR-08, §30, R4)
├── Analytics/         (seller analytics, market insights, event tracking — ANL-FR-*, §33)
├── Notifications/     (push/SMS/email, preferences — NOT-FR-*)
├── Locations/         (governorates, distance/nearby — HW-FR-02, SI-FR-05, R3)
└── Admin/            (moderation, taxonomy mgmt, plan mgmt, liquidity dashboard, audit log — ADM-FR-*)
```

Not built as separate modules: a "Reports" module (folded into Admin's ticket handling), a dedicated "Wholesalers/Retailers" module (these are `account_type` values on Users/Businesses, not distinct domains), a "Suppliers" module (Businesses already covers this — "Supplier" is a role a Business plays, not a separate entity).

### 9.2 Module Internal Structure (pragmatic, not maximal)

```
Modules/Catalog/
├── Models/            (Product, ProductMedia, ProductPriceTier)
├── Http/
│   ├── Controllers/    (ProductController, BulkImportController)
│   └── Requests/       (StoreProductRequest, UpdateProductRequest)
├── Policies/           (ProductPolicy)
├── Actions/            (CreateProduct, PublishProduct — used where a single, reusable
│                        piece of business logic is invoked from more than one place)
├── Services/           (only where logic genuinely needs isolation, e.g.
│                        BulkImportService parsing/validating a spreadsheet)
├── Routes/             (api.php)
├── Resources/          (ProductResource, ProductCardResource)
├── Database/
│   ├── Migrations/
│   └── Seeders/
└── Tests/
```

Not every module needs every directory — e.g., `Favorites` needs `Models/`, `Http/`, `Policies/`, `Routes/`, `Tests/` and nothing else; no `Services/` folder is created just for symmetry. No generic repository interfaces, no generic "Manager" classes, no domain-framework abstractions are introduced (§11 instruction).

### 9.3 What modules share
Authentication (Sanctum), the MySQL database and its transaction boundaries, configuration, the S3 filesystem disk, the `/api/v1/` namespace and its response conventions, Laravel's Event/Listener and Notification systems, and the queue (for bulk import, media processing, notification dispatch, webhook handling).

### 9.4 Communication between modules
Direct PHP method calls / Eloquent relationships / Laravel Events — **never** internal HTTP calls between modules. E.g., when Orders needs to check a seller's subscription entitlement, it calls `app(EntitlementService::class)->can($business, 'create_order')` — a normal service-container call within the same process, not an HTTP request to a "Subscriptions service."

---

## 10. Database Architecture (single MySQL database)

Only tables required by actual SRS requirements are created — not the full illustrative list from the brief. Each entry: Purpose · Key fields · PK/FK · Relationships · Indexes · Ownership.

### 10.1 Identity & Business
**users** — Purpose: identity, credentials, role, language, notification prefs (§5.1 `User`). Fields: id, phone (unique), email (nullable, unique), password_hash, account_type (enum), language, status, created_at. PK: id. Indexes: unique(phone). Ownership: self.

**business_accounts** — Purpose: company data + verification status (§5.1 `BusinessAccount`). Fields: id, user_id (FK users), company_name, activity, governorate_id (FK governorates), address, contact_person, verification_status (enum: unverified/pending/verified/rejected), onboarded_by_admin (bool). PK: id. FK: user_id → users. Indexes: (governorate_id), (verification_status). Ownership: user_id owner + sub-users.

**business_sub_users** — Purpose: ACC-FR-08 limited-permission staff. Fields: id, business_account_id (FK), user_id (FK), permissions (JSON — small, non-relational permission flags; justified deviation from "no JSON for relational data" since permission sets are inherently a flexible bag, not queried relationally). PK: id.

**verification_requests** / **verification_documents** — Purpose: ACC-FR-04, DAT-FR-02. Fields (requests): id, business_account_id, status, reviewed_by (FK users, admin), reviewed_at, rejection_reason. Fields (documents): id, verification_request_id, disk (private S3), key, mime_type, size, document_type (FK to a small `document_types` taxonomy). Indexes: (business_account_id, status). Ownership: business account + admin-only read.

### 10.2 Taxonomy
**fabric_types, materials, colors, units, governorates** — small reference tables (id, name_ar, name_en, active). Admin-managed (ADM-FR-03), referenced by FK from `products` — no polymorphism, no generic "taxonomy_terms" table, since each list has genuinely distinct shape/usage and the SRS calls them out as separate concepts.

### 10.3 Catalog
**products** — Purpose: §5.1 `Product`, §5.2 fields. Fields: id, business_account_id (FK), fabric_type_id (FK), material_id (FK), width_cm, weight_gsm (nullable), price (nullable), price_on_contact (bool), currency (default EGP), unit (per-meter/per-kg), moq (nullable), quantity_available, governorate_id (FK), status (enum: draft/pending_review/published/hidden/unavailable/rejected), created_at, updated_at, deleted_at (soft delete, BR-SEL-04). PK: id. Indexes: (status), FULLTEXT(name_ar, name_en / search_text), (fabric_type_id, color_id, governorate_id, price) composite for filter queries. Ownership: business_account_id.

**product_colors** — pivot table (product_id, color_id) since color is multi-value (§5.2).

**product_price_tiers** — (product_id, min_qty, unit_price) for SEL-FR-06 quantity tiers.

**product_media** — Purpose: §16 S3 media. Fields: id, product_id (FK), disk, key/path, mime_type, size, original_name, type (image/video), sort_order. Ownership: derived from product's business_account_id.

### 10.4 Discovery & Buyer Tools
**favorites** — (id, user_id, favoritable_type, favoritable_id) polymorphic — justified here because "favoritable" is genuinely either a Product or a BusinessAccount and the access pattern (toggle/list) is identical for both; unique(user_id, favoritable_type, favoritable_id) enforces BR-FAV-01.

**saved_suppliers** — could be folded into `favorites` with `favoritable_type = BusinessAccount`; modeled that way to avoid a redundant table (design decision, not from a table explicitly named this in the SRS).

**comparisons** — ephemeral, client-held selection (up to 4 IDs) — **not persisted server-side** unless the SRS requires cross-device comparison lists, which it doesn't state; comparison is computed on-demand via `GET /api/v1/compare?ids=1,2,3,4`, avoiding an unnecessary table.

**alerts** — (id, user_id, fabric_type_id nullable, price_ceiling nullable, business_account_id nullable, active) for BUY-FR-03.

**search_logs** — (id, term, result_count, user_id nullable, created_at) for SRC-FR-11's zero-result tracking; only zero/low-result rows need retain beyond a short TTL for the Admin dashboard — avoids building a full analytics warehouse for raw search logs.

### 10.5 Inquiries, RFQ, Quotation, Chat
**inquiries** — (id, buyer_id (FK users), seller_business_id (FK business_accounts), product_id nullable (FK products), lead_status (enum: new/in_progress/done/not_completed), created_at). Indexes: (seller_business_id, lead_status).

**rfqs** — (id, inquiry_id FK, product_id FK, quantity, color_id nullable, needed_by_date).

**quotations** — (id, rfq_id FK, price, availability_note, valid_until).

**conversations** — (id, inquiry_id FK unique, buyer_id, seller_business_id).

**messages** — (id, conversation_id FK, sender_id FK users, body, read_at nullable, created_at). Index: (conversation_id, created_at) for pagination.

### 10.6 Subscriptions
**subscription_plans** — (id, account_type (importer/wholesaler/retailer), name, price nullable — *"not specified in source"*, billing_cycle enum). 

**subscription_entitlements** — (id, plan_id FK, key (e.g., product_limit, inquiry_limit, search_priority, featured_placement, analytics_depth, support_level), value) — key/value so Admin can add/edit entitlement keys without a migration (MNT-NFR-02), while `subscription_plans` itself stays relational, not a JSON blob.

**subscriptions** — (id, business_account_id FK, plan_id FK, status (active/expired/cancelled/restricted), current_period_end, trial_ends_at nullable).

**subscription_usage_counters** — (subscription_id FK, key, current_value) — running counters checked against `subscription_entitlements.value` server-side (BR-SUB-01).

### 10.7 Orders, Payments, Shipping (R2/R4)
**orders** — (id, buyer_id FK, seller_business_id FK, status enum per BR-ORD-01, created_at).
**order_items** — (id, order_id FK, product_id FK, quantity, confirmed_quantity nullable, unit_price_snapshot).
**payments** — (id, payable_type/payable_id polymorphic [Order|Subscription], provider, provider_reference, status, amount, currency, created_at) — polymorphic here is justified: both payables need identical provider/webhook/status handling, and the SRS itself says to keep payment logic isolated but shared at the provider-integration layer (§28).
**shipments** — (id, order_id FK unique, method, tracking_number nullable, status, carrier_reference nullable).

### 10.8 Notifications & Audit
**notifications** — Laravel's standard polymorphic notifications table (notifiable_type/id, type, data JSON, read_at) — using Laravel's built-in mechanism rather than a bespoke one; JSON payload here is appropriate since notification payload shape genuinely varies per notification type and is never queried relationally.
**notification_preferences** — (user_id FK, category, channel, enabled).
**audit_logs** — (id, actor_id FK users [admin], action, auditable_type, auditable_id, metadata JSON, created_at) — append-only; no `updated_at`, no application-level UPDATE/DELETE route exists for this table (BR-ADM-01).

### 10.9 Analytics
**analytics_events** — (id, event_type enum [profile_view, product_view, search, inquiry_created, rfq_created, quotation_created, message_sent, favorite_added], subject_type/id, actor_id nullable, metadata JSON, created_at) — a single lean event table, not a data warehouse (§33 instruction); aggregation for the Analytics/Admin dashboards is computed via scheduled jobs into small rollup tables (`daily_business_stats`) rather than querying raw events live at scale.

### 10.10 Design decisions explained
- **No generic "entity" or "activity" tables.** Each domain concept gets its own table with real foreign keys.
- **JSON used only twice** (`business_sub_users.permissions`, `notifications.data`, `audit_logs.metadata`) — each case is genuinely unstructured/variable-shape data, not a relational shortcut.
- **Polymorphism used only twice** (`favorites.favoritable`, `payments.payable`) — both cases have identical CRUD/authorization shape across their two target types; this is deliberate, not "excessive polymorphism" (§14 avoids it elsewhere, e.g., colors/materials are NOT polymorphic taxonomy terms, they're distinct tables since the SRS treats them as distinct dimensions).
- **Soft deletes** only on `products` (BR-SEL-04, so inquiries/orders referencing a deleted product retain integrity) — not applied blanket-wide.

---

## 11. API Architecture (`/api/v1/`, REST)

Standard response envelope for errors (per §23 instruction):
```json
{ "message": "Validation failed", "errors": { "price": ["The price field is required."] } }
```
No SQL errors, stack traces, secrets, or infra details are ever exposed (§23).

### 11.1 Endpoint catalog (grouped, R1 unless noted)

**auth** — `POST /auth/otp/request`, `POST /auth/otp/verify`, `POST /auth/register`, `POST /auth/login`, `POST /auth/logout`, `POST /auth/account-type`.

**users** — `GET /me`, `PATCH /me`, `DELETE /me` (account deletion, US-ACC-09), `PATCH /me/notification-preferences`. R2: `POST /businesses/{id}/sub-users`, `PATCH/DELETE /sub-users/{id}`.

**businesses** — `POST /businesses` (create profile), `GET/PATCH /businesses/{id}`, `GET /businesses/{id}/catalog` (public), `GET /businesses` (supplier search, SRC-FR-07).

**verification** — `POST /businesses/{id}/verification-documents`, `GET /businesses/{id}/verification-status`.

**subscriptions** — `GET /subscription-plans?account_type=`, `POST /subscriptions` (subscribe), `PATCH /subscriptions/{id}` (upgrade/downgrade/cancel), `GET /subscriptions/{id}/usage`, `GET /subscriptions/{id}/invoices`.

**catalog / products** — `GET /products` (search/filter/sort, paginated), `POST /products`, `GET/PATCH/DELETE /products/{id}`, `POST /products/{id}/media`, `POST /products/{id}/duplicate`, `PATCH /products/{id}/status` (hide/unavailable), `POST /catalog/bulk-import`, `GET /catalog/bulk-import/{jobId}`, `GET /catalog/export` (R2).

**taxonomy** — `GET /taxonomy/fabric-types`, `/materials`, `/colors`, `/units`, `/governorates` (public, read-only outside Admin).

**favorites** — `GET /favorites`, `POST /favorites`, `DELETE /favorites/{id}`.

**comparison** — `GET /compare?type=product|supplier&ids=1,2,3,4`.

**inquiries / rfq / quotations** — `POST /inquiries`, `GET /inquiries` (mine, filterable by lead_status), `PATCH /inquiries/{id}/status`, `POST /inquiries/{id}/rfq`, `POST /rfqs/{id}/quotations`.

**chat** — `GET /conversations`, `GET /conversations/{id}/messages` (paginated), `POST /conversations/{id}/messages`, `POST /conversations/{id}/read`, `POST /broadcasting/auth` (Pusher channel auth).

**alerts** — `GET/POST/DELETE /alerts`.

**notifications** — `GET /notifications`, `POST /notifications/{id}/read`.

**analytics** — `GET /businesses/{id}/analytics?range=` (basic + advanced per entitlement), `GET /businesses/{id}/leads`.

**admin** — `GET/POST /admin/verification-requests/{id}/approve|reject`, `GET/POST /admin/products/{id}/approve|reject|hide`, `CRUD /admin/taxonomy/*`, `CRUD /admin/subscription-plans`, `POST /admin/featured`, `GET /admin/dashboard/liquidity`, `POST /admin/accounts/{id}/suspend|reactivate`, `GET/POST /admin/reports`, `GET /admin/audit-logs`, `POST /admin/businesses` (assisted onboarding, ADM-FR-10).

**orders (R2)** — `POST /orders`, `GET /orders`, `GET/PATCH /orders/{id}` (status transitions), `GET /orders/{id}/timeline`.

**payments (R4)** — `POST /orders/{id}/pay`, `POST /webhooks/payments/{provider}`, `POST /subscriptions/{id}/pay`, `POST /webhooks/subscriptions/{provider}`.

**shipping (R4)** — `POST /orders/{id}/shipment`, `GET /orders/{id}/shipment`, `POST /webhooks/shipping/{provider}`.

**locations (R3)** — `GET /shops/nearby?lat=&lng=`.

Every endpoint: validated via Form Requests, authorized via Policies, paginated where it returns a collection, and documented with request/response/error shape at build time (full per-endpoint OpenAPI spec generated from route+FormRequest+Resource definitions, not hand-duplicated in this document).

---

## 12. S3 Media Architecture

```
Client → Laravel → Laravel Filesystem (S3 disk) → Amazon S3 (+ CDN for public media)
```
- Product media: validated (MIME + size, SEC-NFR-05) → stored on the **public** S3 disk/prefix → served via CDN with adaptive resize (CI-FR-02, PRF-NFR-03). `product_media` stores disk, key, mime_type, size, original_name, product_id — never binary in MySQL.
- Verification documents: stored on a **private** S3 disk/prefix, never a predictable public URL. Access only via a signed, time-limited URL generated after a Policy check confirms the requester is the owning business or an authorized admin role (§16, §40).
- No custom media server is built; no local persistent disk storage on shared hosting (§12/§16 instruction).

---

## 13. Search Architecture

R1: **MySQL-based search** — FULLTEXT index for free-text (SRC-FR-01), composite B-tree indexes for filter/sort columns (SRC-FR-02/03), standard `LIMIT/OFFSET` or cursor pagination. This meets NFR-PRF-01 (sub-2s p95 at 100k products) without introducing a dedicated search engine.

Documented, not built: if catalog size or query complexity outgrows MySQL FULLTEXT, a dedicated search engine (e.g., Meilisearch/OpenSearch) sits behind the same `SearchService` interface used today — **Future scalability option, not MVP infrastructure** (per SCL-NFR-02 and §24/§11 instructions). No Elasticsearch is introduced now.

---

## 14. Notification Matrix

| Event | Trigger | Recipient | Channel | Persisted | Read state |
|---|---|---|---|---|---|
| verification_submitted | US-ACC-04 | Admin queue | in-app | Yes | Yes |
| verification_approved/rejected | US-ADM-01 | Business owner | push + in-app + SMS/email (financial-adjacent) | Yes | Yes |
| product_submitted | US-SEL-02/11 | Admin queue | in-app | Yes | Yes |
| product_approved/rejected | US-ADM-02 | Seller | push + in-app | Yes | Yes |
| new_inquiry | US-INQ-01 | Seller | push + in-app | Yes | Yes |
| new_rfq | US-INQ-02 | Seller | push + in-app | Yes | Yes |
| quotation_received | US-INQ-03 | Buyer | push + in-app | Yes | Yes |
| new_message | US-CHT-03 | Recipient | push + in-app (+ Pusher realtime) | Yes | Yes |
| market_alert_match | US-BUY-03 | Buyer | push + in-app | Yes | Yes |
| subscription_expiring / expired | US-SUB-08 | Business owner | push + in-app + SMS/email | Yes | Yes |
| order_created / order_updated | R2, US-ORD-* | Both parties | push + in-app | Yes | Yes |
| payment_success / payment_failed | R4, US-PAY-01 | Payer | push + in-app + SMS/email | Yes | Yes |
| shipment_updated | R4, US-SHP-01 | Buyer | push + in-app | Yes | Yes |
| review_result (generic) | US-ADM-01/02 | Business owner | push + in-app | Yes | Yes |

Marketing notifications (NOT-FR-04) are a separate, opt-in-only category, never merged with the operational events above.

---

## 15. Security Architecture Checklist

- TLS 1.2+ on all traffic; no credentials/tokens in URLs (SEC-NFR-01, CI-FR-01).
- Passwords hashed with a modern algorithm (bcrypt/argon2); OTP hashed, expires in 5 minutes, attempt-rate-limited (SEC-NFR-02).
- All authorization enforced server-side via Policies — role + ownership + business ownership + subscription entitlement + admin override, in that order (§21, SEC-NFR-03/04).
- Client-supplied subscription/entitlement/role claims are never trusted (BR-SUB-01).
- Uploaded files validated by MIME + size before acceptance; queued validation before public exposure (SEC-NFR-05).
- OWASP API & Mobile Top 10 hardening: rate limiting (`ThrottleRequests`), mass-assignment guards (`$fillable`), CSRF/Sanctum token auth, input validation on every Form Request, output encoding; security review before every major release (SEC-NFR-06).
- Card data never touches THOB servers; PCI-DSS-compliant provider handles all card entry (SEC-NFR-07, BR-PAY-01) — R4 only.
- Verification documents: encrypted at rest, private S3 prefix, no predictable URLs, signed time-limited download links, admin-role-gated (DAT-FR-02, §40).
- Webhook endpoints (payments R4, shipping R4) verify provider signatures and are idempotent against replay.
- Audit log is append-only at the application layer — no admin action bypasses it (BR-ADM-01).
- Data minimization and anonymization on deletion per Egyptian Law 151/2020 (DAT-FR-01/03).

---

## 16. Testing Strategy

Per MNT-NFR-03, automated tests must cover **at minimum**: authentication, entitlement/authorization enforcement, search, and the inquiry pathway. Concretely:

- **Unit tests**: OTP expiry/rate-limit logic, entitlement-resolution service, price/price-on-contact XOR rule (BR-SEL-03), order state-machine transition guard (BR-ORD-01).
- **Feature/integration tests** (per module, Pest/PHPUnit): registration → OTP → account-type selection; business profile + verification upload/review cycle; product CRUD + review-queue gating + plan-limit enforcement; search/filter/sort correctness incl. zero-result logging; inquiry → RFQ → quotation → chat end-to-end; subscription upgrade/downgrade/expiry state transitions; order lifecycle transitions (R2); payment webhook handling incl. signature-invalid rejection (R4); admin moderation + audit-log write-on-every-action.
- **Authorization test suite**: for every Policy, assert 403 for non-owner/non-participant/wrong-role attempts, and success for the correct actor — directly covers the Authorization Matrix (§8).
- **Load test**: 500 concurrent users (NFR-PRF-04), search p95 <2s at 100k products (PRF-NFR-01).
- **Security tests**: forged entitlement claim rejected, malicious file upload rejected, webhook without valid signature rejected, OWASP Top 10 checklist before each major release.
- **CI gate**: the full feature-test suite plus authorization suite runs on every PR; merges are blocked on failure.

---

## 17. Deployment Architecture

Runs on shared hosting from day one (§12 instruction) — no Docker requirement in production, no Kubernetes, no dedicated WebSocket server:
- PHP + Laravel + MySQL over HTTPS.
- Cron (Laravel Scheduler) drives: subscription expiry checks, daily backups, rollup-table aggregation for analytics, notification digest jobs.
- Queue worker (`php artisan queue:work`, run via a persistent process or a scheduled short-lived worker if the host disallows long-running processes) handles bulk import, media processing, webhook processing, notification dispatch.
- External services called over HTTPS: S3, Pusher, OTP/SMS provider, push notification provider, payment provider (R4), maps provider (R3).
- Versioned API (`/api/v1/`) with a ≥6-month deprecation window (MNT-NFR-01).
- Path to scale beyond shared hosting (SCL-NFR-01/02): move to a VPS/managed PHP host with externalized session/cache store (Redis becomes optional infra at that point, not a launch dependency) and, if needed, a MySQL read replica for search-heavy read traffic — all without a redesign, since the app is already stateless-by-convention.

---

## 18. Implementation Order

1. **Foundation**: Auth (OTP, registration), Users, Taxonomy tables + seed data, Businesses (profile).
2. **Verification**: document upload, S3 private storage, Admin review flow, audit log foundation (since Admin needs it from day one).
3. **Subscriptions core**: plans, entitlements, server-side entitlement service — built *before* Catalog, since Catalog's product-limit enforcement depends on it.
4. **Catalog**: products, media (S3 public), bulk import, review queue.
5. **Search**: MySQL FULLTEXT + filters, zero-result logging.
6. **Favorites & Comparison**: low-complexity, high buyer value, quick to ship after Search.
7. **Inquiries/RFQ/Quotation**: the MVP's core value loop.
8. **Chat (Pusher)**: layered on top of Inquiries once the inquiry/RFQ data model is stable.
9. **Notifications**: wired incrementally alongside each module above as their trigger events go live, finalized as its own pass once all R1 events exist.
10. **Admin dashboard**: liquidity metrics, taxonomy management, plan management, moderation queues, ticket/report handling — built in parallel with steps 2–9 since Admin depends on data from all of them.
11. **R1 hardening**: full authorization test suite, load test, security review, MVP acceptance criteria (§7.1 SRS) sign-off.
12. **R2**: Retailer profile/dual-role, sub-users, product video, custom groups, standalone promotions, XLSX export, Orders module.
13. **R3**: End Customer accounts, Locations/maps, distance-ranked search, Retailer customer-facing inquiries.
14. **R4**: Payments (subscription + order, kept isolated), Shipping, advanced Market Insights.

This order keeps every release's own Must-priority items sequenced before its Should/Could items, and never begins an R-later capability's build ahead of its own release's foundation.

---

## 19. Risks

| Risk | Impact | Mitigation |
|---|---|---|
| Shared-hosting resource limits under load spikes (e.g., bulk import, search) | Timeouts, degraded UX | Queue heavy work (bulk import, media processing); documented upgrade path off shared hosting (SCL-NFR-01) |
| Open pricing/limit decisions (Appendix D #1, #2) block subscription launch | Delays R1 launch of paid plans | Entitlements modeled as admin-editable key/value from day one so business can decide pricing without a redeploy |
| Unresolved "price on contact vs. mandatory indicative price" (Appendix D #3) affects price-comparison feature (BUY-FR-05, SRC-FR-09) | Comparison feature quality varies by adoption | Schema supports both states now (BR-SEL-03); comparison UI gracefully handles missing numeric price |
| Verification document requirements not finalized (Appendix D #5) | Onboarding friction or inconsistent gating | Document *type* is taxonomy-driven, not hardcoded, so requirements can change without a deploy |
| Marketplace liquidity — the SRS's own core risk ("Enough Supply + Enough Buyers") | Platform failure if either side doesn't materialize | Assisted onboarding (ADM-FR-10), liquidity dashboard (ADM-FR-06) for early intervention, phased launch (§2.7) |
| Spam/abuse in Inquiries/Chat at launch scale | Trust erosion | Rate limiting + reporting from R1 day one (INQ-FR-09), not deferred |
| Pusher outage | Chat delivery gap | MySQL remains source of truth; REST fallback retrieval (§17/18 architecture) |
| Payment/shipping provider selection undecided | Blocks R4 planning | Payments/Shipping modules built against a provider-agnostic interface; provider chosen closer to R4 |

---

## 20. Future Scalability Strategy (explicitly not built now)

- **Dedicated search engine** (Meilisearch/OpenSearch) behind the existing `SearchService` interface, if catalog size/query complexity outgrows MySQL FULLTEXT (SCL-NFR-02).
- **Externalized cache/session store (Redis)** once moving beyond a single shared-hosting instance, to support true horizontal scale-out (SCL-NFR-01).
- **Read replica(s)** for the transactional database to isolate search/analytics read load from write traffic.
- **CDN-based image transformation** pipeline if S3+basic resize proves insufficient at higher media volume.
- **Commission-based revenue model** alongside subscriptions — contingent on Appendix D Open Decision #8, not assumed.
- **Full customer-facing web storefront** — contingent on Appendix D Open Decision #9 (SRS currently only requires a responsive web buyer/seller panel, not a full customer web marketplace).

None of these are adopted at MVP; each is deferred until the corresponding NFR/SCL trigger is actually observed in production metrics.

---

## 21. Open Decisions Carried Forward (SRS Appendix D — unresolved, not decided here)

1. Exact pricing (EGP) per plan and billing cycle, and founding-importer trial length.
2. Concrete numeric product/inquiry limits behind "Limited" / "Large/unlimited per policy."
3. Whether an indicative price is mandatory or "price on contact" is an acceptable default — affects price-comparison feature depth.
4. Whether phone/WhatsApp is shown in R1 by default or gated further.
5. Which documents are mandatory for the verification badge, and whether unverified suppliers may publish at all.
6. Initial geographic/governorate focus for supplier onboarding.
7. Ownership/governance of the fabric-taxonomy reference list.
8. Whether a transaction commission is planned for R4 alongside subscriptions.
9. Scope of the "web" client — full customer marketplace vs. seller panel + marketing site only.
10. Whether/when a ratings-and-reviews system for suppliers is introduced.

This specification takes no position on any of the above beyond what the SRS states; each is implemented in a way (taxonomy-driven, admin-editable, key/value entitlements) that avoids hardcoding an answer the product owner hasn't yet given.

---

*End of specification. Every FR/NFR ID in SRS §3–§6 is represented in §2/§3 above; every release's Must-priority scope is fully covered by a User Story in §4; no requirement was silently added, removed, reprioritized, or reassigned to a different release.*
