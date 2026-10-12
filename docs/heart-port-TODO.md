# Heart → Echo Portal port: TODO for the next agents

Goal: port every useful part and piece of logic from `/home/cheth/xyz/os/heart` (Mehr clinical system) into
this Laravel app, then retire heart. Plan: `~/.claude/plans/what-is-heart-project-lexical-raccoon.md`.

## Done (branch `worktree-heart-port-2`, not yet merged to main or pushed)
- Generic config-driven procedures (`config/procedures/*.php`, `App\Support\Procedures`, `ProcedureStudy`,
  `procedures/form|show` views) for angiography, angioplasty, rhc, peripheral, intervention and discharge (rtl).
- "New Study" dropdown in the nav listing echo plus every procedure.
- Nurse and QualityControl roles; heart demo data (`HeartDemoSeeder`, `app:seed-heart-demo`).
- Extraction tools in `tools/heart/` (see their README).
- Stress test (`std.htm`) intentionally skipped: it has only DB-lookup combos and no clinical fields.

## In progress: four branches, each with a WIP commit; the agents were stopped mid-task
All four branched from `2bf8432`, so none contains `c9a555a` (demo data). Each worktree lives at
`.claude/worktrees/<name>` on branch `worktree-<name>`. **Nothing in these branches has been tested.** Finish,
test, then merge into `worktree-heart-port-2`.

| Worktree | Test DB | Where it stopped | Remaining |
|---|---|---|---|
| heart-admissions | echo_test_a | Controllers, models, migrations, requests and tests written; was writing views | Finish the `admissions/`, `patients/`, `wards/` views; admission select on procedure forms; linked studies on the admission page; procedure drafts in the Worklist; translations; gate |
| heart-eprescription | echo_test_b | Models, migrations, controllers, policy, seeder and views written | Tests (incl. hospital isolation); discharge-summary integration; QR printable view; translations; gate |
| heart-import | echo_test_c | `config/heart.php`, `heart` DB connection, `app/Support/HeartImport/*` | The two artisan commands `heart:import --hospital= [--dry-run] [--only=]` and `his:import-admissions`; tests against sample tables; README/.env.example |
| heart-reports | echo_test_d | `app/Reports/*` narrative builders and `procedures/report.blade.php` | Run the procedure tests; `/dashboard/statistics` registry page plus nav link; tests; translations; gate |

### Brief per branch
- **admissions**: per-hospital wards; admissions UI with length of stay; extra patient demographics from
  `patient.htm` (encrypt PII); ward patients page and nav link; pick an admission on procedure forms; list
  linked studies on the admission page; procedure drafts in the Worklist.
- **eprescription** (heart `ep/`): drug catalog plus demo seeder; prescriptions (draft → signed by a doctor);
  drug-search JSON endpoint; printable view with QR code; discharge-summary integration; prescriptions on the
  patient page.
- **import**: idempotent `heart:import` from a `heart` MySQL connection (`HEART_DB_*` env), keyed on `legacy_id`.
  It maps `blank` codes to null, honours `legacy` renames, maps heart's `echo` table to `echo_studies`, and never
  imports users. `his:import-admissions` reads from SQL Server (`HIS_DB_*` env) with parameterized queries. Never
  copy heart's hard-coded credentials (`include/connect.php`, `hisConnect.php`, `workflow.php`).
- **reports**: port heart's Handlebars templates (`reports/templates`) to narrative Blade reports, keeping the
  generic fallback; a per-hospital registry statistics page using inline SVG/CSS bars.

## Rules
- Run PHP inside docker from a worktree:
  `docker compose -f ../../../compose.yaml exec -T -e DB_DATABASE=<test db> -w /var/www/html/.claude/worktrees/<name> app <cmd>`
- Gate: `vendor/bin/pest`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse --memory-limit=1G`.
- Every new clinical model uses `BelongsToHospital`, with a test proving another hospital can't read or modify it.
- Every literal `__('...')` needs entries in `lang/{fa,ar,tr,es,fr,de}.json` (LocalizationTest).
- Don't run migrate or seed against the dev DB from agent worktrees; it holds the user's own data (`chth` hospital).
- Route binds go in `AppServiceProvider`, because production caches routes.
- Don't commit `.claude/`. Commits end with `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

## After all four are merged into worktree-heart-port-2
1. Full gate.
2. `git merge --no-ff` into main, then push.
3. Refresh the local demo: `migrate`, `npm run build`, `app:seed-heart-demo`. Then smoke-test in a browser,
   including the Alpine `show_if` toggling, which has never been checked in a real browser.

## Later / needs a decision from the user
- Split permissions per procedure (all procedures currently reuse `studies.*`).
- Ward-scoped users from heart's role JSON.
- Heart's patient self-service dashboard (`dashboard.php`, where patients log in by national ID): not ported
  because it is insecure and of low value. Confirm with the user before porting.
