# THOB — Project Instructions for Claude Code

هذا الملف بيتقرأ تلقائيًا في أول كل session. اقرأه دايمًا قبل أي تعديل.

## المصدر الوحيد للحقيقة

- **`docs/THOB_Implementation_Specification.md`** — السبيك الكاملة (User Stories, 
  DB schema, API catalog, Architecture, Business Rules, Authorization Matrix). 
  أي قرار تصميم أو معماري يرجع لها. لو حاجة مش موجودة فيها، دورّ في 
  "Open Decisions" (Section 21) قبل ما تفترض حاجة من عندك.
- **`docs/THOB_ClaudeCode_Prompts.md`** — تقسيم الشغل لـ Phases، وترتيب التنفيذ.
- **`PROGRESS.md`** — إيه اللي خلص وإيه اللي لسه، حدّثه في آخر كل session.

## قواعد ثابتة (متتجاوزش عنها أبدًا)

1. **Laravel Modular Monolith فقط.** تطبيق واحد، قاعدة بيانات واحدة (MySQL)، 
   deployment واحد. ممنوع نهائيًا: microservices، Kubernetes، Kafka، RabbitMQ، 
   event sourcing، CQRS، GraphQL، Elasticsearch (إلا كخيار مستقبلي موثّق مش 
   مطبّق فعليًا)، Redis إجباري، repository/service interfaces لكل حاجة.
2. **Shared hosting compatible.** من غير root access، من غير Docker في 
   production، من غير أي long-running process تاني غير queue worker عادي.
3. **الأمان أولوية فوق أي حاجة:**
   - أي subscription/entitlement/role claim جاي من الكلاينت — **ممنوع نثق فيه**. 
     الفحص دايمًا server-side (`EntitlementService`).
   - مستندات التوثيق: private S3، ممنوع URL يتخمّن.
   - بيانات الكارت (R4): ممنوع تلمس سيرفرات THOB أبدًا. Hosted checkout بس.
   - كل authorization عبر Laravel Policies، بالترتيب: authenticated → role → 
     ownership → business ownership → subscription entitlement → admin override.
4. **لا حذف نهائي بدون سبب موثّق.** المنتجات soft-delete. انتهاء الاشتراك 
   يخفي المنتجات مش يمسحها. راجع Business Rules (Section 5 في السبيك).
5. **ممنوع تخترع requirement.** لو حسيت حاجة ناقصة في السبيك، وضّحها كـ 
   "Implementation Assumption" في تعليق الكود + في ملخص الـ session، ومتفترضش 
   بصمت.
6. **الإصدارات (Releases) مقفولة بالترتيب:** R1 (MVP) → R2 → R3 → R4. 
   ممنوع تبني feature من R2+ قبل ما R1's Must-priority items تخلص وتتعمل 
   لها tests. لو module من R4 (زي Payments) اتبنى مبكر لأي سبب، لازم يكون 
   خلف feature flag مقفول.
7. **كل Phase لازم يخلص بـ tests شغالة (فعليًا passing) قبل ما تتحرك للي بعدها.**

## البنية المعمارية (Section 9 في السبيك)

```
app/
Modules/
├── Auth/
├── Users/
├── Businesses/
├── Verification/
├── Taxonomy/
├── Catalog/
├── Search/
├── Favorites/
├── Comparison/
├── Inquiries/
├── Chat/
├── Subscriptions/
├── Orders/          (R2)
├── Payments/         (R4)
├── Shipping/         (R4)
├── Analytics/
├── Notifications/
├── Locations/        (R3)
└── Admin/
```

كل موديول فيه بس الفولدرات اللي محتاجها فعليًا (Models, Http/Controllers, 
Http/Requests, Policies, Routes, Resources, Database/Migrations, Tests). 
ممنوع تعمل فولدر Services/ أو Actions/ لمجرد التماثل — بس لو فيه منطق 
فعلي محتاج عزل.

## Stack

- Laravel (أحدث LTS متاحة وقت البدء)
- MySQL 8+
- Laravel Sanctum (auth tokens)
- Laravel Filesystem + S3 driver (media + verification docs)
- Pusher PHP SDK (realtime chat)
- Laravel Notifications (built-in) + queue driver
- PHPUnit أو Pest للـ tests

راجع `.env.example` للمتغيرات المطلوبة.

## قبل ما تبدأ أي Phase

1. اقرأ الأقسام المذكورة في برومبت الـ Phase من `docs/THOB_ClaudeCode_Prompts.md`.
2. افتح `PROGRESS.md` وشوف آخر حالة.
3. اشتغل على branch منفصل لكل Phase.
4. في الآخر: شغّل الـ tests، حدّث `PROGRESS.md`، واديني ملخص بالملفات 
   اللي اتعملت/اتعدّلت.

## لو حصل تعارض

لو حاجة في الكود الحالي بتتعارض مع السبيك، السبيك هي اللي تكسب — عدّل 
الكود، ومتعدلش السبيك من غير ما أطلب صراحة.
