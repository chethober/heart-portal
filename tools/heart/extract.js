// Usage: node extract.js <heartDir> <form.htm>  -> JSON field spec on stdout
// Run from the repository root; see tools/heart/README.md.
const fs = require('fs'), vm = require('vm'), path = require('path');
const [heart, form] = process.argv.slice(2);
const src = f => fs.readFileSync(path.join(heart, f), 'utf8').replace(/^﻿/, '');
const html = src(form);
const inline = [...html.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(m => m[1]).join('\n');
const anyStub = new Proxy(function () {}, { get: (t, k) => k === Symbol.toPrimitive ? () => '' : anyStub, apply: () => anyStub, construct: () => anyStub });
const ctx = { console, alert: m => { throw new Error('alert: ' + m); }, $: anyStub, jQuery: anyStub,
  document: anyStub, location: { search: '', href: '' }, navigator: { userAgent: '' }, setTimeout: () => 0, today: () => '1400/01/01' };
ctx.window = ctx;
vm.createContext(ctx);
vm.runInContext('Array.prototype.contains = function (x) { return this.indexOf(x) >= 0; };', ctx);
for (const f of ['js/cal.js', 'js/popup.js', 'js/clock.js', 'js/input.js', 'js/config.js', 'js/fieldDef.js']) vm.runInContext(src(f), ctx, { filename: f });
vm.runInContext(`
  var __out = [], __chap = '', __sec = '';
  var __sc = Q.prototype.startchapter, __ss = Q.prototype.startsection, __es = Q.prototype.endsection;
  Q.prototype.startchapter = function (o) { __chap = (typeof o === 'string' ? o : (o && o.caption) || '').replace(/<[^>]*>/g, '').trim(); __sec=''; return __sc.apply(this, arguments); };
  Q.prototype.startsection = function (o) { __sec = (typeof o === 'string' ? o : (o && o.caption) || ''); return __ss.apply(this, arguments); };
  Q.prototype.endsection = function () { __sec = ''; return __es.apply(this, arguments); };
  Q.prototype.write = function () {
    __out = { table: this.tablename, pk: this.pk, fields: this.Allfields.map(function (f) { return f; }) };
  };
  var __push = Array.prototype.push;
`, ctx);
// tag each field with chapter/section at push time
vm.runInContext(`
  var __origAdd = Q.prototype.addField;
  Q.prototype.addField = function (o) {
    var before = this.Allfields.length, r = __origAdd.apply(this, arguments);
    for (var i = before; i < this.Allfields.length; i++) { this.Allfields[i].__chapter = __chap; this.Allfields[i].__section = __sec; }
    return r;
  };
`, ctx);
vm.runInContext(inline, ctx, { filename: form });
const out = vm.runInContext('__out', ctx);
const pick = f => ({ name: f.name, type: f.type, label: f.label, datatype: f.datatype, datasize: f.datasize,
  values: f.valuelist && f.valuelist.length ? f.valuelist.map((v, i) => [v, (f.labellist || [])[i]]) : undefined,
  linked: f.LinkedFieldID ? [f.LinkedFieldID, f.LinkedFieldValue] : undefined, chapter: f.__chapter, section: f.__section || undefined });
console.log(JSON.stringify({ table: out.table, pk: out.pk, fields: out.fields.filter(f => f.name).map(pick) }, null, 1));
