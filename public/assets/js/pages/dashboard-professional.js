/* No HTML interpolation from searchable records; server owns permissions and search. */
window.BestsheetDashboard = (() => {
    'use strict';
    const normalize = value => String(value ?? '').normalize('NFKC').toLocaleLowerCase('fa-IR')
        .replace(/[يى]/g, 'ی').replace(/ك/g, 'ک').replace(/[۰-۹]/g, c => '۰۱۲۳۴۵۶۷۸۹'.indexOf(c))
        .replace(/[٠-٩]/g, c => '٠١٢٣٤٥٦٧٨٩'.indexOf(c)).replace(/[٬,]/g, '')
        .replace(/\u200c/g, ' ').replace(/\s+/g, ' ').trim();
    const exactNumber = value => String(value).replace(/\B(?=(\d{3})+(?!\d))/g, '٬').replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
    const colors = ['#183b56', '#16866b', '#2596be', '#d49a32', '#9b6ab0', '#d45b67', '#4d908e', '#64748b'];
    function init(sections) {
        document.querySelector('.js-dashboard-refresh')?.addEventListener('click', () => window.location.reload());
        document.querySelectorAll('.js-clear-search').forEach(button => button.addEventListener('click', () => {
            button.form.querySelector('input[type="search"]').value = '';
            button.form.requestSubmit();
        }));
        document.querySelectorAll('.js-kind-filter').forEach(select => {
            const list = document.querySelector(`[data-domain-list="${select.dataset.domain}"]`);
            const counter = document.querySelector(`.js-result-count[data-domain="${select.dataset.domain}"]`);
            const apply = () => {
                const items = [...list.querySelectorAll('.js-domain-item')];
                items.forEach(item => item.hidden = !!select.value && item.dataset.kind !== select.value);
                const count = items.filter(item => !item.hidden).length;
                counter.textContent = `${exactNumber(count)} نتیجه`;
                const empty = list.querySelector('.domain-items-empty');
                if (empty) empty.hidden = count > 0 || items.length === 0;
            };
            select.addEventListener('change', apply); apply();
        });
        // Personal panels explicitly search only the recent rows already displayed.
        ['meeting-item', 'activity-item', 'assignment-item'].forEach(className => {
            const items = [...document.querySelectorAll(`.dashboard-shell .${className}`)];
            if (!items.length) return;
            const box = document.createElement('div'); box.className = 'local-list-search';
            const input = document.createElement('input'); input.type = 'search'; input.className = 'form-control form-control-sm';
            input.placeholder = 'جستجو در موارد اخیر نمایش‌داده‌شده…'; input.setAttribute('aria-label', input.placeholder);
            const count = document.createElement('small'); count.className = 'dashboard-muted'; count.setAttribute('aria-live', 'polite');
            box.append(input, count); items[0].parentElement.prepend(box);
            const apply = () => {
                const tokens = normalize(input.value).split(' ').filter(Boolean);
                items.forEach(item => item.hidden = !tokens.every(token => normalize(item.textContent).includes(token)));
                count.textContent = `${exactNumber(items.filter(item => !item.hidden).length)} از ${exactNumber(items.length)} مورد`;
            };
            input.addEventListener('input', apply); apply();
        });
        if (!window.Chart) return; // Data tables remain usable if charts cannot load.
        Chart.defaults.font.family = 'Vazirmatn, IRANSans, sans-serif';
        Chart.defaults.color = '#64748b';
        Object.entries(sections).forEach(([key, chart]) => {
            const canvas = document.getElementById(`domain-chart-${key}`);
            if (!canvas) return;
            const financial = key === 'finance';
            const horizontal = chart.type === 'bar' && !financial;
            const type = financial ? 'line' : chart.type;
            const values = chart.data.map(value => Number(value) / (financial ? 1e9 : 1));
            new Chart(canvas, {
                type,
                data: {labels: chart.labels, datasets: [{label: financial ? 'پرداخت (میلیارد ریال)' : chart.unit,
                    data: values, backgroundColor: financial ? 'rgba(22,134,107,.12)' : chart.labels.map((_, index) => colors[index % colors.length]),
                    borderColor: financial ? '#16866b' : 'transparent', borderWidth: financial ? 2 : 0,
                    fill: financial, tension: 0, pointRadius: financial ? 4 : 0, borderRadius: type === 'bar' ? 5 : 0}]},
                options: {responsive: true, maintainAspectRatio: false, indexAxis: horizontal ? 'y' : 'x',
                    animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : {duration: 450},
                    plugins: {legend: {display: type === 'doughnut', position: 'bottom', rtl: true, labels: {usePointStyle: true, boxWidth: 8}},
                        tooltip: {rtl: true, callbacks: {label: context => `${chart.labels[context.dataIndex]}: ${exactNumber(chart.data[context.dataIndex])} ${chart.unit}`}}},
                    scales: type === 'doughnut' ? undefined : {
                        x: {beginAtZero: horizontal, grid: {display: horizontal}, title: {display: horizontal, text: chart.unit}, ticks: horizontal ? {precision: 0} : {}},
                        y: {beginAtZero: true, grid: {display: !horizontal}, title: {display: financial, text: 'میلیارد ریال'}, ticks: horizontal ? {autoSkip: false} : {precision: financial ? undefined : 0}}
                    }, cutout: type === 'doughnut' ? '62%' : undefined}
            });
        });
    }
    return {init, normalize};
})();
