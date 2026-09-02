# THOB — Progress Tracker

آخر تحديث: 2026-09-02 — Core API envelope retrofit سليم، full suite 131 tests passing

## طريقة الاستخدام
بعد كل Phase، حدّث الحالة هنا: `⬜ لسه` / `🔄 شغال عليها` / `✅ خلصت + tests عدّت`.
لو Phase اتقسمت لـ sub-phases (زي Phase 3)، سجّلهم كلهم.

## R1 — MVP

| Phase | الوصف | الحالة | ملاحظات |
|---|---|---|---|
| 0 | Project Setup + Auth Foundation | ✅ | Spec: issue #1. Module `Auth` (OTP request/verify, register, login/logout, OTP password reset, `me`, account-type). Sanctum bearer tokens. `/api/v1/auth/*`. Faked `OtpSender` seam. 43 module tests + full suite green. Stripped leftover multi-tenant POS scaffold. |
| 1 | Business Profile + Verification + Audit Log | ✅ | Spec: issue #3; tickets #4–#9. `Taxonomy` (governorates+fabric_types+materials+colors+units, `GET /api/v1/taxonomy/*` public read, seeders; admin CRUD → Phase 9). `Businesses` (`business_accounts` + profile CRUD `/api/v1/businesses`, `BusinessPolicy`, `verified` flag computed from `verification_status`). `Verification` (`document_types`/`verification_requests`/`verification_documents`, private `verification` disk, owner upload/submit/status/signed download, admin queue/approve/reject with mandatory reason, `VerificationPolicy`). `Admin` audit-log foundation (`audit_logs` append-only, `AuditLog::record()`, model blocks update/delete). Events `VerificationSubmitted/Approved/Rejected` fired, no listeners (Phase 8). Published spatie/permission migration + `admin` role seeder (Phase 0 gap). 46 new tests, full suite 89 green. |
| 2 | Subscriptions & Entitlements | ⬜ | |
| 3.1 | Catalog — Products الأساسية | ⬜ | |
| 3.2 | Catalog — Media | ⬜ | |
| 3.3 | Catalog — Bulk Import + Limits | ⬜ | |
| 4 | Search | ⬜ | |
| 5 | Favorites + Comparison | ⬜ | |
| 6 | Inquiries, RFQ, Quotation, Leads | ⬜ | |
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

## Blockers الحالية
-
