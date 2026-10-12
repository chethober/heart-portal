<div align="center">

# Echo Portal

**Open-source, multi-hospital echocardiography reporting.**

Technicians enter measurements once, the platform grades them against guideline cut-offs,
doctors sign the report, and every step is audited.

[![CI](https://github.com/chethober/echocardiography/actions/workflows/ci.yml/badge.svg)](https://github.com/chethober/echocardiography/actions/workflows/ci.yml)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](LICENSE)

</div>

---

## Features

- **Structured echo form** — LV, RV, atria, all four valves, IVC/PAP and pericardium, 17-segment
  regional wall motion, live report preview, voice input, Jalali (Solar Hijri) date picker and
  per-user browser drafts across multiple patient tabs.
- **Automatic grading** — BSA (Mosteller), LVEDVI/LVEDDI, sex-specific LV size, LV systolic
  function from EF, LVH from IVS, diastolic function (e′, E/e′, E/A, TR velocity), RV size and
  function, LA/RA size, SPAP from TRG + estimated RAP with PH grading, and aortic dilation by
  segment. Every automatic value can be overridden and the report explains how it was derived.
- **Signed, versioned reports** — studies start as drafts; doctors sign them; signed reports can
  only be *amended* with a reason, and every signed version is preserved with the signature it
  was signed with. Bilingual printable report with the hospital's name and logo.
- **Multi-hospital (multi-tenant)** — hospitals self-register, are approved by a platform admin,
  and invite their own doctors and echo technicians. Every query is scoped to the user's hospital.
- **Security** — encrypted national ID and phone number, authenticator-app 2FA (enforceable),
  role-based permissions, private signature storage, append-only audit log protected by a
  database trigger.
- **Multilingual interface** — English, Persian, Arabic, Turkish, Spanish, French and German,
  with right-to-left layout for Persian and Arabic. The language follows the browser and can be
  changed from any page. The echo form and printed report stay in their clinical language.

## Roles

| Role | Can |
|---|---|
| Echo technician | record studies, edit drafts, search patients, view/print reports |
| Doctor | everything a technician can + sign and amend reports |
| Hospital admin | invite/manage staff, reset 2FA, view reports and the audit log, delete drafts |
| Platform admin | approve, suspend and reactivate hospitals (no access to patient data) |

## Quick start (Docker)

Requirements: Docker with Compose, Node.js 20+ (for building the front-end assets).

```sh
git clone https://github.com/chethober/echocardiography.git
cd echocardiography
cp .env.example .env

docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

npm install && npm run build
```

Open <http://localhost:8000>. Outgoing mail (invitations, verification) is captured by
Mailpit at <http://localhost:8025>.

With `APP_ENV=local` the seeder creates demo accounts, all with the password `password`:

| Email | Role |
|---|---|
| `admin@echo.test` | Platform admin |
| `hadmin@echo.test` | Hospital admin (Demo Heart Hospital) |
| `doctor@echo.test` | Doctor |
| `tech@echo.test` | Echo technician |
| `nurse@echo.test` | Nurse |
| `qc@echo.test` | Quality control |

It also adds six fictional patients with admissions and coronary angiographies recorded in the
heart system's fields (drafts, signed and amended). On a demo deployment, add the same data to the
demo hospital with `php artisan app:seed-heart-demo`; it is safe to run again.

Two-factor login is enforced by default; set `TWO_FACTOR_ENFORCED=false` in `.env` to try the
demo accounts without an authenticator app.

### Production

- Set `APP_ENV=production`, `APP_DEBUG=false`, a real `APP_URL`, mail settings and database
  credentials, and serve over HTTPS.
- Create the first platform administrator interactively:
  `php artisan app:create-platform-admin`
- Keep `TWO_FACTOR_ENFORCED=true`.
- Back up the database **and** `APP_KEY` — encrypted fields cannot be read without the key.

## Configuration

| Variable | Default | Purpose |
|---|---|---|
| `TWO_FACTOR_ENFORCED` | `true` | Require every user to enrol an authenticator app |
| `TWO_FACTOR_ISSUER` | `APP_NAME` | Name shown in authenticator apps |
| `ECHO_DISPLAY_TIMEZONE` | `Asia/Tehran` | Timezone for signing times and audit entries |
| `APP_LOCALE` | `en` | Interface language when the browser asks for none we support |
| `APP_SOURCE_URL` | this repository | "Source code" link in the footer (AGPL §13) — point it at your fork if you modify the code |

Clinical fields are whitelisted in [`config/echo.php`](config/echo.php).

To add a language, add it to `available_locales` in [`config/app.php`](config/app.php) (and to
`rtl_locales` if it is right-to-left), then create `lang/<code>.json` plus the `auth`, `passwords`,
`pagination` and `validation` files under `lang/<code>/`. `tests/Feature/LocalizationTest.php` fails
until every interface string is translated.

## Architecture

- **Laravel 13**, PHP 8.4, **PostgreSQL 16**, Blade + Tailwind (Breeze), Alpine.js.
- **Tenancy** — a `Hospital` is the tenant. Models with patient data use the
  `BelongsToHospital` trait, which filters every query to the current hospital and fails closed
  (returns nothing) when no hospital context exists. See `app/Models/Concerns` and
  `app/Models/Scopes`.
- **Clinical pages** — the echo form, patient history and report live in
  `resources/views/{studies,patients}/*.php` (plain PHP views). Their data comes from
  controllers in `app/Http/Controllers/Clinical`.
- **Calculations** — `app/Services/EchoCalculations.php` derives every automatic value;
  `app/Support/ReportHtml.php` sanitizes report HTML built from free text.
- **Workflow** — `EchoStudy::finalize()` / `amend()` and `EchoStudyPolicy`; signed snapshots in
  `echo_study_versions`.
- **Audit** — `App\Support\Audit::record()`; `audit_logs` rejects UPDATE/DELETE at the database level.

## Development

```sh
docker compose exec app ./vendor/bin/pest                               # tests (uses the echo_test database)
docker compose exec app ./vendor/bin/pint                               # code style
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=1G  # static analysis
npm run dev                                                             # Vite dev server
```

Create the test database once: `docker compose exec pgsql createdb -U echo echo_test`.

## Contributing

Contributions are welcome — please read [CONTRIBUTING.md](CONTRIBUTING.md) and the
[Code of Conduct](CODE_OF_CONDUCT.md). **Never include real patient data** in issues, pull
requests, screenshots or test fixtures.

Found a vulnerability? Please follow [SECURITY.md](SECURITY.md) instead of opening an issue.

## Medical disclaimer

Echo Portal is reporting software. Automatic grading follows published reference ranges but is
an aid, not a diagnosis: the signing physician is responsible for every report. The software is
provided without warranty; see the license.

## License

Copyright © 2026 chethober and contributors.

Licensed under the [GNU Affero General Public License v3.0 or later](LICENSE). If you run a
modified version as a network service, you must make its source code available to its users.
Bundled third-party components are listed in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
