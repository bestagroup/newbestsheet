// Dependency-free interaction tests. DOM/chart doubles do not replace browser visual QA.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const vm = require('node:vm');
const source = readFileSync('public/assets/js/pages/dashboard-professional.js', 'utf8');

class Element {
    constructor(text = '') {
        this.textContent = text; this.value = ''; this.checked = false; this.hidden = false;
        this.dataset = {}; this.attributes = {}; this.listeners = {}; this.children = {};
        this.classList = {add() {}};
    }
    querySelector(key) { return this.children[key] || null; }
    querySelectorAll(key) { return this.children[key] || []; }
    setAttribute(key, value) { this.attributes[key] = value; }
    addEventListener(key, listener) { this.listeners[key] = listener; }
    fire(key, data = {}) { this.listeners[key]?.({preventDefault() {}, ...data}); }
    focus() { this.focused = true; }
}
function setup({hasChart = true, hash = ''} = {}) {
    const list = new Element();
    for (const key of ['list-search', 'kind-filter', 'exact-search', 'result-count', 'clear-search', 'search-empty']) {
        list.children[`[data-${key}]`] = new Element();
    }
    const rows = ['شرکت کیان ۱۲۳ پرداخت', 'کیان شرکت ۱۲۳ قرارداد', 'شرکت بهار ۴۵۶ پرداخت'].map(text => new Element(text));
    rows.forEach((row, i) => { row.dataset.kind = i === 1 ? 'قرارداد' : 'پرداخت'; });
    list.children['[data-search-row]'] = rows;
    const doc = new Element(), shell = new Element(), ids = {};
    const tabs = ['finance', 'portfolio'].map(key => {
        const tab = new Element(); tab.dataset.domainTab = key; tab.hash = `#domain-${key}`; tab.parentElement = new Element();
        ids[`domain-${key}`] = new Element(); ids[`domain-chart-${key}`] = new Element(); ids[`domain-chart-${key}`].parentElement = new Element();
        doc.children[`[data-chart-table="${key}"]`] = new Element(); doc.children[`[data-chart-status="${key}"]`] = new Element();
        return tab;
    });
    doc.children['[data-searchable-list]'] = [list]; doc.children['[data-domain-tab]'] = tabs;
    doc.children['[data-exact-number]'] = [new Element('12345678901234567890.1234')]; doc.children['.dashboard-shell'] = shell;
    doc.getElementById = id => ids[id] || null;
    const calls = [], events = {};
    const Chart = class { constructor(canvas, config) { calls.push(config); } resize() {} };
    const window = {location: {hash}, addEventListener: (key, callback) => { events[key] = callback; }, matchMedia: () => ({matches: true})};
    if (hasChart) window.Chart = Chart;
    const context = {window, document: doc, Chart, history: {replaceState: (_, __, value) => { window.location.hash = value; }}, getComputedStyle: () => ({getPropertyValue: () => '#647584'})};
    vm.runInNewContext(source, context);
    const api = window.BestsheetDashboard;
    api.init({finance: {type: 'bar', labels: ['مهر'], data: ['12345678901234567890.1234'], unit: 'ریال'}, portfolio: {type: 'doughnut', labels: ['فعال'], data: ['0'], unit: 'تعداد'}});
    return {api, list, rows, tabs, ids, calls, doc, window, events};
}
test('Persian/Arabic characters, digits and separators normalize consistently', () => {
    const {api} = setup();
    assert.equal(api.normalize('  شركت كيـان ١٢٣ '), 'شرکت کیـان 123');
    assert.equal(api.normalize('سرمایه‌گذاری ۱٬۲۳۴'), 'سرمایه گذاری 1234');
});
test('exact numeric formatting never rounds large integers or fractional digits', () => {
    const {api, calls} = setup();
    assert.equal(api.exactNumber('-12345678901234567890.1234'), '-۱۲٬۳۴۵٬۶۷۸٬۹۰۱٬۲۳۴٬۵۶۷٬۸۹۰٫۱۲۳۴');
    assert.equal(calls[0].options.plugins.tooltip.callbacks.label({dataIndex: 0}), 'مهر: ۱۲٬۳۴۵٬۶۷۸٬۹۰۱٬۲۳۴٬۵۶۷٬۸۹۰٫۱۲۳۴ ریال');
    assert.equal(calls[0].options.animation, false);
});
test('multiword search, contiguous phrase, kind, empty state and clear compose', () => {
    const {list, rows} = setup();
    const input = list.querySelector('[data-list-search]'), exact = list.querySelector('[data-exact-search]'), kind = list.querySelector('[data-kind-filter]');
    input.value = 'شركت كيان ١٢٣'; input.fire('input');
    assert.deepEqual(rows.map(row => row.hidden), [false, false, true]);
    exact.checked = true; exact.fire('change');
    assert.deepEqual(rows.map(row => row.hidden), [false, true, true]);
    kind.value = 'قرارداد'; kind.fire('change');
    assert.ok(rows.every(row => row.hidden));
    assert.equal(list.querySelector('[data-search-empty]').hidden, false);
    list.querySelector('[data-clear-search]').fire('click');
    assert.equal(input.value, ''); assert.equal(input.focused, true);
    assert.deepEqual(rows.map(row => row.hidden), [true, false, true]);
});
test('accessible tabs initialize from hash, navigate RTL and lazily create charts', () => {
    const {tabs, ids, calls, window, events} = setup({hash: '#domain-portfolio'});
    assert.equal(ids['domain-finance'].hidden, true);
    assert.equal(tabs[1].attributes['aria-selected'], 'true');
    assert.equal(calls.length, 1); assert.equal(calls[0].type, 'doughnut');
    tabs[1].fire('keydown', {key: 'ArrowRight'});
    assert.equal(tabs[0].focused, true); assert.equal(ids['domain-finance'].hidden, false);
    assert.equal(calls.length, 2);
    tabs[0].fire('keydown', {key: 'End'}); assert.equal(tabs[1].attributes['aria-selected'], 'true');
    tabs[1].fire('keydown', {key: 'Home'}); assert.equal(tabs[0].attributes['aria-selected'], 'true');
    window.location.hash = '#domain-portfolio'; events.hashchange();
    assert.equal(ids['domain-finance'].hidden, true); assert.equal(calls.length, 2);
});
test('unavailable chart library exposes exact table and preserves tab interaction', () => {
    const {doc, ids, tabs} = setup({hasChart: false});
    assert.equal(doc.querySelector('[data-chart-table="finance"]').attributes.open, '');
    assert.equal(ids['domain-chart-finance'].parentElement.hidden, true);
    tabs[1].fire('click'); assert.equal(ids['domain-portfolio'].hidden, false);
});
