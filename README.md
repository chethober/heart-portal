<div align="center">

# Heart Portal

**Open-source, multi-hospital cardiology reporting — echo lab, cath lab, admissions and e-prescriptions.**

Technicians enter measurements once, the platform grades them against guideline cut-offs,
doctors sign the report, and every step is audited.

[![CI](https://github.com/chethober/heart-portal/actions/workflows/ci.yml/badge.svg)](https://github.com/chethober/heart-portal/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

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
- **Cath lab and discharge forms** — coronary angiography, coronary angioplasty (PCI), right heart
  catheterization, peripheral angiography, cardiac intervention and discharge summary, ported from
  the legacy *heart* system. They follow the same draft → signed → amended workflow as echo
  studies (the discharge summary is signed by the intern, then the resident, then the attending
  physician, each of whom may correct it before signing); angiography, angioplasty and discharge reports are written up as clinical narratives.
- **Admissions and wards** — patient record with demographics and contact details, admissions
  (ward, type, arrival, referrer, attending physician) and discharge, a live ward census, and
  import of admissions from the hospital information system.
- **E-prescription** — a shared drug catalog that hospitals can extend; prescriptions drafted by
  clinical staff and signed by a doctor, discharge medications on the discharge summary, and a
  QR code on the printout that opens a public verification page (no patient data).
- **Registry statistics** — studies and patients per form and per month, signed vs. draft, and
  charted findings for a chosen period.
- **Multi-hospital (multi-tenant)** — hospitals self-register, are approved by a platform admin,
  and invite their own doctors, echo technicians, nurses and quality-control staff. Every query is scoped to the user's hospital.
- **Security** — encrypted national ID and phone number, authenticator-app 2FA (enforceable),
  role-based permissions, private signature storage, append-only audit log protected by a
  database trigger.
- **Multilingual interface** — English, Persian, Arabic, Turkish, Spanish, French and German,
  with right-to-left layout for Persian and Arabic. The language follows the browser and can be
  changed from any page. Procedure reports (cath lab and discharge) print their template, headings
  and labels in the interface language; the sentences written from the form stay in the form's
  clinical language. The echo form and printed report stay in their clinical language.

## Roles

| Role | Can |
|---|---|
| Nurse / technician | record studies, admissions and patient details, draft prescriptions, search patients, view/print reports |
| Doctor | everything a nurse or technician can + sign and amend reports, sign and cancel prescriptions |
| Hospital admin | invite/manage staff, reset 2FA, manage wards and the drug catalog, view reports and the audit log, delete drafts |
| Platform admin | approve, suspend and reactivate hospitals (no access to patient data) |

Access to each clinical form (echo, admissions and every cath-lab form) is also set per staff
member, like heart's per-form access flags: on the Staff page, **Form access** lets a hospital
admin choose what each member may view, record, edit and sign. A new member starts with
everything their role allows, and the role caps what can be granted (only doctors sign).

## Quick start (Docker)

Requirements: Docker with Compose, Node.js 20+ (for building the front-end assets).

```sh
git clone https://github.com/chethober/heart-portal.git
cd heart-portal
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
| `nurse@echo.test` | Nurse / technician |

It also adds six fictional patients with admissions and coronary angiographies recorded in the
heart system's fields (drafts, signed and amended). On a demo deployment `app:seed-demo` adds the
same data (or run `php artisan app:seed-heart-demo` on its own); it is safe to run again.

Two-factor login is enforced by default; set `TWO_FACTOR_ENFORCED=false` in `.env` to try the
demo accounts without an authenticator app.

### Production

- Set `APP_ENV=production`, `APP_DEBUG=false`, a real `APP_URL`, mail settings and database
  credentials, and serve over HTTPS.
- Create the first platform administrator interactively:
  `php artisan app:create-platform-admin`
- Keep `TWO_FACTOR_ENFORCED=true`.
- For a demo deployment, `php artisan app:seed-demo` creates the demo hospital and accounts with
  random passwords, printed once (re-run to reset them), and adds the heart demo patients.
- Back up the database **and** `APP_KEY` — encrypted fields cannot be read without the key.

## Configuration

| Variable | Default | Purpose |
|---|---|---|
| `TWO_FACTOR_ENFORCED` | `true` | Require every user to enrol an authenticator app |
| `TWO_FACTOR_ISSUER` | `APP_NAME` | Name shown in authenticator apps |
| `ECHO_DISPLAY_TIMEZONE` | `Asia/Tehran` | Timezone for signing times and audit entries |
| `APP_LOCALE` | `en` | Interface language when the browser asks for none we support |
| `APP_SOURCE_URL` | this repository | "Source code" link in the footer — point it at your fork if you modify the code |

Clinical fields are whitelisted in [`config/echo.php`](config/echo.php).

To add a language, add it to `available_locales` in [`config/app.php`](config/app.php) (and to
`rtl_locales` if it is right-to-left), then create `lang/<code>.json` plus the `auth`, `passwords`,
`pagination` and `validation` files under `lang/<code>/`. `tests/Feature/LocalizationTest.php` fails
until every interface string is translated.

## Importing from heart and the HIS

Heart Portal replaces the legacy *heart* system (Mehr clinical information system). Its data is
copied in with two re-runnable artisan commands; both take `--dry-run`, which reads and checks
everything and then rolls back.

```bash
# heart's MySQL database (HEART_DB_* in .env): patients, admissions, echo and procedure studies.
php artisan heart:import --hospital=<slug or id> [--only=patients,admissions,...] [--center=<id> | --all-centers] [--dry-run]

# Admissions from the hospital information system's SQL Server view (HIS_DB_* in .env).
php artisan his:import-admissions --hospital=<slug or id> [--since=1405/01/01] [--limit=500] [--dry-run]
```

- Rows are matched on `legacy_id` (heart's row id), admissions also on the HIS admission number, and
  patients on national ID within the hospital. Running an import again only applies what changed.
- Imported studies are signed history (status final, no signing user) with a version saying
  "Imported from heart". Studies amended or deleted in Heart Portal are never overwritten.
- heart's placeholder codes ("Missing", 9, -1) import as empty. heart's users are never imported:
  invite staff instead.
- A heart database holding several centers (`admit.centerId`) needs `--center` or `--all-centers`,
  so that one hospital's patients never land in another.
- Set `HIS_IMPORT_HOSPITAL` to run `his:import-admissions` from the scheduler (`HIS_IMPORT_CRON`,
  every 30 minutes by default).
- Set `HIS_LOOKUP_HOSPITAL` (defaults to `HIS_IMPORT_HOSPITAL`) to let that hospital's patient
  search look up a national ID or admission number in the HIS when it is not on record, as heart did.
- The app image includes `pdo_mysql`. The HIS needs the `pdo_sqlsrv` extension and Microsoft's
  ODBC driver, which are not in the image. Install them where the HIS import runs.
- Never reuse heart's connection settings files (`include/connect.php`, `hisConnect.php`): they
  hold plain-text credentials.

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
- **Procedures** — each heart form is a `ProcedureStudy` model listed in `app/Support/Procedures.php`,
  with its fields in `config/procedures/<key>.php` and, where it has one, a narrative report in
  `app/Reports`. Every procedure report prints in one shared template,
  `resources/views/procedures/report/document.blade.php` (hospital header, patient line, signature
  boxes), with the report's own body after it. Signed snapshots live in `procedure_study_versions`.
- **E-prescription** — `routes/prescriptions.php`, `Prescription` / `PrescriptionPolicy` (drafted by
  clinical staff, signed or cancelled by doctors; signed prescriptions are immutable) and
  `app/Support/Prescribing.php`.
- **Admissions and statistics** — `Admission`, `Ward` and `app/Support/RegistryStatistics.php`;
  the importers are in `app/Support/HeartImport`.
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

Heart Portal is reporting software. Automatic grading follows published reference ranges but is
an aid, not a diagnosis: the signing physician is responsible for every report. The software is
provided without warranty; see the license.

## License

Copyright © 2026 CHTH.

Licensed under the [MIT License](LICENSE).
Bundled third-party components are listed in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
