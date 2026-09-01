# THOB — Claude Code Session Prompts (Phase by Phase)

**كيفية الاستخدام:**
1. حط هذا الملف + `THOB_Implementation_Specification.md` في جذر الـ repo.
2. كل Phase = session/تاسك منفصل لـ Claude Code.
3. انسخ البرومبت بتاع الـ Phase وابعته كما هو (أو عدّل فيه لو محتاج).
4. متبدأش Phase جديدة قبل ما تراجع وتعمل test للي قبلها.
5. كل برومبت بيقول لكلود يقرا السبيك الأول — ده أهم سطر في كل برومبت، متشيلوش.

---

## Phase 0 — Project Setup + Auth Foundation

```
اقرأ ملف THOB_Implementation_Specification.md كامل قبل ما تبدأ، وركز على:
- Section 9 (Laravel Modular Monolith Architecture) للبنية العامة
- Section 4.1 (Registration & Verification stories: US-ACC-01, US-ACC-02, US-ACC-07)
- Section 10.1 (Database: users, business_accounts)
- Section 7 (Validation Catalogue) للحقول الخاصة بـ users

المطلوب في هذا الـ Phase:
1. Setup مشروع Laravel جديد بالـ structure الموديولية الموضحة في Section 9.1 
   (استخدم فقط الفولدرات المذكورة في 9.2 — متعملش abstractions زيادة).
2. Module: Auth
   - Migration + Model لـ users (الحقول: phone unique, email nullable unique, 
     password_hash, account_type enum, language, status)
   - OTP request/verify (US-ACC-01): endpoint لإرسال OTP، تخزينه hashed، 
     expiry 5 دقايق، rate limiting على المحاولات (لا تسجل الـ OTP نفسه في اللوج أبدًا)
   - Register + Login + Logout + session (US-ACC-02, US-ACC-07)
   - Account-type selection endpoint (importer/wholesaler/retailer/customer)
3. اكتب Feature Tests تغطي: OTP expiry، rate limit بعد محاولات غلط، 
   تسجيل رقم مكرر، اختيار نوع الحساب.
4. متلمسش أي Business logic تانية غير المذكورة هنا — باقي الموديولز هتيجي في Phases تانية.

في الآخر اديني ملخص بالملفات اللي اتعملت والـ tests اللي عدّت.
```

---

## Phase 1 — Business Profile + Verification + Audit Log

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.1: US-ACC-03, US-ACC-04, US-ACC-05
- Section 4.10: US-ADM-01, US-ADM-09 (audit log)
- Section 10.1 (business_accounts, verification_requests, verification_documents, audit_logs)
- Section 12 (S3 Media Architecture) — خاص بتخزين مستندات التوثيق PRIVATE
- Section 15 (Security Architecture) بند verification documents
- Section 8 (Authorization Matrix) لصلاحيات كل Actor على Business Profile / Verification

المطلوب:
1. Module: Businesses — business_accounts table + CRUD لملف الشركة (US-ACC-03)
   ملحوظة: governorate_id هو Implementation Assumption (مرجع من taxonomy) — نفّذه كذلك.
2. Module: Verification
   - رفع مستندات التوثيق (US-ACC-04): validation للـ MIME/size، تخزين على 
     S3 disk خاص PRIVATE، مفيش URL عام يتم تخمينه أبدًا.
   - Admin review flow: approve/reject مع سبب (US-ADM-01)
   - Badge "موثّق" يظهر تلقائيًا لما verification_status = verified (US-ACC-05)
3. Module: Admin (الأساس بس) — audit_logs table، append-only، 
   كل action إداري لازم يتسجل فيها (US-ADM-09) — ممنوع أي endpoint 
   يعمل update/delete على الجدول ده.
4. Policies: BusinessPolicy, VerificationPolicy — طبّق Authorization Matrix 
   بتاعة Section 8 بالظبط (Owner فقط يقدر يعدّل بروفايله، Admin بس يقدر يوثّق).
5. Tests: رفع مستند صالح/غير صالح، محاولة وصول لمستند شخص تاني (لازم 403)، 
   admin approve/reject بيسجل في audit log.

اديني ملخص في الآخر.
```

---

## Phase 2 — Subscriptions & Entitlements

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.6 (كل الـ US-SUB stories في R1)
- Section 6 (Subscription/Entitlement Catalogue — الخطط والصلاحيات زي ما هي 
  من الـ SRS Appendix A، ملحوظة: الأسعار "not specified in source document" 
  — متخترعش أرقام)
- Section 10.6 (subscription_plans, subscription_entitlements, subscriptions, 
  subscription_usage_counters)
- Section 11 (Entitlement Architecture من البرومبت الأصلي) — 
  الفحص لازم يبقى server-side بالكامل، ممنوع نثق في أي بيانات جاية من الكلاينت
- Business Rules: BR-SUB-01, BR-SUB-02, BR-SUB-03 (Section 5)

المطلوب:
1. Module: Subscriptions
   - plans + entitlements كـ key/value (عشان الأدمن يقدر يعدّل من غير deploy)
   - EntitlementService: `$business->subscription()->can('capability_key')`
   - Upgrade فوري / Downgrade-Cancel في آخر المدة المدفوعة (US-SUB-05)
   - انتهاء الاشتراك → حالة مقيدة (products تتخفي مش تتمسح) (US-SUB-08)
   - Trial + منح ترويجية من الأدمن (US-SUB-07)
2. Seed: أضف plans الاستيراد (Basic/Pro/Premium) بالصلاحيات المذكورة في 
   Section 6 بالظبط، من غير أسعار (اجعل price nullable).
3. Tests: محاولة تجاوز حد المنتجات (لسه مفيش Catalog، اعمل mock/stub بسيط 
   يستخدم EntitlementService)، upgrade فوري، downgrade مايقصّش المدة المدفوعة، 
   انتهاء الاشتراك يغيّر الحالة صح، ومحاولة تزوير plan claim من الكلاينت 
   لازم تترفض.

اديني ملخص.
```

---

## Phase 3 — Catalog (Products + Media + Bulk Import)

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.2 كاملة (US-SEL-01 لغاية US-SEL-11)
- Section 10.2 (Taxonomy) و 10.3 (products, product_colors, product_price_tiers, 
  product_media)
- Section 7 (Validation Catalogue) لحقول المنتج
- Section 12 (S3 Media Architecture) — الصور PUBLIC عبر CDN
- Business Rules: BR-SEL-01 لغاية BR-SEL-04

المطلوب (يفضّل تقسيمها لـ 3 sessions فرعية لو حسيت الحجم كبير):

3.1 Products الأساسية:
   - Taxonomy tables (fabric_types, materials, colors, units, governorates) + seed
   - Product model + CRUD (US-SEL-01, US-SEL-02, US-SEL-05)
   - القاعدة: exactly one of {price, price_on_contact} (BR-SEL-03)
   - status lifecycle: draft/pending_review/published/hidden/unavailable/rejected
   - MOQ + price tiers (US-SEL-06)
   - Edit/duplicate/hide/delete (soft delete) + mark unavailable (US-SEL-07, US-SEL-08)
   - ownership check صارم: بايع مش هو صاحب المنتج ياخد 403

3.2 Media:
   - رفع صورة/أكتر لكل منتج (US-SEL-03) — S3 public disk
   - على الأقل صورة واحدة مطلوبة قبل النشر
   - Product لازم يتحفظ حتى لو رفع الصورة فشل (AVL-NFR-03 — لا نفقد بيانات 
     المنتج بسبب فشل رفع صورة)

3.3 Bulk Import + Limit Enforcement:
   - Bulk upload عبر template XLSX/CSV (US-SEL-09) — queued Job، 
     كل صف يتحقق لوحده، صف غلط ميوقفش باقي الملف
   - ربط مع EntitlementService من Phase 2: فرض حد عدد المنتجات (SEL-FR-10) 
     server-side فقط
   - Review queue (US-SEL-11): منتج جديد/معدّل يدخل pending_review قبل الظهور

Tests لكل جزء منفصلة: منتج من غير صورة يترفض publish، تجاوز حد الخطة يترفض، 
bulk import مع صف غلط بيكمل باقي الصفوف، محاولة تعديل منتج شخص تاني = 403.

اديني ملخص.
```

---

## Phase 4 — Search

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.3 (US-SRC-01 لغاية US-SRC-11)
- Section 13 (Search Architecture) — MySQL FULLTEXT فقط، ممنوع أي search engine خارجي
- Business Rules: BR-SRC-01, BR-SRC-02

المطلوب:
1. Module: Search
   - FULLTEXT index على اسم المنتج (عربي/إنجليزي) + composite indexes للفلاتر 
     (fabric_type, color, governorate, price)
   - Filter بكل الحقول المذكورة في SRC-FR-02، Sort (الأنسب/السعر/الأحدث/تقييم المورد)
   - Pagination إلزامي على أي نتيجة collection
   - Featured ranking boost مع label واضح "Featured" (BR-SRC-01)
   - Zero-result logging (search_logs table) لسكشن الأدمن لاحقًا (US-SRC-11)
2. Supplier search منفصل (US-SRC-07) — فلترة بالمحافظة/التخصص/التوثيق
3. Product detail endpoint (US-SRC-05) + navigate to supplier catalog (US-SRC-06)

Tests: بحث بكلمة فيها اختلاف إملائي شائع يرجّع نتائج معقولة، فلاتر متعددة 
= تقاطع صحيح، منتج pending_review أو hidden أو deleted ميظهرش في النتائج 
أبدًا (BR-SRC-02)، فحص الأداء بالـ index على بيانات تجريبية كبيرة نسبيًا.

اديني ملخص.
```

---

## Phase 5 — Favorites + Comparison

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.3: US-SRC-08, US-SRC-09
- Section 4.8: US-BUY-02, US-BUY-05
- Section 10.4 (favorites polymorphic table)
- Business Rules: BR-FAV-01, BR-CMP-01

المطلوب:
1. Favorites: toggle/list لمنتجات وموردين، polymorphic (favoritable_type/id)، 
   unique constraint يمنع التكرار (BR-FAV-01)
2. Comparison: endpoint يقارن حتى 4 عناصر (منتجات أو موردين) side-by-side، 
   يتحسب on-demand من غير جدول تخزين منفصل (زي ما موضّح في Section 10.4)، 
   محاولة إضافة عنصر خامس تترفض بوضوح

Tests: تكرار favorite لنفس العنصر ميعملش صف جديد، مقارنة بـ 5 عناصر ترفض، 
إزالة favorite لشخص تاني = 403.

اديني ملخص.
```

---

## Phase 6 — Inquiries, RFQ, Quotation, Leads

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.4 كاملة (US-INQ-01 لغاية US-INQ-09)
- Section 10.5 (inquiries, rfqs, quotations)
- Section 36 (من البرومبت الأصلي — Leads) و Section 4.7 (US-ANL-03)
- Business Rules: BR-INQ-01, BR-INQ-02

هذا الجزء هو قلب الـ MVP، خد وقتك فيه.

المطلوب:
1. Inquiry: إرسال استفسار من صفحة منتج/مورد (US-INQ-01)، كل inquiry = 
   Lead واحد تلقائيًا (BR-INQ-01)، lead_status: new/in_progress/done/not_completed
2. RFQ: طلب سعر منظم (منتج، كمية، لون، تاريخ الاحتياج) (US-INQ-02)
   - لو الكمية أقل من MOQ: تحذير مش منع (Implementation Assumption موضّحة في السبيك)
3. Quotation: رد البائع بسعر + توافر + مدة صلاحية (US-INQ-03)، 
   العرض المنتهي الصلاحية يظهر كـ "منتهي" مش قابل للتفعيل
4. فرض حدود الخطة على الاستفسارات المرسلة/المستقبلة (US-INQ-08) عبر 
   EntitlementService من Phase 2
5. Rate limiting + reporting للرسائل المسيئة (US-INQ-09) — 429 بعد حد معين، 
   endpoint للإبلاغ ينشئ تذكرة أدمن
6. Lead management screen (US-ANL-03): كل الاستفسارات بحالتها وآخر نشاط

Tests: إرسال استفسار خارج حدود الخطة يترفض برسالة واضحة (مش بيختفي بصمت)، 
quotation منتهي الصلاحية يُعرض كذلك، محاولة رد على RFQ مش موجّه للبائع = 403، 
rate limit يشتغل صح.

اديني ملخص.
```

---

## Phase 7 — Chat (Pusher)

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.5 كاملة (US-CHT-01 لغاية US-CHT-09) — هذا القسم فيه تفاصيل 
  دقيقة عن Pusher architecture، اقرأه بعناية
- Section 17/18 من البرومبت الأصلي (منطق Pusher: مش Source of Truth، 
  MySQL هو المصدر)

المطلوب:
1. Module: Chat
   - Conversation مربوطة بـ Inquiry واحد (idempotent creation) (US-CHT-01)
   - Conversation authorization: هذا أهم جزء — الـ broadcasting/auth endpoint 
     لازم يستخدم نفس الـ Policy اللي بتستخدمها الـ REST endpoints، ممنوع 
     تكرار منطق التفويض في مكانين (US-CHT-02)
   - Send message: validate → persist في MySQL أولاً → broadcast عبر Pusher 
     بعدين. لو الـ broadcast فشل، الرسالة تفضل محفوظة وقابلة للاسترجاع 
     عبر الـ API (US-CHT-03, US-CHT-05) — هذا شرط أساسي، اختبره صراحة
   - Load history + pagination (US-CHT-06)
   - Read/unread state + unread count (US-CHT-07)
   - Rate limiting (يشارك نفس منطق Phase 6) (US-CHT-08)
   - Report message → admin ticket (US-CHT-09)
2. Pusher channels: `conversation.{id}` private channels

Tests: شخص مش طرف في المحادثة يترفض من channel auth (403)، إرسال رسالة 
مع محاكاة فشل Pusher لازم الرسالة تتحفظ وتترجع من الـ API عادي، 
unread count يتحدّث صح.

اديني ملخص.
```

---

## Phase 8 — Notifications

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.9 (US-NOT-01 لغاية US-NOT-04)
- Section 14 (Notification Matrix) — القائمة الكاملة للأحداث والقنوات
- تأكد إنك وصّلت الأحداث دي بكل الموديولز اللي اتعملت في الـ Phases اللي فاتت 
  (verification, product review, inquiry, rfq, quotation, message, subscription expiry)

المطلوب:
1. استخدم Laravel Notifications built-in system (Section 10.8)
2. نفّذ كل event من Notification Matrix في Section 14، وربطه بالمكان الصح 
   في كل Module (event listener بعد كل action ذات صلة)
3. تفضيلات الإشعارات لكل فئة (US-NOT-02)
4. تفريق واضح بين operational notifications (SMS/email إجباري للأحداث 
   المالية/الحسابية - US-NOT-03) و marketing notifications (opt-in فقط - US-NOT-04)

Tests: تعطيل تفضيل فئة معينة يمنع push بس مش SMS للأحداث المالية، 
كل event في الجدول فعليًا بيطلق notification لما يحصل trigger بتاعه.

اديني ملخص.
```

---

## Phase 9 — Admin Dashboard (يمكن تشتغل عليها Parallel)

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.10 كاملة (US-ADM-01 لغاية US-ADM-10)
- Section 11 (Admin endpoints في الـ API catalog)
- Business Rule BR-ADM-01

المطلوب:
1. Taxonomy management (fabric types/materials/colors/units) من غير deploy (US-ADM-03)
2. Subscription plans management (US-ADM-04)
3. Featured curation: موردين/منتجات/بانرات (US-ADM-05)
4. Liquidity dashboard: active sellers/products/buyers, weekly inquiries, 
   zero-result searches (US-ADM-06) — استخدم search_logs من Phase 4 
   و analytics_events لو موجودة
5. Suspend/ban account (destructive action، يحتاج تأكيد صريح) (US-ADM-07)
6. Reports/disputes ticket queue (US-ADM-08)
7. Assisted onboarding — أدمن يسجّل مورد بالنيابة (US-ADM-10)
8. تأكد كل الـ actions دي بتتسجل في audit_logs (BR-ADM-01) — لو حاجة مش 
   بتتسجل، دي مشكلة لازم تتصلح

Tests: كل admin action يعمل audit log entry، suspend account يمنع الوصول فورًا، 
non-admin يحاول يوصل لأي admin endpoint = 403.

اديني ملخص.
```

---

## Phase 10 — R1 Hardening (قبل الإطلاق)

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 16 (Testing Strategy) كاملة
- Section 15 (Security Architecture Checklist) كاملة
- Section 8 (Authorization Matrix) — راجع كل صف فيها
- Section 7.1 من الـ SRS الأصلي (MVP Acceptance Criteria — A1 لغاية A7)
- Section 3 (NFR matrix) بند PRF-NFR-01, PRF-NFR-02, PRF-NFR-04

المطلوب:
1. Authorization test suite شاملة: لكل Policy في المشروع، اختبر كل صف من 
   Authorization Matrix (Section 8) — كل actor مع كل resource
2. Security checklist: راجع كل بند في Section 15 وتأكد إنه متطبق فعليًا 
   (مش بس موثّق) — خصوصًا: عدم الثقة بأي بيانات كلاينت، rate limiting، 
   input validation، secured verification documents
3. Load test: 500 concurrent users من غير تدهور (NFR-PRF-04)
4. Performance test: نتائج البحث في أقل من ثانيتين عند 95th percentile 
   على كتالوج يوصل 100,000 منتج (PRF-NFR-01)
5. راجع الـ 7 معايير قبول MVP من السبيك الأصلي (A1-A7) واحد واحد وأكّد 
   إنها كلها شغالة end-to-end
6. اديني تقرير نهائي: أي حاجة ناقصة أو fail، مع رقم الـ requirement بتاعها

هذا الـ Phase مفيهوش features جديدة — بس تحقق وتقوية لكل حاجة اتعملت قبل كده.
```

---

## Phase 11+ — R2 (Retailer + Orders)

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.9 كاملة (كل R2 stories: US-ACC-08, US-ACC-10, US-SEL-04, 
  US-SEL-12, US-SEL-13, US-SUB-09, US-ANL-05, US-SI-06, وكل US-ORD stories)
- Section 10.7 (orders, order_items)
- Business Rules: BR-ORD-01, BR-ORD-02

المطلوب (قسّمها بنفس منطق R1 لو حجمها كبير):
1. Sub-users بصلاحيات محدودة (US-ACC-08)
2. Product video (US-SEL-04)، custom product groups (US-SEL-13)
3. Retailer dual-role: profile للمحل + بيع للعملاء + شراء من الموردين (US-SEL-12)
4. Standalone promotional products منفصلة عن الخطة (US-SUB-09)
5. XLSX/CSV export بنفس template الـ import (US-SI-06)
6. Orders module كامل: state machine صارم (created → seller_confirmed → 
   preparing → shipped/delivered → completed, مع cancelled/rejected كحالات 
   نهائية) (BR-ORD-01) — ممنوع أي قفزة بين حالات غير مسموح بيها
   - سلة بمنتجات من أكتر من بائع تتقسّم تلقائيًا لأوردرات منفصلة (Implementation 
     Assumption موضّحة في السبيك)
   - Inventory deduction اختياري للبائع (US-ORD-05)
   - Preliminary quotation document (US-ORD-06)

Tests: محاولة قفزة حالة غير مسموح بيها في الـ order ترفض، سلة multi-seller 
تتقسم صح، partial confirmation يحدّث كل بند لوحده.

اديني ملخص.
```

---

## Phase 12+ — R3 (End Customer)

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.10 كاملة (US-ACC-06, US-SRC-12, US-SRC-13, US-LOC-01, 
  US-INQ-10, US-SUB-10)
- Section 31 من البرومبت الأصلي (Location) — permission تُطلب وقت 
  استخدام الميزة بس، مش من أول تشغيل التطبيق

المطلوب:
1. Customer registration: verification بالتليفون بس، من غير أي مستندات (US-ACC-06)
2. Location permission flow: يُطلب فقط لما العميل يستخدم ميزة "أقرب محل" 
   (HW-FR-02, BR-LOC-01) — لو رفض الإذن، البحث يشتغل من غير ترتيب بالمسافة 
   بدل ما يترفض كامل (Implementation Assumption)
3. Customer search: نوع + لون + خامة + سعر + متاح + أقرب محل، مرتب بالمسافة 
   (US-SRC-12, US-SRC-13)
4. Retailer يستقبل استفسارات من العملاء (US-INQ-10) — بيستخدم نفس Inquiry 
   model بتاعة Phase 6
5. تأكيد إن حساب Customer محدش بيشوفله أي شاشة اشتراك مدفوع أبدًا (US-SUB-10)

Tests: طلب permission بيحصل بس عند استخدام nearest-shop، رفض الـ permission 
مايكسرش البحث، customer account مفيهوش أي endpoint يعرض plans.

اديني ملخص.
```

---

## Phase 13+ — R4 (Payments, Shipping, Advanced)

```
اقرأ THOB_Implementation_Specification.md وركز على:
- Section 4.11 كاملة (US-PAY-01, US-PAY-02, US-SHP-01)
- Section 10.7 (payments polymorphic table, shipments)
- Business Rules: BR-PAY-01, BR-PAY-02
- Section 15 بند PCI-DSS (SEC-NFR-07) — أهم بند أمان في المشروع كله

المطلوب:
1. Payments module: 
   - Order payment عبر hosted checkout من مزود متوافق مع PCI-DSS — 
     ممنوع منعًا باتًا تمرير أي بيانات كارت عبر سيرفرات THOB (BR-PAY-01)
   - Webhook handler: تحقق من التوقيع قبل أي تحديث، idempotent ضد التكرار
   - فصل منطقي بين subscription billing و order payment (BR-PAY-02) 
     — نفس الـ provider client بس handlers منفصلة
2. Shipping module: shipment assignment + tracking number + status updates 
   (US-SHP-01) — تكامل مش بناء منصة لوجستية
3. Feature flags: أي endpoint من R4 لازم يكون خلف `feature('online_payment')` 
   أو `feature('shipping')` عشان يفضل مقفول لحد ما تقرر تفعّله فعليًا

Tests: webhook من غير توقيع صحيح يترفض، webhook مكرر (replay) ميعملش 
تأثير مضاعف، محاولة الوصول لـ R4 endpoint والـ feature flag مقفول = 404/403.

اديني ملخص.
```

---

## ملاحظات عامة تتكرر في كل Phase (اقرأها قبل ما تبدأ أي حد)

- لو Claude Code اقترح حاجة مش مذكورة في السبيك (مكتبة تانية، معمارية 
  مختلفة، ميزة إضافية)، وقفه واسأله يرجع لـ Section رقم كذا في السبيك.
- كل Phase لازم يخلص بـ tests شغالة قبل ما تنتقل للي بعدها.
- لو حسيت الـ context بتاع الـ session طويل قوي، اقفل واعمل session جديدة 
  وابدأها بـ "كمّل من حيث ما وقفنا في Phase X، اقرأ THOB_Implementation_Specification.md 
  الأول."
