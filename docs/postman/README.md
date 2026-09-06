# THOB Postman Collection

Hand-maintained, seeded from the live route inventory + FormRequest/Resource source
(2026-09-06). Covers **phases 0, 1, 2, 3 (products + media), 4, 5, 6** — 57 requests in 8 folders.

| File | What it is |
|---|---|
| `THOB API.postman_collection.json` | The collection (schema v2.1.0). |
| `THOB.postman_environment.json` | The **THOB — Local** environment (base URL, phones, IDs). |

## Import

1. Postman → Import → drop both files.
2. Top-right environment selector → **THOB — Local**.
3. Edit `base_url` if your app isn't on `http://127.0.0.1:8000`.

## Authentication

The collection sets `Authorization: Bearer {{token}}` on **every** request. Public
requests (OTP, register, login, all Search, all Taxonomy) override this to *No Auth*.
Admin requests override it to `Bearer {{token_admin}}`; some Phase 6 requests use
`{{token_buyer}}` / `{{token_seller}}`.

### Getting a token

Run, in order, in the **Phase 0 — Auth** folder:

1. **OTP request** — `{{otp_purpose}}` defaults to `registration`.
2. **Set `{{otp_code}}` by hand.** The `log` OTP driver never emits the code (SEC-NFR-02).
   See `docs/RUNBOOK.md` §3 — quickest path is a dev-only capture sender, or use the test
   suite's `FakeOtpSender`. On a real SMS provider, read it from the SMS.
3. **OTP verify** — its test script saves `{{registration_token}}`.
4. **Register** — its test script saves `{{token}}` (and `{{token_buyer}}`).
   *Or* **Login** if the account already exists.
5. **Account type — choose** — pick `wholesaler`/`retailer` to act as a buyer,
   `importer` to act as a seller. Repeat the whole flow with `{{phone_seller}}` to get a
   second identity, and copy that token into `{{token_seller}}` manually.

For **admin** requests: create an admin user (RUNBOOK §7), log in as them, copy
`body.token` into `{{token_admin}}`.

### Multi-actor testing

Phase 6 needs three identities at once (buyer, seller, sometimes admin). The collection
reads `{{token_buyer}}` / `{{token_seller}}` / `{{token_admin}}` for those. Populate them
once by running the auth flow per phone and pasting the resulting token into the matching
variable.

## Test scripts

- **Collection level**: every response is checked for valid JSON and that
  `envelope.status` matches the HTTP class.
- **Per request**: OTP verify → saves the handoff token; Register / Login → save the
  bearer token; Create business / Subscribe / List plans / Search / Add favorite /
  Send inquiry / Submit RFQ / Admin queue → save the id they return into the environment,
  so the next request in the folder just works.

## Saved examples

Representative responses are attached to: OTP request (200 + 409), OTP verify (200),
Create business profile (201), Search products (200), Send inquiry (201 + 422 limit).
Others show the shape via the request description + `docs/API_REFERENCE.md`.

## What's NOT here

- **Phase 3 bulk import** (`POST /products/import` + template + per-row report) — not built
  (#16). The "Phase 3 — Catalog" folder covers product CRUD + media (3.1/3.2).
- **Verification document download** is a single "paste the signed URL" request — the URL
  comes back in the *Upload* response and is saved to `{{download_url}}`.
- **Filament admin actions** (grant trial, extend period, taxonomy CRUD) — those are panel
  UI, not REST. See `docs/RUNBOOK.md` §7.
- Phases 7–10 (Chat, Notifications, rest of Admin, Hardening).

## Known response quirks (see `docs/API_REFERENCE.md` §1.3)

- **403** and **404** fall back to Laravel's native body (`{"message":"…"}`), not the
  envelope. The collection-level `status` check is skipped when there's no `status` key.
- `POST` / `PATCH /subscriptions` return **HTTP 422** `{message,errors}` on validation
  error (not the enveloped 400) — those two requests extend the framework FormRequest.

## Keeping it current

When you add or change an endpoint: update the request here **and** the row in
`docs/API_REFERENCE.md` **and** `docs/PHASE_STATUS.md`. There is no generator — the three
are kept in sync by hand, same as `docs/PROGRESS.md`.
