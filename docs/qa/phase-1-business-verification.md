# Phase 1 QA — Business Profile · Verification · Audit Log

Modules: `Modules/Businesses`, `Modules/Verification`, `Modules/Admin`. Spec: issue #3,
tickets #4–#9 · US-ACC-03/04/05, US-ADM-01, US-ADM-09 · SEC-NFR-01/03/05, DAT-FR-02.

## 1. Status summary

| Axis | State |
|---|---|
| Implementation | ✅ Business CRUD (3 EP), verification upload/submit/status/download (4 EP), admin queue/approve/reject (3 EP), audit log, Filament verification panel |
| Automated Tests | ✅ **50 passing** — Businesses 16 (+4 ContactVisibilityTest), Verification 31 (+2 malware scan), Admin 5 |
| Manual QA | ⬜ **NOT RUN** — cases in §4 |
| Postman Coverage | ✅ 9/10 REST endpoints. Signed-URL document **download** is a manual step (URL comes back in the upload response). |
| Documentation | ✅ `docs/API_REFERENCE.md` §3 items 10–19 |
| **Verification Status** | **DOCUMENTED** |

## 2. Requirements traceability

| Req | Acceptance criterion | Automated test(s) | Verdict |
|---|---|---|---|
| US-ACC-03 | Incomplete fields → field-level validation | `BusinessProfileTest::required_fields_are_validated`, `the_governorate_must_exist_in_the_taxonomy` | ✅ |
| US-ACC-03 | Valid data → `BusinessAccount` linked to `User`, `unverified` | `BusinessProfileTest::a_business_account_user_creates_a_profile_in_unverified_status` | ✅ |
| US-ACC-03 | Profile editable on return | `BusinessProfileTest::an_owner_can_edit_their_profile` | ✅ |
| BR-ACC-03 | Governorate is a taxonomy reference, not free text | `BusinessProfileTest::the_governorate_must_exist_in_the_taxonomy` | ✅ |
| — | One profile per user | `BusinessProfileTest::a_user_cannot_create_a_second_profile` (422) | ✅ |
| — | Customer account cannot create a profile | `BusinessProfileTest::a_customer_account_cannot_create_a_profile` (422) | ✅ |
| — | Non-owner sees public subset only | `BusinessProfileTest::a_non_owner_sees_only_the_public_subset` | ✅ |
| — | Actor columns record created_by / updated_by | `BusinessProfileTest::the_actor_columns_record_who_created_and_last_edited…` | ✅ |
| US-ACC-04 | Upload validated for MIME + size before acceptance | `VerificationUploadTest::a_file_with_a_disallowed_type_is_rejected…`, `…content_type_does_not_match_its_extension…`, `an_oversized_file_is_rejected` | ✅ |
| US-ACC-04 | File on private location, no guessable URL | `VerificationUploadTest::an_owner_uploads_a_valid_document_and_it_lands_on_the_private_disk` (obfuscated path), `the_download_link_is_signed_and_time_limited` | 🟡 (fake disk; real S3 = manual §4-3) |
| US-ACC-04 | Pending docs → account `verification_pending` → `verified`/`rejected` | `VerificationUploadTest::submitting_moves_the_business_to_pending…`; `AdminVerificationReviewTest` approve/reject | ✅ |
| US-ACC-04 | Rejection reason visible, re-upload allowed | `VerificationUploadTest::the_owner_reads_their_status_including_a_rejection_reason`; `AdminVerificationReviewTest::re_uploading_after_rejection_returns_the_business_to_pending` | ✅ |
| US-ACC-04 | Only admin can generate signed download URL | `VerificationUploadTest::another_business_cannot_touch_this_business_documents_or_status`; `AdminVerificationReviewTest::an_admin_can_download_any_business_document`; `Unit/AuthorizationMatrixTest` | ✅ |
| US-ACC-05 | Badge included when `verification_status = verified` | `BusinessProfileTest::the_verified_flag_tracks_the_verification_status_column`; `AdminVerificationReviewTest::the_verified_badge_shows_after_approval` | ✅ |
| US-ACC-05 | Badge disappears immediately on revoke | — | ⬜ (suspension is Phase 9; see §3) |
| US-ADM-01 | Pending request → admin approve/reject → status + reason logged | `AdminVerificationReviewTest` (9), `Filament/VerificationRequestPanelTest` (6) | ✅ |
| US-ADM-01 | Reject requires a reason | `AdminVerificationReviewTest::rejecting_requires_a_reason` (400 `body.reason`); Filament `reject_requires_a_reason` | ✅ |
| US-ADM-01 | Already-decided request cannot be re-reviewed | `AdminVerificationReviewTest::an_already_decided_request_cannot_be_re_reviewed` (409/4092) | ✅ |
| US-ADM-09 | Admin action → append-only audit log, no update/delete | `AuditLogTest` (5): `record` appends, no `updated_at`, `update()`/`delete()` throw | ✅ |
| US-ADM-09 | Approve/reject write an audit row | `AdminVerificationReviewTest::approving_verifies…writes_an_audit_row…`, `rejecting_records_the_reason_writes_an_audit_row…` | ✅ |
| BR-ADM-01 | **Every** admin action audited | above — verification only | 🟡 (other admin actions are Phase 9) |
| SEC-NFR-05 | Files validated by type + size | as US-ACC-04 rows | ✅ |
| SEC-NFR-05 | Files **scanned** before publish | `VerificationUploadTest::a_file_carrying_the_eicar_signature_is_rejected_and_nothing_is_written`, `the_scanner_can_be_disabled_by_config` | ✅ (`FileScanner` seam; `SignatureFileScanner` EICAR default; real ClamAV/hosted = deploy config) |
| SEC-NFR-03 | Server-side authz by role + ownership | `Verification/tests/Unit/AuthorizationMatrixTest` (BusinessPolicy + VerificationPolicy rows) | ✅ |
| DAT-FR-02 | Verification docs access-restricted to admin | as US-ACC-04; `VerificationPolicy::download` | ✅ (encryption-at-rest = infra, §4-3) |

## 3. Coverage gaps

| Gap | Nature | Mitigation |
|---|---|---|
| **AV / malware scan of uploaded documents** (SEC-NFR-05 "scanned before publish") | ~~Not implemented~~ **DONE 2026-09-06** — `FileScanner` seam wired into upload (scans the temp file before anything is stored). Default `SignatureFileScanner` flags the EICAR signature; `VERIFICATION_SCANNER=null` disables it; a real ClamAV/hosted adapter is a deploy-config binding in `VerificationServiceProvider::SCANNERS`. | Add the real backend adapter when the infra is provisioned. |
| **Badge disappears on suspension** (US-ACC-05) | Suspension endpoint is Phase 9. `isVerified()` reads the column live (no cache), so it *should* flip — untested. | Re-test in Phase 9; manual case §4-5. |
| **Real private S3 bucket + signed-URL expiry against S3** | Tests use `Storage::fake()`. `temporaryUrl` is S3-only. | Manual case §4-3 in a staging env with real S3. |
| **BR-ADM-01 for non-verification admin actions** | Those actions don't exist yet. | Phase 9 QA. |
| **`contact_channels` JSON shape** | Implementation Assumption (`{type,value}[]`); no consumer validates the semantics. | Confirm with the mobile client team. |
| **Filament document download** (`admin.verification.documents.download`, session-auth) | Not in the automated REST suite path; 1 test hits it via signed route. | Manual case §4-4. |

## 4. Manual QA test cases

### QA-1-1 · Governorate list drives the profile form (US-ACC-03 / BR-ACC-03)
- **Steps:** `GET /taxonomy/governorates` → pick an `id`. `POST /businesses` with that id. Then
  `POST /businesses` with `governorate_id: 999999`.
- **Expected:** first → 201; second → 400 `body.governorate_id`. Inactive governorate also rejected.
- **Result:** ☐ pass ☐ fail

### QA-1-2 · Malicious / disguised file upload (SEC-NFR-05)
- **Pre:** an EICAR test file with a `.pdf` extension and a real `%PDF` header prepended; a real
  `.exe` renamed `.pdf`; a 20 MB PDF. (For a real ClamAV backend, set `VERIFICATION_SCANNER`
  accordingly on staging.)
- **Steps:** upload each to `POST /businesses/{id}/verification-documents`.
- **Expected:** `.exe`→pdf and content-type mismatch → **400 `body.file`**; oversized → 400;
  **EICAR-carrying PDF → 422 `body.file`** ("failed a security scan") with **no DB row and no
  file on the disk**. All three: nothing persisted.
- **Result:** ☐ pass ☐ fail

### QA-1-3 · Verification document is private on real S3 (US-ACC-04, DAT-FR-02, SEC-NFR-01)
- **Pre:** staging with `VERIFICATION_DISK_DRIVER=s3`, private bucket, SSE enabled.
- **Steps:** upload a doc → note the S3 key from logs/DB. Try the raw
  `https://<bucket>.s3…/<key>` directly (no signature). Use the `download_url` from the upload
  response within TTL, then again after TTL expires.
- **Expected:** raw URL → 403 AccessDenied. Signed URL within TTL → file streams. After TTL → 403.
  Object is SSE-encrypted at rest.
- **Result:** ☐ pass ☐ fail ☐ blocked

### QA-1-4 · Admin review via the Filament panel (US-ADM-01, US-ADM-09)
- **Pre:** admin user (RUNBOOK §7); a business with an uploaded + submitted verification request.
- **Steps:** open `/admin` → Verification Requests. Confirm the pending badge count. Open the
  record; view company data + documents; download a document. Approve one request; reject another
  with a reason; try Reject with an empty reason; open an already-approved request.
- **Expected:** approve → business Verified, request Approved, `reviewed_by`/`reviewed_at` set,
  audit row `verification.approved`. Reject → business Rejected + reason, audit row
  `verification.rejected`. Empty reason → validation error, request stays Pending. Approve/Reject
  buttons **hidden** on an already-decided request.
- **Result:** ☐ pass ☐ fail

### QA-1-5 · Badge lifecycle (US-ACC-05) — revisit in Phase 9
- **Steps:** verify a business → `GET /businesses/{id}` shows `verified: true`. (Phase 9) suspend
  the owner → immediately re-fetch.
- **Expected:** badge present after approval; **gone** immediately after suspension (no TTL wait).
- **Result:** ☐ pass ☐ fail ☐ blocked (Phase 9)

### QA-1-6 · Audit log is genuinely append-only at the DB level (US-ADM-09)
- **Steps:** with DB access, attempt `UPDATE audit_logs SET action='x' WHERE id=1;` and
  `DELETE FROM audit_logs WHERE id=1;` directly.
- **Expected (current):** the **application** blocks update/delete (model throws). Raw SQL is
  **not** blocked yet — a DB-level trigger/revoked grant is an optional Phase 10 hardening item.
  Record the raw-SQL result as informational.
- **Result:** ☐ pass ☐ fail — notes:

## 5. Spec ⇄ implementation notes

- `GET /businesses/{id}` has **no authorize gate** — any authenticated user can read the public
  subset of any business by id. Intentional (mirrors public supplier search) but worth a product
  confirmation.
- Verification 403s render as Laravel-native `{"message":""}` (via `abort_unless`), not the
  envelope — spec §11 permits framework-native 403.
- `submit` is owner-only; an admin cannot submit on the owner's behalf (assisted onboarding is
  Phase 9).
