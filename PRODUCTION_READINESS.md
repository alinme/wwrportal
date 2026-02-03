# Production Readiness Checklist

This document lists what is **done** and what is **still to improve** for the WWR Portal, based on the project spec (PROJECT.md) and production requirements.

---

## 1. Security & Access Control

### 1.1 Admin-only routes — **Done**

- **Behaviour:** Dashboard, campaigns, schools, and settings are protected by the `admin` middleware (`EnsureAdmin`). Only users with the **admin** role can access them.
- **Magic link users:** Users who log in via the magic link have the **school_manager** role. They are **not** logged into the admin dashboard; they are redirected straight to their school portal (`/portal/{school}`). If they try to open `/dashboard`, `/campaigns`, etc., they are redirected to their portal.

### 1.2 Magic link rate limiting — **Done**

- **What it is:** Rate limiting caps how many times someone can call a URL in a time window (e.g. per IP). For the magic link, it means: “at most 10 attempts per minute per IP.” That prevents an attacker from trying many different tokens (brute-force).
- **Implementation:** The route `/access/{school}/{token}` uses `throttle:10,1` (10 requests per 1 minute per IP). After 10 failed or successful attempts in one minute, the next attempt gets “429 Too Many Requests” until the minute has passed.

### 1.3 First admin user — **Done**

- The first admin is created in `DatabaseSeeder`: run `php artisan db:seed`. The seeder creates roles (admin, school_manager, educator), then creates or finds the admin user and assigns the **admin** role (Spatie). See `database/seeders/DatabaseSeeder.php`.

---

## 2. Deployment

- **Status:** Hosting not yet decided (cPanel or custom VPS).
- **Note:** PROJECT.md mentioned `fly.toml` or `Dockerfile` for Fly.io; for cPanel or a classic VPS you typically don’t need those. When you choose a host, ensure:
  - `APP_ENV=production`, `APP_DEBUG=false`, correct `APP_URL`
  - Production DB (e.g. MySQL) and `php artisan migrate --force`
  - Queue worker if you use queued jobs (e.g. for heavy PDFs): `php artisan queue:work`
  - Scheduler if you add cron tasks: `php artisan schedule:work` or a cron entry

---

## 3. Configuration & Environment

### 3.1 R2 (Cloudflare) and Google Maps

- **R2:** See **R2_SETUP.md** for how to get R2 credentials and set `R2_*` in `.env`. `.env.example` lists the R2 variables.
- **Google Maps:** Set `GOOGLE_PLACES_API_KEY` in `.env` (see GOOGLE_MAPS_SETUP.md).

### 3.2 Romanian UI

- **Current:** `APP_LOCALE=ro` is set in `.env`. The school portal layout forces `app()->setLocale('ro')`.
- **Gap:** Many strings are still hardcoded in English or not yet in `lang/ro.json`. For full Romanian, replace hardcoded text with `__('Key')` and add the keys to `lang/ro.json` (and/or `lang/ro/`).

---

## 4. PDF & Templates

### 4.1 Templates — **Done (placeholders)**

- You added the template files (blank placeholders) under `storage/app/templates/`:
  - `contract_template.pdf`, `annex_template.pdf`, `gdpr.html`, `distribution_table_template.docx` (optional).
- Replace them with real content when ready. The PDF service uses DejaVu-style fonts for Romanian diacritics.

### 4.2 School contact: Contact person (no Director/CUI)

- **Schema:** Schools use **contact person** and **contact phone**, not director or CUI:
  - `schools.contact_person`, `schools.contact_phone`
  - (Groups also have `contact_phone`.)
- **PDF/contract:** Contract overlay uses `$school->official_name` and `$campaign->name`. If you want contact person on the contract, add it in `PdfGeneratorService` (e.g. `$school->contact_person`).

---

## 5. Storage & Assets

- **R2:** Configure R2 in `.env` (see R2_SETUP.md). Then generated PDFs can be stored in R2 and served via temporary signed URLs.
- **Queue for heavy PDFs:** Optional but recommended for large GDPR PDFs; implement a queued job and a queue worker when you’re ready.

---

## 6. Spatie Laravel Permission — **Done**

- **Setup:** Config and migrations are published; permission tables exist.
- **Roles:** Roles `admin`, `school_manager`, `educator` are created in `DatabaseSeeder`.
- **User model:** Uses `HasRoles`; admin user is assigned role `admin`; magic-link users are assigned `school_manager`.
- **Checks:** Admin routes use `EnsureAdmin` (which uses `hasRole('school_manager')` to redirect); school portal uses `EnsureSchoolAccess` with `hasRole('school_manager')`. The `users.role` column is kept in sync for convenience.

---

## 7. Testing

- **Status:** Accepted. After these changes, tests should be updated so that:
  - Users with role **admin** can access dashboard, campaigns, schools.
  - Users with role **school_manager** are redirected to their portal when visiting admin routes (and can only use portal routes).

---

## Summary Table

| Area                  | Status | Notes |
|-----------------------|--------|--------|
| Admin-only routes     | Done   | EnsureAdmin; magic link → portal only |
| Magic link rate limit | Done   | throttle:10,1 |
| First admin user      | Done   | DatabaseSeeder |
| Deployment            | TBD    | cPanel or VPS; no fly.toml yet |
| .env R2 & Maps        | Doc    | R2_SETUP.md + .env.example |
| Romanian UI           | Partial| APP_LOCALE=ro; many strings still to translate |
| Templates             | Done   | Placeholders added |
| Director/CUI          | N/A    | Use contact_person / contact_phone |
| Spatie                | Done   | Roles and middleware configured |
| Tests                 | OK     | Update for admin vs school_manager |
