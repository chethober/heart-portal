# Porting heart forms

Heart (the old Mehr cardiology system) defines each form in JavaScript: `new Q(table, pk, ...)`
followed by `addField` / `startradio` / ... calls. Its form builder (`js/input.js`) expands shared
field collections, "Others" boxes, Jalali date columns (`DateOf_<name>`) and so on, and the
resulting `Allfields` list is what heart's setup turns into database columns.

`extract.cjs` runs heart's own scripts in a Node sandbox and records that list, so the spec matches
heart's real column names. `to-config.cjs` turns the spec into `config/procedures/<key>.php`.

```sh
node tools/heart/extract.cjs /path/to/heart angiography.htm > /tmp/angiography.json
node tools/heart/to-config.cjs /tmp/angiography.json angiography 'Coronary Angiography' \
    DateOf_Date_of_angiography angiography.htm > config/procedures/angiography.php
```

Then add the model (see `App\Models\AngiographyStudy`), its key in `App\Support\Procedures::MODELS`,
a `<key>_studies` migration that calls `Procedures::addColumns()`, a factory and tests.

Mapping rules (in `to-config.cjs`):

- heart's placeholder choices (Missing, -- select --) are dropped and listed under `blank`; they are
  stored as null. "Unknown" is kept, as it is a clinical answer.
- fields that clash with workflow columns (`status`, ...) are prefixed with the procedure key, and
  `legacy` keeps heart's name for importing data.
- `nationalcode` becomes the patient, `admitId` the admission link; heart's `auto_*` audit columns
  are dropped (Heart Portal's audit log replaces them).

The create migration reads the config when it runs, so once a procedure's table exists in any
database, add or change its fields with a new migration (and edit the config to match) rather than
regenerating the config and expecting the old migration to pick it up.
