// Usage: node to-config.js spec.json <key> <label> <dateField> <sourceForm> > config/procedures/<key>.php
// Run from the repository root; see tools/heart/README.md.
// Turns a heart form spec (from extract.js) into an Echo Portal procedure config.
const [specFile, key, label, dateField, sourceForm] = process.argv.slice(2);
const spec = require(require('path').resolve(specFile));

// Columns the procedure tables already use for workflow; clashing heart fields get a prefix.
const RESERVED = new Set(['id', 'hospital_id', 'patient_id', 'admission_id', 'created_by', 'reported_by', 'status',
  'finalized_at', 'amended_by', 'amended_at', 'amendment_reason', 'legacy_id', 'created_at', 'updated_at', 'deleted_at']);
// Heart fields that become relations instead of columns.
const SKIP = new Set(['nationalcode', 'admitId']);
// Placeholder choices that mean "not answered"; stored as null instead of a code.
const BLANK = /^\W*(missing|select|please select|بی جواب)\W*$/i;
const FARSI = { 'خیر': 'No', 'بلی': 'Yes' };
const TYPES = { radio: 'choice', combo: 'choice', yesno: 'choice', checkbox: 'boolean', number: 'number',
  text: 'text', multiline: 'multiline', shamsidate: 'date', time: 'time' };

const renamed = {};
const column = name => RESERVED.has(name) ? (renamed[name] = `${key}_${name}`) : name;

const sections = [];
for (const f of spec.fields) {
  if (f.type === 'auto' || SKIP.has(f.name)) continue;
  const type = TYPES[f.type];
  if (!type) throw new Error(`Unmapped type ${f.type} for ${f.name}`);
  const title = /[^\x00-\x7f]/.test(f.chapter || '') || !f.chapter ? 'Registration' : f.chapter.replace(/_/g, ' ');
  let section = sections[sections.length - 1];
  if (!section || section.title !== title) sections.push(section = { title, fields: [] });

  const out = { name: column(f.name), type, label: (f.label || f.name).trim() };
  if (out.name !== f.name) out.legacy = f.name;
  if (f.section) out.group = f.section.trim();
  if (type === 'choice') {
    out.options = []; out.blank = [];
    for (const [value, text] of f.values || []) {
      const l = String(text ?? value).trim();
      if (BLANK.test(l)) out.blank.push(String(value)); else out.options.push([String(value), FARSI[l] || l]);
    }
    if (!out.blank.length) delete out.blank;
  }
  if (f.linked) out.show_if = [column(f.linked[0]), String(f.linked[1])];
  section.fields.push(out);
}

const q = s => `'${String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
const field = f => {
  const parts = [`'type' => ${q(f.type)}`, `'label' => ${q(f.label)}`];
  if (f.group) parts.push(`'group' => ${q(f.group)}`);
  if (f.options) parts.push(`'options' => [${f.options.map(([v, l]) => `${q(v)} => ${q(l)}`).join(', ')}]`);
  if (f.blank) parts.push(`'blank' => [${f.blank.map(q).join(', ')}]`);
  if (f.show_if) parts.push(`'show_if' => [${q(f.show_if[0])}, ${q(f.show_if[1])}]`);
  if (f.legacy) parts.push(`'legacy' => ${q(f.legacy)}`);
  return `                ${q(f.name)} => [${parts.join(', ')}],`;
};

console.log(`<?php

/*
|--------------------------------------------------------------------------
| ${label} fields
|--------------------------------------------------------------------------
|
| Generated from the heart system's ${sourceForm} form (the fields its form
| builder sends to the database). The list drives the ${key}_studies
| migration, validation, mass assignment, the form and the report.
|
| Column names match heart's ${spec.table} table so its data can be imported
| as is; "legacy" names a heart column that was renamed here. Choice codes are
| heart's codes. "blank" lists heart's placeholder codes (missing, select),
| which are stored as null. "show_if" shows a field only while another field
| has the given value.
|
*/

return [
    'label' => ${q(label)},
    'date_field' => ${q(dateField)},
    'sections' => [
${sections.map(s => `        [
            'title' => ${q(s.title)},
            'fields' => [
${s.fields.map(field).join('\n')}
            ],
        ],`).join('\n')}
    ],
];`);
console.error('sections', sections.length, 'fields', sections.reduce((n, s) => n + s.fields.length, 0), 'renamed', renamed);
