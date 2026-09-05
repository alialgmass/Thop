# THOB — Progress Tracker

آخر تحديث: 2026-09-02 — Phase 2 (Subscriptions) code review 14 patches مطبّقة، full suite 197 tests passing

## طريقة الاستخدام
بعد كل Phase، حدّث الحالة هنا: `⬜ لسه` / `🔄 شغال عليها` / `✅ خلصت + tests عدّت`.
لو Phase اتقسمت لـ sub-phases (زي Phase 3)، سجّلهم كلهم.

## R1 — MVP

| Phase | الوصف | الحالة | ملاحظات |
|---|---|---|---|
| 0 | Project Setup + Auth Foundation | ✅ | Spec: issue #1. Module `Auth` (OTP request/verify, register, login/logout, OTP password reset, `me`, account-type). Sanctum bearer tokens. `/api/v1/auth/*`. Faked `OtpSender` seam. 43 module tests + full suite green. Stripped leftover multi-tenant POS scaffold. |
| 1 | Business Profile + Verification + Audit Log | ✅ | Spec: issue #3; tickets #4–#9. `Taxonomy` (governorates+fabric_types+materials+colors+units, `GET /api/v1/taxonomy/*` public read, seeders; admin CRUD → Phase 9). `Businesses` (`business_accounts` + profile CRUD `/api/v1/businesses`, `BusinessPolicy`, `verified` flag computed from `verification_status`). `Verification` (`document_types`/`verification_requests`/`verification_documents`, private `verification` disk, owner upload/submit/status/signed download, admin queue/approve/reject with mandatory reason, `VerificationPolicy`). `Admin` audit-log foundation (`audit_logs` append-only, `AuditLog::record()`, model blocks update/delete). Events `VerificationSubmitted/Approved/Rejected` fired, no listeners (Phase 8). Published spatie/permission migration + `admin` role seeder (Phase 0 gap). 46 new tests, full suite 89 green. |
| 2 | Subscriptions & Entitlements | ✅ | Spec issue #30. Module `Subscriptions`: plans/entitlements/subscriptions/usage counters + `EntitlementService` (server-side gate) + REST API (`/api/v1/subscription-plans`, `/api/v1/subscriptions` CRUD/usage) + Filament admin resources + scheduled `subscriptions:process-period-ends` command. 64 module tests, full suite 197 green. Code review (14 patches) applied. See Phase 2 section below. |
| 3.1 | Catalog — Products الأساسية | ⬜ | Spec: issue #11. Module `Catalog` — `products`/`product_colors`/`product_price_tiers`، lifecycle، CRUD `/api/v1/products`، `ProductPolicy` ownership، BR-SEL-03 price XOR، MOQ/tiers، duplicate/hide/unavailable/soft-delete. |
| 3.2 | Catalog — Media | ⬜ | Spec: issue #11. `product_media` على disk `public`، MIME/size validation، ≥1 صورة قبل publish، two-phase save (AVL-NFR-03). |
| 3.3 | Catalog — Bulk Import + Limits | ⬜ | Spec: issue #11. XLSX/CSV queued import (row-level partial failure)، `EntitlementService` product_limit server-side (BR-SEL-01)، review queue (US-SEL-11) REST + Filament عبر `DecideProductReview` action مشترك. ملاحظة: مكتبة spreadsheet محتاجة موافقة. |
| 4 | Search | ✅ | Spec: issue #12. Module `Search`: `SearchNormalizer` (AR folds + tashkeel strip + EN synonym map), `ProductSearchService`/`SupplierSearchService` (dual-driver: MySQL FULLTEXT ⇄ SQLite LIKE), `FeaturedRanker` (bounded within-page boost via `EntitlementService` `featured_products`/`featured_supplier` + truthful `featured` flag), `ZeroResultLogger` → `search_logs` (zero-result only). Public `GET /api/v1/products` (search/filter/sort/paginate), `GET /api/v1/products/{id}` (buyer detail, 404 not 403 for non-visible), `GET /api/v1/businesses` (supplier search), `GET /api/v1/businesses/{id}/catalog` (moved from Catalog, now filter/sort-aware). Seller self-list repointed `GET /products` → `GET /products/mine` (+ `/products/mine/{id}`). `Product::scopeBuyerVisible` (BR-SRC-02, excludes suspended-owner). Observers keep `products.search_text` / `business_accounts.search_text` synced. 32 new tests (13 unit normalizer + 14 product search + 5 supplier), full suite 240 green. No new dependency. |
| 5 | Favorites + Comparison | ✅ | Spec: issue #13 (retroactive, filed after implementation). Module `Favorites`: `favorites` polymorphic table (`unique(user_id, favoritable_type, favoritable_id)` = BR-FAV-01)، enum `FavoritableType` (product/supplier — المكان الوحيد لِـ dispatch: modelClass/find/card/morphMap)، morph map non-enforcing (AuditLog يفضل FQCN)، `POST/GET/DELETE /api/v1/favorites` (`firstOrCreate` idempotent + `QueryException` catch للـ race، `?type=` filter، `FavoritePolicy::delete` owner-only → 403). Module `Comparison`: `GET /api/v1/compare?type=&ids=1,2,3,4` بدون جدول (BR-CMP-01: `max:4` → 400 برسالة واضحة، ids المكررة بتتشال)، بيرجّع `items` + `missing_ids` (helper contract زيادة عن السبيك، مقبول)، بيعيد استخدام `ProductDetailResource`/`SupplierCardResource`. `BusinessAccount::scopeActiveAccount` (owner مش suspended) موحّدة عبر Catalog/Search/Comparison. 15 tests، السويت 260 green. مفيش dependency. Comparison + Favorites تحت `auth:sanctum`. ملاحظة: `SupplierCardResource` عايش في Search module (مش Businesses) — عدم اتساق بسيط مع `ProductCardResource` في Catalog، مؤجّل. مفيش toggle-by-target endpoint (DELETE بالـ favorite id فقط، حسب §11.1). |
| 6 | Inquiries, RFQ, Quotation, Leads | 🔄 | Spec: issue #14, split into 3 tickets (send inquiry+leads / RFQ+quotation / reporting). Ticket 1 done: Module `Inquiries` — `inquiries` table (`buyer_id`/`seller_business_id`/`product_id` nullable/`message`/`lead_status`; `lead_status` IS the Lead, no separate leads table per BR-INQ-01). `POST/GET/PATCH /api/v1/inquiries*` (send, list scoped by `?role=buyer|seller` + `?lead_status=`, seller-only status transitions through the 4 fixed values). `EntitlementService` `inquiry_limit`/`inquiry_count` reused (seller side always enforced; buyer side no-ops when the account type's plan doesn't define the key — no plan does yet, Open Decision #2). Rate-limited via `ThrottlesByKey` (Auth module, reused). `InquiryCreated` event fired, no listener (Phase 8). `InquiryPolicy` per §8 matrix. 17 module tests, full suite 277 green. RFQ+Quotation (ticket 2) and abuse Reporting (ticket 3) not started. |
| 7 | Chat (Pusher) | ⬜ | |
| 8 | Notifications | ⬜ | |
| 9 | Admin Dashboard | ⬜ | |
| 10 | R1 Hardening (tests + security + load) | ⬜ | |

## R2 — Retailer + Orders
| Phase | الوصف | الحالة | ملاحظات |
|---|---|---|---|
| 11 | Sub-users, Video, Retailer dual-role, Promotions, Export, Orders | ⬜ | |

## R3 — End Customer
| Phase | الوصف | الحالة | ملاحظات |
|---|---|---|---|
| 12 | Customer accounts, Location, Distance search | ⬜ | |

## R4 — Payments, Shipping, Advanced
| Phase | الوصف | الحالة | ملاحظات |
|---|---|---|---|
| 13 | Payments, Shipping, Feature flags | ⬜ | |

## Known Issues / Open Decisions مؤجلة
(سجّل هنا أي Implementation Assumption اتخدت أو أي حاجة محتاجة قرار من صاحب المنتج)

### قرارات محسومة
- **Open Decision #9 (واجهة الويب) — محسومة 2026-09-01:** واجهة الويب الوحيدة =
  **Filament v5** admin panel على `/admin`. مفيش marketplace web client في الريبو
  ده؛ تطبيق المشتري/البائع عميل منفصل بيستهلك `/api/v1/`. الـ Inertia/Vue scaffold
  بتاع الـ starter kit **legacy** ومقرر يتشال. تفاصيل: `docs/adr/0001-web-ui-is-filament-only.md`،
  `docs/CLAUDE.md` قسم "واجهة الويب"، السبيك §17 + Open Decision #9.

### Phase 0
- **OTP = 6 أرقام**؛ صلاحية 5 دقايق (SEC-NFR-02)؛ قفل بعد 3 محاولات غلط. throttle: 3 طلبات OTP/دقيقة للرقم، 5 تحقق/دقيقة، 5 محاولات login/دقيقة (phone+IP). كلها في `Modules/Auth/config/otp.php` — الأرقام مش محددة في السبيك (Implementation Assumption).
- **`users.status`**: `pending_type_selection | active | suspended`. اكتمال بروفايل الشركة هيتتبع لاحقًا عبر verification status مش عبر العمود ده.
- **صيغ الهاتف المقبولة**: `01XXXXXXXXX` / `201XXXXXXXXX` / `+201XXXXXXXXX` (010/011/012/015) → تتخزن كـ `+20…`.
- **تعارض سبيك/كود**: الـ starter kit كان multi-tenant POS (Fortify + Inertia + name/email). اتشال بالكامل (web auth kit + tests بتاعته) لصالح schema THOB (phone-first). Fortify + Sanctum فاضلين للـ API. جدول `users` الأساسي اتعدّل مش اتعمله ALTER migrations.
- **DB**: التطوير/الاختبار على SQLite حاليًا؛ السبيك بيطلب MySQL 8 — التبديل مؤجّل (المايجريشنز متوافقة مع MySQL).
- `JsonResource::withoutWrapping()` مفعّل عالميًا من `AuthServiceProvider` — مفيش `data` wrapper في ردود الـ API.
- `POST /api/v1/auth/password/reset` بيلغي كل tokens الـ user (رغم إن السبيك مقالتش) — قرار أمان تحت قاعدة 3 في docs/CLAUDE.md.
- `GET /api/v1/auth/account-types` بيرجّع الأنواع الأربعة بـ label/description ثنائي اللغة (US-ACC-02). الترجمة بتتبع locale التطبيق. `Accept-Language` على `/api/v1/` بيتطبّق دلوقتي عبر Core `api.language` middleware (قرار Phase 1 retrofit — كان مؤجّل).
- `OTP_DRIVER` env يحدد الـ OtpSender (`log` حاليًا) — إضافة مزود SMS = key جديد في `AuthServiceProvider::OTP_DRIVERS`.

### Phase 1 (من السبيك — issue #3)
- **Taxonomy مسحوبة لقدام** من Phase 3: الجداول + seeders + endpoints للقراءة بس. Admin CRUD (US-ADM-03) لسه Phase 9.
- **Open Decision #5** (المستندات الإلزامية / هل غير الموثّق ينشر): محلولة بنيويًا بس — `document_types.is_required` عمود، seeded `true` للسجل التجاري + البطاقة الضريبية، قابل للتغيير من غير deploy. مفيش موقف من قفل النشر.
- **Open Decision #7** (حوكمة taxonomy الأقمشة): seed lists مبدئية مصغّرة ومعلَّمة.
- **الإشعارات**: events (`VerificationSubmitted/Approved/Rejected`) بتتطلق من غير listeners — Phase 8 بيربط.
- **`onboarded_by_admin`** عمود على `business_accounts` من دلوقتي (تفادي migration في Phase 9)، assisted onboarding نفسه Phase 9.
- **audit_logs**: append-only بالبناء (مسار كتابة واحد `AuditLog::record()`)؛ حارس DB-level (trigger/revoked grants) خيار Phase 10.
- **verification disk**: `Storage::fake()` هو الـ seam في التيستات — مفيش storage contract مخصص. bucket SSE إعداد deployment مش كود. disk `verification` جديد في `config/filesystems.php` (`visibility: private`، `local` driver افتراضيًا، S3 في production عبر `VERIFICATION_DISK_DRIVER`).
- **تنزيل المستند**: بيعمل stream للملف مباشرة (`Storage::download`) — `temporaryUrl` متاح على S3 بس؛ الـ endpoint نفسه policy-gated فمفيش URL عام.
- **صيغة `contact_channels`**: مصفوفة من `{type, value}` (JSON عمود، Implementation Assumption — السبيك مقالتش الشكل بالظبط).
- **تعارض Phase 0**: `spatie/laravel-permission` migration ماكانتش published أصلًا رغم `HasRoles` + Filament Roles resource — published دلوقتي + `admin` role seeder في `Modules/Admin`. و `database/seeders/DatabaseSeeder.php` كان لسه بيشاور على `Modules\Tenancy` المحذوف — اتكتب من جديد (roles + taxonomy + document types).
- **`business_accounts.user_id`** unique (ملف واحد لكل مستخدم). محاولة تانية → 422 مش 403 (business rule مش authorization).
- علاقات `verificationRequests()` / `latestVerificationRequest()` على `BusinessAccount` (Businesses بيعرف Verification — coupling مقبول تحت §9.4).

### Phase 1 — code-review fixes (applied)
- **تنزيل المستند بقى signed + time-limited فعليًا**: `download_url` في الـ resource = `URL::temporarySignedRoute` (TTL من `verification.download_link_ttl_seconds`)، والـ route عليه `signed` middleware + policy check (defense in depth). §12/§15.
- **رفع الملف**: تحقق `mimetypes:` (MIME مسنيّف) بالإضافة لـ `mimes:` (امتداد) — ملف متغيّر الامتداد بيترفض. SEC-NFR-05.
- **approve/reject**: حارس حالة — طلب مش `pending` أو مش submitted → 409 (US-ADM-01 "Given a pending request"). العملية كلها في `DB::transaction`.
- **audit action** = enum `Modules\Admin\Enums\AuditAction` بدل string literals.
- **taxonomy + document_types**: عمود `is_active` (spec §10.2)، endpoints بتفلتر بيه (`scopeActive`).
- **authz موحّد**: `App\Http\Concerns\RendersApiErrors` + `ResolvesRequestUser` مشتركين؛ `RendersApiErrors` اتشال من `Modules/Auth` واتنقل لـ `app/`. `VerificationPolicy` بتتحقن (constructor injection) مش `Gate::policy` (أبيليتيها بتمتد عبر 3 models).
- **dedup**: `BusinessProfileRules` trait للـ Store/Update requests؛ `TaxonomyController` data-driven.

### Phase 1 — Filament admin (verification review فقط، مقدَّم من Phase 9)
- `Modules/Verification/Filament/Resources/VerificationRequests/*` على بانل `/admin` الموجود. List (فلتر status، badge بعدد الـ pending) + View (infolist: بيانات الشركة + الطلب + المستندات) + هيدر أكشنز Approve / Reject (modal reason مطلوب)، ظاهرين بس طول ما الطلب pending.
- منطق القرار اتنقل لـ `Modules\Verification\Actions\DecideVerificationRequest` — مشترك بين REST controller والـ Filament page (نفس الـ state guard + audit log + events). استثناء `VerificationNotPendingException` → 409 في الـ API، notification في البانل.
- تنزيل المستند في البانل: route `admin.verification.documents.download` خلف `web`+`auth`+`RedirectIfNotAdmin` (session مش bearer). الـ API لسه signed URL.
- `RedirectIfNotAdmin` كان بيتحقق من role `'Admin'` (كابيتال) — اتظبط لـ `'admin'` عشان يطابق `RoleSeeder` وباقي الكود.
- تسجيل موديول الـ Filament: `discoverResources` تاني في `AdminPanelProvider`.
- 6 tests (Livewire): non-admin 403، list، approve، reject + reason validation، actions hidden بعد الحسم. الإجمالي 98 green.
- الباقي من Phase 9 (taxonomy CRUD, plans, featured, liquidity, ban, reports, onboarding) لسه ⬜.

### Phase 1 — Core API envelope retrofit (applied 2026-09-02)
- **انvelope موحّد على كل `/api/v1/`** من `Modules/Core`: `{ custom_code, status, message, body, info }` (`ApiResponse` trait + `ApiException` family + `Modules\Core\Exceptions\Handler`). `status` = `true` لأي 2xx. `custom_code` registry موثّق في السبيك §11. ترقيم codes: 2000 success، 4000 validation، 4001 unauthenticated، 4221/4222/4224 (OTP/login/handoff)، 4091 registered، 4092 account-type/not-pending، 4290/4291 throttle، 5031 OTP delivery.
- **Validation → HTTP 400** (مش 422) في كل الموديولات؛ كل الـ requests على Core `BaseRequest`؛ 403 policy و-brand 429 بيلزموا defaults بتاعة Laravel.
- **`App\Http\Concerns\RendersApiErrors` + `ResolvesRequestUser` اتحذفوا** — استُبدلوا بـ Core (`$request->user()`). الموديولات المهاجَرة: Auth (46)، Businesses (11)، Verification (29)، Taxonomy (5). كلها تتكلم باسم `apiBody()`/`ExceptionResponse` بدل الـ concatenated JSON.
- **`created_by`/`updated_by`** (nullable FK → `users.id`) على `business_accounts`/taxonomy terms/`verification_requests`+documents/`document_types` عبر Core traits `HasCreatedByColumn`/`HasUpdatedByColumn` (boot listeners = `user('id')`). `AuditLog::actor()` → `Modules\Admin\Models\Admin` (Admin extends User، `record()` لسه بياخد `App\Models\User`).
- **`api.language` middleware** (Core `AppLanguage`، alias من `CoreServiceProvider`) اتضاف على api route groups بتاعة كل الموديولات الأربعة.
- **ملحوظة pagination**: nested `JsonResource` collection بتتسلسل مسطّحة من غير `data`؛ الـ queue paginated بيتبني صراحة عبر `toResponse($request)->getData(true)`.
- Tests: Core envelope/2xx، actor-columns (BusinessProfileTest)، full suite **131 tests / 494 assertions green**.

### Phase 2 — Subscriptions & Entitlements (applied 2026-09-02)
- **Module `Modules/Subscriptions/`** جديد — جداول `subscription_plans`, `subscription_entitlements`, `subscriptions`, `subscription_usage_counters` (migration `2026_09_02_000008`). `subscriptions.notes` JSON. Enums `SubscriptionStatus` (Active/Expired/Cancelled/Restricted) و `BillingCycle` (Monthly/Annual).
- **Models**: `SubscriptionPlan`, `SubscriptionEntitlement` (`$timestamps=false`), `Subscription` (`isExpired()` بـ trial-end, `isTrial()`, `markExpired()`), `SubscriptionUsageCounter` (`CREATED_AT`/`UPDATED_AT=null`). علاقة `subscription()` HasOne عبر `latestOfMany` على `BusinessAccount`.
- **`EntitlementService`** — نقطة الفحص الوحيدة server-side (BR-SUB-01): `can()`, `get()`, `incrementUsage()` (firstOrCreate+increment لـ SQLite), `decrementUsage()`, `currentUsage()`, `getActiveSubscription()`.
- **API**: `SubscriptionController` — `plans()`, `store()`, `show()`, `usage()`, `update()` (Upgrade فوري بيرجّع subscription جديد؛ Downgrade/Cancel بيخزّن `notes` وبيرجّع الحالي — BR-SUB-02). Form requests `SubscribeRequest`/`UpdateSubscriptionRequest`. `SubscriptionResource`/`SubscriptionPlanResource` (API) + `SubscriptionPolicy` (owner **أو** admin).
- **Seeder/Factories**: `SubscriptionPlanSeeder` (Importer Basic/Pro/Premium + Wholesaler + Retailer بـ entitlement من Section 6، بدون أسعار — `price` nullable). `SubscriptionPlanFactory` (states importer/wholesaler/retailer)، `SubscriptionFactory` (active/expired/cancelled/trial). `DatabaseSeeder` اتعمّد بـ seeder.
- **Filament admin** على `/admin`: `SubscriptionPlanResource` (CRUD + Repeater entitlements) و `SubscriptionResource` (read-only `canCreate/canEdit/canDelete=false`; `ViewSubscription` بأكشنز Grant Trial/Promo, Extend Period, Cancel). تسجيل عبر `discoverResources` في `AdminPanelProvider` + `@source` في `theme.css`.
- **Policy fix**: `SubscriptionPolicy` view/update/delete كانت owner-only فتكسر الـ panel (403) — اتضاف `|| $user->hasRole('admin')` (نمط `VerificationPolicy::viewStatus`).
- **Filament component fix**: `TextInput::decimalDigits()` مش موجودة في Filament v5 — اتشالت (استُبدلت بـ `step(0.01)`).
- Tests: 13 ملفات — `SubscriptionPlanTest`, `SubscribeTest`, `UpgradeSubscriptionTest`, `DowngradeSubscriptionTest`, `EntitlementServiceTest`, `SubscriptionExpiryTest`, `ClientTamperingTest`, `TrialPromoTest`, `SubscriptionPlanPanelTest`, `SubscriptionPanelTest`, `PeriodEndCommandTest`, `SubscriptionShowTest` (جديدة). Module filter=64 tests، السُويت الكامل **197 tests / 643 assertions green**.
- **Code review (bmad-code-review, full mode)**: 14 patches مطبّقة — P1 `scopeActiveForBusiness` بيفلتر فترة/trial المنتهية؛ P2 أمر مجدول `subscriptions:process-period-ends` بيطبّق downgrade/cancel عند نهاية الفترة ويمرّر expired→Restricted؛ P3 دمج `notes` بدون clobber؛ P4 رفض subscribe لـ plan من `account_type` مختلف (4222 `plan_type_mismatch`)؛ P5 حذف scaffold ميّت؛ P6/P7 authorize على `usage`/`show`/`viewAny`؛ P8 status guard في resource؛ P9 سقوف رقمية في seeder؛ P10 إضافة `GET /subscriptions/{id}` (`show`)؛ P11 `decrementUsage` عداد guard؛ P12 `grantTrial` visible فقط لما غير active + null-guard entitlements؛ P13 `$fillable` بدل `$guarded`؛ P14 `SubscriptionPlanResource`. مؤجّل (موثّق): product-hiding عند expiry (BR-SUB-03، Catalog لاحقًا)، `account_type` enum علىـ plan، races/concurrency، rate limiting، FK on-delete. مرفوض بموافقة المستخدم: plans endpoint خاص (D4)، proration (D2).

### Phase 4 — Search (issue #12, applied 2026-09-04)
- **`GET /api/v1/products` repointed** لبحث عام (Search module، غير محمي عبر `optional.sanctum` alias جديد بيحل مستخدم Sanctum لو فيه توكن). قائمة البايع (كل الحالات) اتنقلت لـ `GET /api/v1/products/mine` + `GET /api/v1/products/mine/{id}`. `ProductController@index` بقى يرجّع `.toResponse()->getData(true)` زي `queue()`.
- **`GET /api/v1/businesses/{id}/catalog`** اتنقل من Catalog لـ Search؛ بقى بياخد نفس عقد الفلاتر/الترتيب/الـ pagination. `ProductController@publicCatalog` اتشال.
- **`sort=supplier_rating`** بيتدهور لـ "الموثّق الأول ثم الأحدث" — مفيش نظام تقييم موردين في R1 (Implementation Assumption).
- **فلتر "specialty" للموردين** = عمود `business_accounts.activity` النصّي الحر عبر عمود `search_text` منرمَل — مفيش taxonomy تخصصات (Implementation Assumption).
- **Zero-result logging** بيتسجّل فقط عند نتيجة صفر بالظبط (عتبة "low-result" في السبيك اتسابت 0 للـ phase دي). فشل التسجيل مبيكسرش الرد.
- **Featured boost** = تعديل موضعي محدود: العنصر المميز بيطلع لفوق بحد أقصى `FeaturedRanker::BOOST_POSITIONS` (=12) مركز داخل الصفحة، مبيشيلش عنصر عادي ومبيقفزش مسافة عشوائية. وزن ثابت في الكود (مش admin-editable لسه). مبني على مفاتيح `featured_products` / `featured_supplier` البوليانية فقط — `search_priority` المتدرّج مش موصّل (مش موجود في seeder). مطبّق على sort `relevance`/`newest` بس (مش على ترتيب السعر).
- **`GET /api/v1/businesses` + `/businesses/{id}/catalog`** اتسجّلوا في Search module (مش Businesses) عشان كل رو/تات البحث تفضل في مكان واحد؛ السبيك اقترح Businesses module بس ده انحراف مقصود موثّق (Search بيملك الـ controller والـ service).
- **Open Decision #5**: منتجات البيزنس غير الموثّق بتظهر في البحث (تتميّز فقط بغياب البادج). `Product::scopeBuyerVisible` مكتوبة عشان قلبها لـ "الموثّق فقط" يبقى سطر واحد.
- **عمود `search_text`** على `products` و`business_accounts` (منرمَل)، بيتزامن عبر observers في Search module. مايجريشن Phase 4 بيبدّل MySQL FULLTEXT من `(name_ar,name_en)` لـ `search_text` وبيضيف FULLTEXT على `business_accounts`؛ كله no-op على SQLite.
- **اختبار FULLTEXT الحقيقي + هدف p95<2s عند 100k منتج (PRF-NFR-01)**: فحص MySQL يدوي/CI مؤجّل — مش تيست SQLite، تماشيًا مع سياسة "المايجريشنز MySQL-compatible، التيستات على SQLite".
- **إصلاحات Phase 3 عرضية**: `ProductFactory` أضيف `draft()` state و`name_ar` بقى unique رقمي (كان `fake()->unique()->word()` بيخلص pool)؛ `ProductMediaResource` كان ناقص `use Storage`.

## Blockers الحالية
-
