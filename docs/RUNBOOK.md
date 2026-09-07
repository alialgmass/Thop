# THOB — Developer Runbook

Practical "how do I run / reset / test this" reference. Hand-maintained.
<span dir="rtl">دليل تشغيل سريع للمطوّر — الإعداد، قاعدة البيانات، التيستات، لوحة الأدمن، ومشاكل شائعة.</span>

---

## 1. Stack & prerequisites

- **PHP 8.4** (composer.json floor is 8.3), **Composer 2**
- **Node 20+** / npm (only for the Filament panel assets — the API needs no JS build)
- **SQLite** for local dev & tests (default). **MySQL 8+** is the production target.
- No Docker, Redis, or queue worker required for local API work (`QUEUE_CONNECTION=sync`).

Laravel modular monolith via `nwidart/laravel-modules`. Every module lives in
`Modules/<Name>/` with its own `routes/`, `Http/`, `Models/`, `database/`, `tests/`,
`config/`, `lang/`.

---

## 2. First-time setup

```bash
git clone <repo> thob && cd thob
composer install
cp .env.example .env
php artisan key:generate

# SQLite dev DB
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed

# Filament panel assets (only if you need /admin)
npm install && npm run build
```

> `.env.example` carries the THOB keys that matter (locale, DB, `OTP_DRIVER`,
> `VERIFICATION_DISK*`, `CATALOG_REVIEW_*`, queue). §3 explains the THOB-specific ones.

Serve:

```bash
php artisan serve                 # http://127.0.0.1:8000  → API at /api/v1, panel at /admin
# or the full dev stack (server + queue + logs + vite):
composer run dev
```

---

## 3. Environment configuration

Only the keys below affect the completed phases. Anything about payments, Pusher, PWA is
dormant.

| Key | Default | Purpose |
|---|---|---|
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `en` | Envelope message language; per-request override via `Accept-Language: ar`. |
| `DB_CONNECTION` | `sqlite` | `sqlite` locally, `mysql` in prod (see §6). |
| `OTP_DRIVER` | `log` | OTP sender binding. `log` = `LogOtpSender`, which records only *that* a code was issued, **never the code** (SEC-NFR-02). Add real SMS as a new key in `Modules\Auth\Providers\AuthServiceProvider::OTP_DRIVERS`. |
| `VERIFICATION_DISK` | `verification` | Filesystem disk name for verification documents. |
| `VERIFICATION_DISK_DRIVER` | `local` | `local` in dev; `s3` in prod (private bucket). Defined in `config/filesystems.php`. |
| `VERIFICATION_SCANNER` | `signature` | Malware scan on uploads (SEC-NFR-05). `signature` = dependency-free EICAR check; `null` = off; a real ClamAV/hosted adapter is registered in `VerificationServiceProvider::SCANNERS`. |
| `FILESYSTEM_DISK` | `local` | Product media (Phase 3) will use `public`. |

OTP tuning lives in `Modules/Auth/config/otp.php` (length 6, TTL 300s, 3 attempts, handoff
token TTL 600s, throttles 3/5/5 per minute) — not in `.env`.

Verification upload limits live in `Modules/Verification/config/verification.php`
(`accepted_mimes`, `accepted_mimetypes`, `max_file_size_kb`, `download_link_ttl_seconds`).

### Getting an OTP code locally

The `log` sender never emits the code and it is hashed at rest (SEC-NFR-02), so you can't
read a real OTP by default. For manual API / Postman testing:

1. **Use the `capture` driver** (recommended). Set `OTP_DRIVER=capture` in `.env`. It
   stashes the plaintext in the cache for 5 minutes; it is **refused in production**.
   After `POST /api/v1/auth/otp/request`:
   ```bash
   php artisan tinker --execute "echo cache('otp:+201000000001');"
   ```
   (Phone is the normalized `+20…` form. The buyer phone `01000000001` → `+201000000001`.)
2. **Skip OTP in tests** — feature tests bind `FakeOtpSender`
   (`Modules/Auth/tests/Support/FakeOtpSender.php`) which exposes the last code. Use the
   `AuthModuleTestCase::completeOtp()` helper.

---

## 4. Database — reset & seed

```bash
php artisan migrate:fresh --seed      # drop all, re-migrate, re-seed
php artisan migrate --seed            # apply new migrations + seed
php artisan db:seed                   # re-run seeders only
```

`DatabaseSeeder` runs, in order:

| Seeder | Gives you |
|---|---|
| `RoleSeeder` (Admin) | the `admin` Spatie role (`web` guard). **No admin user** — see §7. |
| `TaxonomyDatabaseSeeder` | 27 governorates + fabric types, materials, colors (with hex), units. |
| `DocumentTypeSeeder` (Verification) | `commercial_register` + `tax_card` document types, `is_required = true`. |
| `SubscriptionPlanSeeder` | Importer Basic/Pro/Premium + one Wholesaler + one Retailer plan, entitlements from spec Appendix A, **no prices** (`price` nullable). |

There is **no user/business/product factory seeder** — create those with model factories in
tinker or tests. Example dev fixture:

```bash
php artisan tinker
>>> $u = \App\Models\User::factory()->create(['account_type' => 'wholesaler', 'status' => 'active']);
>>> $u->createToken('api')->plainTextToken;   // bearer token for Postman
```

---

## 5. Running tests

Config: `phpunit.xml` forces `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`,
`BCRYPT_ROUNDS=4`, array cache/mail/session, sync queue. Tests use `RefreshDatabase`.

```bash
# everything
php artisan test --compact                       # 338 tests, ~1-3 min

# one module / phase
php artisan test --compact Modules/Auth           # Phase 0
php artisan test --compact Modules/Businesses Modules/Verification Modules/Admin   # Phase 1
php artisan test --compact Modules/Subscriptions   # Phase 2
php artisan test --compact Modules/Search           # Phase 4
php artisan test --compact Modules/Favorites Modules/Comparison   # Phase 5
php artisan test --compact Modules/Inquiries        # Phase 6

# one file / one test
php artisan test --compact Modules/Inquiries/tests/Feature/RfqQuotationTest.php
php artisan test --compact --filter=an_expired_quotation_is_shown_as_expired
```

Phase → module → test-count map is in `docs/qa/` (one file per phase) and `docs/PHASE_STATUS.md`.

`composer run test` additionally runs `pint --test` (style check) first — use
`vendor/bin/pint` (no `--test`) to auto-fix style before committing PHP changes.

Current baseline (2026-09-06, SQLite `:memory:`):

| Suite | Tests | Pass | Fail | Skip |
|---|---|---|---|---|
| Full | 338 | 338 | 0 | 0 |
| Auth | 50 | 50 | 0 | 0 |
| Businesses + Verification + Admin | 52 | 52 | 0 | 0 |
| Subscriptions | 72 | 72 | 0 | 0 |
| Catalog | 31 | 31 | 0 | 0 |
| Search | 37 | 37 | 0 | 0 |
| Favorites + Comparison | 15 | 15 | 0 | 0 |
| Inquiries | 37 | 37 | 0 | 0 |

---

## 6. SQLite ↔ MySQL

Migrations are written to be **MySQL-compatible**; MySQL-only statements (`FULLTEXT`
indexes in Catalog/Search) are guarded to no-op on SQLite. You can develop entirely on
SQLite; switch to MySQL when you need to exercise real full-text search or the
PRF-NFR-01 load target.

**To MySQL:**

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thob
DB_USERNAME=root
DB_PASSWORD=
```

```bash
mysql -uroot -e "CREATE DATABASE thob CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed
```

**Back to SQLite:** restore `DB_CONNECTION=sqlite`, ensure `database/database.sqlite` exists,
`php artisan migrate:fresh --seed`.

The test suite always runs on in-memory SQLite regardless of `.env` (set in `phpunit.xml`) —
so **`FULLTEXT` behaviour is never exercised by automated tests**. Search tests assert the
SQLite `LIKE` fallback path. Real full-text verification is a manual/CI task on MySQL
(discrepancy D12).

---

## 7. Filament admin panel (`/admin`)

The only web UI. Access requires a `User` (or `Modules\Admin\Models\Admin`) with the
`admin` role. Create one:

```bash
php artisan tinker
>>> $a = \App\Models\User::factory()->create([
...   'phone' => '+201000000000', 'account_type' => 'importer', 'status' => 'active',
... ]);
>>> $a->assignRole('admin');
>>> $a->forceFill(['password' => \Illuminate\Support\Facades\Hash::make('password')])->save();
```

Then log in at `http://127.0.0.1:8000/admin` with that phone + password.

Panels currently live (Phase 1 + Phase 2 slices of Phase 9):

- **Verification Requests** — queue, view (company + request + documents), Approve / Reject
  (reason required). Shares `DecideVerificationRequest` with the REST API.
- **Subscription Plans** — full CRUD + entitlements repeater.
- **Subscriptions** — read-only list + view, with Grant Trial / Extend Period / Cancel actions.

Everything else on Phase 9 (taxonomy CRUD, featured curation, liquidity dashboard,
suspend/ban, dispute queue, assisted onboarding) is not built.

If you see `ViteException: Unable to locate file in Vite manifest` on `/admin`,
run `npm run build`.

---

## 8. Common issues

| Symptom | Cause / fix |
|---|---|
| `ViteException: Unable to locate file in Vite manifest` | Panel assets not built → `npm run build` (or `npm run dev`). API routes are unaffected. |
| `SQLSTATE ... no such table` in tests | Stale state — tests use `:memory:`; just re-run. For dev DB: `php artisan migrate:fresh --seed`. |
| 401 `{"custom_code":4001}` on every call | Missing/expired `Authorization: Bearer` header. Re-issue a token (login or tinker `createToken`). |
| 403 `{"message":"This action is unauthorized."}` (no envelope) | Expected — policy denials fall back to Laravel's native 403 (spec §11 allows it). Check Account Type + ownership. |
| Validation returns HTTP **422** with `errors: {}` instead of **400** enveloped | You hit `POST`/`PATCH /subscriptions` — those two requests extend the framework `FormRequest`, not Core `BaseRequest` (discrepancy D5). All other endpoints return enveloped 400. |
| OTP verify always fails locally | You can't read the `log` driver's code (§3). Set `OTP_DRIVER=capture` and read it from the cache. |
| `Accept-Language: ar` has no effect | Header must be on the request; the Core `api.language` middleware only runs on `/api/v1/*`. Filament uses `APP_LOCALE`. |
| `pint --test` fails in `composer run test` | Run `vendor/bin/pint` to auto-fix, then re-run. |
| Telescope entries missing | `TELESCOPE_ENABLED` — off in testing; on in local by default. |
| `spatie/permission` "role does not exist" | `php artisan db:seed --class="Modules\Admin\Database\Seeders\RoleSeeder"` (or full `db:seed`). |

---

## 9. Where things live

| You want… | Look in |
|---|---|
| Product spec (authoritative) | `docs/docs/THOB_Implementation_Specification.md` |
| Phase order & session prompts | `docs/docs/THOB_ClaudeCode_Prompts.md` |
| What's done / what's next | `docs/PROGRESS.md` (narrative) · `docs/PHASE_STATUS.md` (grid) |
| Every API endpoint contract | `docs/API_REFERENCE.md` |
| Domain vocabulary | `CONTEXT.md` (repo root) |
| Manual QA checklists + traceability | `docs/qa/README.md` + `docs/qa/phase-*.md` |
| Postman collection | `docs/postman/` |
| Architecture decisions | `docs/adr/` |
| Project rules for agents | `CLAUDE.md` (root, Laravel Boost) + `docs/CLAUDE.md` (THOB rules) |
