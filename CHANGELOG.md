# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added
- Multi-hospital platform: hospital self-registration with platform-admin approval, staff
  invitations, roles (platform admin, hospital admin, doctor, echo technician).
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

### Changed
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
