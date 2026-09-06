# Phase 0 QA — Auth Foundation

Module: `Modules/Auth` (+ `Modules/Core` envelope). Spec: issue #1 · US-ACC-01, US-ACC-02,
US-ACC-07 · SEC-NFR-02.

## 1. Status summary

| Axis | State |
|---|---|
| Implementation | ✅ Complete — 9 endpoints, OTP service, handoff tokens, Sanctum bearer tokens |
| Automated Tests | ✅ **46 passing** (`php artisan test Modules/Auth`) — 156 assertions |
| Manual QA | ⬜ **NOT RUN** — cases in §4 |
| Postman Coverage | ✅ 9/9 endpoints, folder "Phase 0 — Auth", token auto-capture on OTP verify + register + login |
| Documentation | ✅ `docs/API_REFERENCE.md` §3 items 1–9 |
| **Verification Status** | **DOCUMENTED** (not VERIFIED — manual QA pending) |

## 2. Requirements traceability

| Req | Acceptance criterion | Automated test(s) | Verdict |
|---|---|---|---|
| US-ACC-01 | OTP sent on registration, expires in 5 min | `OtpRequestTest::it_sends_a_hashed_code_and_never_returns_it` (asserts expiry ≤ 300s) | ✅ |
| US-ACC-01 | Correct OTP in window → account in `pending_type_selection` | `OtpVerifyTest::a_correct_code_returns_a_registration_token` + `RegistrationTest::a_verified_phone_can_register…` | ✅ |
| US-ACC-01 | 3 wrong OTP attempts → rate-limited / locked | `OtpVerifyTest::three_wrong_attempts_lock_the_code` | ✅ |
| US-ACC-01 | OTP provider failure → localized actionable error | `OtpRequestTest::it_surfaces_a_localized_error_when_the_provider_fails` (503/5031) | ✅ |
| US-ACC-01 | Already-registered phone → routed to login | `OtpRequestTest::registration_request_for_an_existing_phone_routes_to_login_and_sends_nothing` (409/4091) | ✅ |
| US-ACC-01 | Email optional, not a verification gate | `RegistrationTest::email_is_optional_but_must_be_unique_when_given` | ✅ |
| BR-ACC-01 | One account per verified phone | `RegistrationTest::phone_format_variants_resolve_to_one_account`; `Unit/PhoneNumberTest` (12 cases) | ✅ |
| US-ACC-02 | Verified account with no type → chooser with bilingual text | `AccountTypeTest::the_selectable_account_types_are_listed_with_bilingual_text`, `…switch_with_the_locale` | ✅ |
| US-ACC-02 | Business type → routed to business-profile flow | `AccountTypeTest::a_pending_user_can_choose…becomes_active` (`next_onboarding_step=business_profile`) | ✅ |
| US-ACC-02 | Customer → no business profile requested | `AccountTypeTest::customer_selection_reports_no_further_onboarding` | ✅ |
| BR-ACC-02 | Type cannot be self-changed once set | `AccountTypeTest::the_account_type_cannot_be_changed_once_set` (409) | ✅ |
| US-ACC-07 | Login with phone + password | `LoginTest::a_user_can_log_in_with_phone_and_password` | ✅ |
| US-ACC-07 | Wrong password rejected (no enumeration) | `LoginTest::a_wrong_password_is_rejected` (422/4222 generic) | ✅ |
| US-ACC-07 | Login throttled after repeated failures | `LoginTest::login_is_throttled_after_repeated_failures` (429 after 5) | ✅ |
| US-ACC-07 | Logout revokes current token | `LoginTest::logout_revokes_the_current_token` | ✅ |
| US-ACC-07 | Password recovery via OTP | `PasswordResetTest` (3) — reset works, old password fails, reg-token rejected | ✅ |
| SEC-NFR-02 | OTP stored hashed, never returned/logged | `OtpRequestTest::it_sends_a_hashed_code_and_never_returns_it`; `OtpServiceTest::issuing_stores_a_hash_not_the_plaintext` | ✅ (see §4-1 for the log assertion) |
| SEC-NFR-02 | OTP expires in 5 min | `OtpVerifyTest::an_expired_code_is_rejected` (travels time) | 🟡 time-travel, not wall-clock |
| SEC-NFR-02 | Attempts rate-limited | `OtpRequestTest::it_throttles_repeated_requests_for_the_same_phone` (4th → 429) | 🟡 within one process, not a real 60s window |

## 3. Coverage gaps

| Gap | Why automation can't fully cover it | Mitigation |
|---|---|---|
| **Real OTP over a real SMS provider** | `log` driver in dev/test; `FakeOtpSender` in tests. No real provider wired. | Manual case §4-2 once an SMS provider exists. |
| **Wall-clock expiry / throttle windows** | Tests use `travel()` and single-process `RateLimiter`. A real 5-min expiry and a real 60s throttle reset are not exercised against the clock. | Manual cases §4-3, §4-4. |
| **"OTP never in plaintext logs" as an integration assertion** | Unit test asserts the sender only logs the phone; no test greps the actual log file across the whole request. | Manual case §4-1. |
| **Concurrent OTP requests / race on `consumed_at`** | Not simulated. | Low risk; note for Phase 10 security suite. |

## 4. Manual QA test cases

### QA-0-1 · OTP is never written to logs in plaintext (SEC-NFR-02)
- **Pre:** `OTP_DRIVER=log`, `LOG_LEVEL=debug`, fresh `storage/logs/laravel.log`.
- **Steps:** `POST /api/v1/auth/otp/request` `{phone: "01000000001", purpose: "registration"}`.
  Then `grep -RинE '[0-9]{6}' storage/logs/laravel.log` and inspect every hit.
- **Expected:** log contains `OTP issued` with `phone` only; **no 6-digit code**, no `code_hash`,
  no stack trace exposing the code. `otp_requests` row stores a bcrypt hash in `code_hash`.
- **Result:** ☐ pass ☐ fail — notes:

### QA-0-2 · Real SMS delivery (blocked until an SMS provider is added)
- **Pre:** real `OTP_DRIVER` (e.g. `sms`) configured with sandbox credentials; a phone you control.
- **Steps:** request registration OTP for that number; read the SMS; verify it.
- **Expected:** SMS arrives within provider SLA; code verifies; `body.registration_token` returned.
  Provider outage → `503/5031` with localized message, and a retry succeeds.
- **Result:** ☐ pass ☐ fail ☐ blocked — notes:

### QA-0-3 · OTP genuinely expires after 5 minutes (wall clock)
- **Pre:** `OTP_DRIVER=capture` so you can read the code from the cache (RUNBOOK §3).
- **Steps:** request OTP; wait **>5 min** real time; `POST /auth/otp/verify` with the correct code.
- **Expected:** `422 / custom_code 4221`, `body.code` message = "expired". A fresh request then
  succeeds.
- **Result:** ☐ pass ☐ fail — notes:

### QA-0-4 · OTP request throttle resets after the window
- **Steps:** send 3 OTP requests for one phone in <60s (all 200) → 4th within the same minute →
  `429 / 4290`, `body.throttle`. Wait 60s. Send again.
- **Expected:** 4th is 429 with a "wait N seconds" message; after the window a new request is 200.
- **Result:** ☐ pass ☐ fail — notes:

### QA-0-5 · Account-type language follows `Accept-Language` end to end
- **Steps:** `GET /auth/account-types` with `Accept-Language: ar`, then with `en`.
- **Expected:** `customer` label = "عميل" (ar) / "Customer" (en); all 4 entries localized;
  `requires_business_profile` = `false` only for `customer`.
- **Result:** ☐ pass ☐ fail — notes:

### QA-0-6 · Full onboarding happy path (Postman "Phase 0 — Auth" folder, run in order)
- **Steps:** OTP request → OTP verify (token auto-saved) → register (bearer auto-saved) →
  `GET /auth/me` → `POST /auth/account-type {importer}` → `GET /auth/me` again.
- **Expected:** `me` first shows `account_type: null, status: pending_type_selection,
  next_onboarding_step: account_type_selection`; after account-type, `status: active`,
  `next_onboarding_step: business_profile`.
- **Result:** ☐ pass ☐ fail — notes:

## 5. Spec ⇄ implementation notes

- Envelope: validation errors are **HTTP 400 / `custom_code` 4000** (not 422) — matches spec §11.
- `POST /auth/password/reset` revokes **all** the user's bearer tokens (spec is silent; security
  decision under `docs/CLAUDE.md` rule 3). Confirm this is desired product behaviour during QA.
- `login_failed` uses `body.phone` as the error key even when the password is what's wrong —
  deliberate anti-enumeration choice.
