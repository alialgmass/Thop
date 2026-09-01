# THOB — Progress Tracker

آخر تحديث: 2026-09-01 — Phase 0 اكتمل (branch `phase-0-auth`, 45 tests passing)

## طريقة الاستخدام
بعد كل Phase، حدّث الحالة هنا: `⬜ لسه` / `🔄 شغال عليها` / `✅ خلصت + tests عدّت`.
لو Phase اتقسمت لـ sub-phases (زي Phase 3)، سجّلهم كلهم.

## R1 — MVP

| Phase | الوصف | الحالة | ملاحظات |
|---|---|---|---|
| 0 | Project Setup + Auth Foundation | ✅ | Spec: issue #1. Module `Auth` (OTP request/verify, register, login/logout, OTP password reset, `me`, account-type). Sanctum bearer tokens. `/api/v1/auth/*`. Faked `OtpSender` seam. 43 module tests + full suite green. Stripped leftover multi-tenant POS scaffold. |
| 1 | Business Profile + Verification + Audit Log | ⬜ | |
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

### Phase 0
- **OTP = 6 أرقام**؛ صلاحية 5 دقايق (SEC-NFR-02)؛ قفل بعد 3 محاولات غلط. throttle: 3 طلبات OTP/دقيقة للرقم، 5 تحقق/دقيقة، 5 محاولات login/دقيقة (phone+IP). كلها في `Modules/Auth/config/otp.php` — الأرقام مش محددة في السبيك (Implementation Assumption).
- **`users.status`**: `pending_type_selection | active | suspended`. اكتمال بروفايل الشركة هيتتبع لاحقًا عبر verification status مش عبر العمود ده.
- **صيغ الهاتف المقبولة**: `01XXXXXXXXX` / `201XXXXXXXXX` / `+201XXXXXXXXX` (010/011/012/015) → تتخزن كـ `+20…`.
- **تعارض سبيك/كود**: الـ starter kit كان multi-tenant POS (Fortify + Inertia + name/email). اتشال بالكامل (web auth kit + tests بتاعته) لصالح schema THOB (phone-first). Fortify + Sanctum فاضلين للـ API. جدول `users` الأساسي اتعدّل مش اتعمله ALTER migrations.
- **DB**: التطوير/الاختبار على SQLite حاليًا؛ السبيك بيطلب MySQL 8 — التبديل مؤجّل (المايجريشنز متوافقة مع MySQL).
- `JsonResource::withoutWrapping()` مفعّل عالميًا من `AuthServiceProvider` — مفيش `data` wrapper في ردود الـ API.

## Blockers الحالية
-
