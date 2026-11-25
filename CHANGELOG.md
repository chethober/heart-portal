# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed
- Right heart catheterization form: the Registration tab is gone (an imported registry code is kept
  and still printed), a new Indication tab offers a dropdown (evaluation before heart transplant,
  for cause of pulmonary hypertension, before valve surgery, or other, which reveals a text field
  beside it), and Result and Recommendation each have their own tab.

### Added
- Multi-hospital platform: hospital self-registration with platform-admin approval, staff
  invitations, roles (platform admin, hospital admin, doctor, nurse / technician).
- Echo study form, patient history and printable report, ported from the original
  single-hospital application, with server-side validation of every field.
- `EchoCalculations` service for automatic grading (single source of truth for all reports).
- Draft → final → amended workflow with doctor signatures and preserved signed versions.
- Append-only audit log with an admin viewer.
- Authenticator-app two-factor login with recovery codes and admin reset.
- Encryption at rest for national ID and phone number.
- Public landing page and help center.
- Interface translations for Persian, Arabic, Turkish, Spanish, French and German, with a
  language switcher, browser-language detection and right-to-left layout for Persian and Arabic.
- Forms ported from the legacy *heart* system, under a New Study menu: coronary angiography,
  coronary angioplasty (PCI), right heart catheterization, peripheral angiography, cardiac
  intervention and discharge summary, with the same draft → signed → amended workflow as echo
  studies. Every form's report is generated as a clinical narrative, as heart printed it: the right
  heart catheterization report tabulates saturations and pressures by site and gives the
  haemodynamics (PVR calculated from mean PA pressure, PCWP and cardiac output when not recorded).
- The discharge summary is signed in three steps: intern (کارورز), resident (دستیار), then the
  attending physician (پزشک معالج), whose signature finalizes it. Each doctor may correct the
  summary before signing; each step's name, signature and date print in its box on the report.
- Doctors draw their signature on the profile page (mouse, finger or stylus); signature images are
  no longer uploaded. A doctor who signed more than one discharge step is printed once.
- One nurse / technician role for staff who record studies but do not sign them (the earlier
  technician and quality-control roles are merged into it).
- Patient record with demographics and contact details; admissions with ward, admission type,
  arrival, referrer and attending physician, and discharge. Until something is searched, the
  Patients page lists who is in hospital by ward. Hospital admins manage their wards.
- E-prescription: shared drug catalog (hospitals can add and withdraw drugs), prescriptions
  drafted by clinical staff and signed or cancelled by doctors, discharge medications on the
  discharge summary, and a QR code linking to a public verification page.
- Registry statistics page: studies and patients per form and month, and charted findings.
- `heart:import` and `his:import-admissions` artisan commands to copy data from heart's MySQL
  database and admissions from the hospital information system (re-runnable, with `--dry-run`).
- Production and public demo deployment (`compose.prod.yaml`, `compose.public.yaml`) and
  `app:seed-demo` / `app:seed-heart-demo` for demo hospitals, accounts and patients.
- Help center sections for the cath-lab forms, admissions and wards, e-prescriptions and
  statistics.

- Per-form access: hospital admins choose, for each staff member, which forms (echo, admissions,
  each cath-lab form) they may view, record, edit and sign, within what their role allows.

- Every study form (echo and the cath-lab forms) shares the same patient details, including the
  admission the study belongs to. Typing a registered patient's national ID fills in their details
  and admissions; a new or mistyped ID (wrong check digit) is flagged.
- Cath-lab forms keep several patients' unsaved drafts in tabs, saved in the browser as you type,
  like the echo form, with a Jalali date picker for dates.

- Final diagnosis picker from heart's ICD guide on the discharge summary and on admissions, with
  ICD-10 codes and criteria, printed on the discharge report, and a Final diagnosis guide in the
  help center. Heart's admission ICD codes are imported. Codes beyond heart's acute MI and angina
  items still need review by a clinician (config/diagnoses.php).

- Patient search by national ID, HIS admission number or name (Persian or Arabic ی/ک, spaces
  ignored), listing several matches with their latest admission. A national ID or admission number
  not on record is looked up live in the hospital information system and imported, for the hospital
  set in HIS_LOOKUP_HOSPITAL, as heart's search did.

- Registry CSV export on the Statistics page for hospital admins and quality control: one row per
  study of a form with the patient and admission, every field as shown on the form or as stored
  codes, filtered by date and status. Exports are recorded in the audit log.

### Changed
- Coronary angioplasty (PCI): the *Vessel Complication* fields of the three territories and the *PCI induced
  Complication* fields moved out of the territory tabs into their own tabs, *Comp* and *PCI R. Comp*,
  after the territory tabs; the yes/no fields of *Indication for PCI* are checkboxes (ticked = Yes).
- Coronary angioplasty (PCI): each territory's lesion fields (description of lesion through post
  procedural TIMI flow) appear only once a territory is chosen, stents after the first and the
  bifurcation fields (*Bifurcating lesion*) are folded behind dropdown buttons opened on demand.
- Coronary angiography: each vessel's (and graft's) *Vessel characteristics* fields are folded behind
  a dropdown button that reveals them on demand, showing how many are filled in.
- Cath-lab forms and their reports now look and work like the echo form and report: a section bar
  showing which sections are filled in, one section at a time, a live report preview beside the
  form, and the echo report's header, patient line, tables and signature.
- Every signed-in form, search and report now lives under `/dashboard`: the worklist, patient
  echos, staff, audit log and (for platform admins) hospitals are tabs of one dashboard, and the
  echo form, reports, help and profile moved to `/dashboard/...`. Old URLs redirect permanently.
- The worklist filters by status in one click, with per-status counts; tabs show how many drafts,
  pending invitations or hospitals awaiting approval need attention.
- The echo form (new, edit and amend) now opens inside the dashboard with its header, tabs and
  styling instead of the old standalone page; every form feature (patient tabs, section chips,
  live preview, drafts, voice input, Jalali dates, auto summary) is unchanged.
- `/dashboard` now opens the echo form; the worklist moved to `/dashboard/worklist` (old filter
  links redirect). The dashboard tabs and header row are gone: sections and their counts, the
  National ID search and Help are all in the top navigation.
- The detailed report (drafts to sign, signed and amended reports, earlier signed versions) opens
  inside the dashboard too, with print, edit/amend, sign and version history unchanged. The old
  standalone navbar, its stylesheet and the unused bundled fonts are removed, and the remaining
  form assets moved from `public/legacy/` to `public/echo-form/`.

### Security
- Report HTML built from free text is sanitized (fixes stored XSS present in the original app).
