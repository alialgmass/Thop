# THOB — Progress Tracker

آخر تحديث: 2026-09-01 — Phase 0 spec published (issue #1)

## طريقة الاستخدام
بعد كل Phase، حدّث الحالة هنا: `⬜ لسه` / `🔄 شغال عليها` / `✅ خلصت + tests عدّت`.
لو Phase اتقسمت لـ sub-phases (زي Phase 3)، سجّلهم كلهم.

## R1 — MVP

| Phase | الوصف | الحالة | ملاحظات |
|---|---|---|---|
| 0 | Project Setup + Auth Foundation | 🔄 | Spec ready: GitHub issue #1 (label `ready-for-agent`). Seams: HTTP feature tests on `/api/v1/auth/*` + faked `OtpSender`. Auth = Sanctum bearer tokens. |
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

-

## Blockers الحالية
-
