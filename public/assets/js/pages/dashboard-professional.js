/* Filter the displayed records locally; the server owns permissions. */
window.BestsheetDashboard = (() => {
    'use strict';
    const normalize = value => String(value ?? '').normalize('NFKC').toLocaleLowerCase('fa-IR')
        .replace(/[يى]/g, 'ی').replace(/ك/g, 'ک').replace(/[۰-۹]/g, c => '۰۱۲۳۴۵۶۷۸۹'.indexOf(c))
        .replace(/[٠-٩]/g, c => '٠١٢٣٤٥٦٧٨٩'.indexOf(c)).replace(/[٬,]/g, '')
        .replace(/\u200c/g, ' ').replace(/\s+/g, ' ').trim();
    const exactNumber = value => {
        const parts = String(value).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '٬');
        return parts.join('٫').replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
    };
    const colors = ['#183b56', '#16866b', '#2596be', '#d49a32', '#9b6ab0', '#d45b67', '#4d908e', '#64748b'];
    function init(sections) {
        document.querySelector('.js-dashboard-refresh')?.addEventListener('click', () => window.location.reload());
        document.querySelectorAll('[data-searchable-list]').forEach(list => {
            const input = list.querySelector('[data-list-search]');
            const select = list.querySelector('[data-kind-filter]');
            const exact = list.querySelector('[data-exact-search]');
            const counter = list.querySelector('[data-result-count]');
            const items = [...list.querySelectorAll('[data-search-row]')];
            const texts = items.map(item => normalize(item.textContent));
            const apply = () => {
                const query = normalize(input.value);
                const tokens = exact.checked ? [query] : query.split(' ').filter(Boolean);
                items.forEach((item, index) => item.hidden = (!!select?.value && item.dataset.kind !== select.value)
                    || !tokens.every(token => texts[index].includes(token)));
                const count = items.filter(item => !item.hidden).length;
                counter.textContent = `${exactNumber(count)} از ${exactNumber(items.length)} مورد`;
                const empty = list.querySelector('[data-search-empty]');
                if (empty) empty.hidden = count > 0 || items.length === 0;
            };
            input.addEventListener('input', apply);
            list.querySelector('[data-clear-search]').addEventListener('click', () => {
                input.value = '';
                apply();
                input.focus();
            });
            select?.addEventListener('change', apply);
            exact.addEventListener('change', apply); apply();
        });
        document.querySelector('.dashboard-shell')?.classList.add('is-enhanced');
        document.querySelectorAll('[data-exact-number]').forEach(cell => { cell.textContent = exactNumber(cell.textContent); });
        const charts = new Map();
        const renderChart = key => {
            if (charts.has(key)) { charts.get(key).resize(); return; }
            const chart = sections[key];
            const canvas = document.getElementById(`domain-chart-${key}`);
            if (!canvas || !chart) return;
            const showTable = () => {
                document.querySelector(`[data-chart-table="${key}"]`)?.setAttribute('open', '');
                const status = document.querySelector(`[data-chart-status="${key}"]`);
                if (status) status.textContent = 'نمودار بارگذاری نشد؛ مقادیر دقیق در جدول در دسترس است.';
                canvas.parentElement.hidden = true;
            };
            if (!window.Chart) { showTable(); return; }
            try { createChart(key, chart, canvas); }
            catch (error) {
                // A chart failure must not disable navigation or hide source values.
                window.Chart.getChart?.(canvas)?.destroy();
                showTable();
            }
        };
        const tabs = [...document.querySelectorAll('[data-domain-tab]')];
        const selectTab = (tab, focus = false) => {
            tabs.forEach(item => {
                const active = item === tab;
                item.setAttribute('aria-selected', String(active));
                item.tabIndex = active ? 0 : -1;
                document.getElementById(`domain-${item.dataset.domainTab}`).hidden = !active;
            });
            if (focus) tab.focus();
            renderChart(tab.dataset.domainTab);
        };
        tabs[0]?.parentElement.setAttribute('role', 'tablist');
        tabs.forEach((tab, index) => {
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-controls', `domain-${tab.dataset.domainTab}`);
            const panel = document.getElementById(`domain-${tab.dataset.domainTab}`);
            panel.setAttribute('role', 'tabpanel'); panel.tabIndex = 0;
            tab.addEventListener('click', event => {
                event.preventDefault(); selectTab(tab);
                history.replaceState(null, '', tab.hash);
            });
            tab.addEventListener('keydown', event => {
                const offsets = {ArrowLeft: 1, ArrowRight: -1};
                let next;
                if (event.key in offsets) next = (index + offsets[event.key] + tabs.length) % tabs.length;
                if (event.key === 'Home') next = 0;
                if (event.key === 'End') next = tabs.length - 1;
                if (next !== undefined) { event.preventDefault(); selectTab(tabs[next], true); }
            });
        });
        const hashTab = () => tabs.find(tab => tab.hash === window.location.hash);
        if (tabs.length) selectTab(hashTab() || tabs[0]);
        window.addEventListener('hashchange', () => { if (hashTab()) selectTab(hashTab()); });
        function createChart(key, chart, canvas) {
            const financial = key === 'finance';
            const horizontal = chart.type === 'bar' && !financial;
            const type = financial ? 'line' : chart.type;
            const values = chart.data.map(value => Number(value) / (financial ? 1e9 : 1));
            const ink = getComputedStyle(document.querySelector('.dashboard-shell')).getPropertyValue('--dash-muted').trim();
            charts.set(key, new Chart(canvas, {
                type,
                data: {labels: chart.labels, datasets: [{label: financial ? 'پرداخت (میلیارد ریال)' : chart.unit,
                    data: values, backgroundColor: financial ? 'rgba(22,134,107,.12)' : chart.labels.map((_, index) => colors[index % colors.length]),
                    borderColor: financial ? '#16866b' : 'transparent', borderWidth: financial ? 2 : 0,
                    fill: financial, tension: 0, pointRadius: financial ? 4 : 0, borderRadius: type === 'bar' ? 5 : 0}]},
                options: {color: ink, font: {family: 'IRANSans, sans-serif'}, responsive: true, maintainAspectRatio: false, indexAxis: horizontal ? 'y' : 'x',
                    animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : {duration: 450},
                    plugins: {legend: {display: type === 'doughnut', position: 'bottom', rtl: true, labels: {usePointStyle: true, boxWidth: 8}},
                        tooltip: {rtl: true, callbacks: {label: context => `${chart.labels[context.dataIndex]}: ${exactNumber(chart.data[context.dataIndex])} ${chart.unit}`}}},
                    scales: type === 'doughnut' ? undefined : {
                        x: {beginAtZero: horizontal, grid: {display: horizontal}, title: {display: horizontal, text: chart.unit, color: ink}, ticks: {color: ink, precision: horizontal ? 0 : undefined}},
                        y: {beginAtZero: true, grid: {display: !horizontal}, title: {display: financial, text: 'میلیارد ریال', color: ink}, ticks: {color: ink, autoSkip: !horizontal, precision: financial ? undefined : 0}}
                    }, cutout: type === 'doughnut' ? '62%' : undefined}
            }));
        }
    }
    return {init, normalize, exactNumber};
})();
