window.BestsheetFinancialCharts = (() => {
    'use strict';
    const format = value => {
        if (value === null || value === undefined) return 'ناموجود / غیرقابل محاسبه';
        const [integer, decimal] = String(value).split('.');
        return (integer.replace(/\B(?=(\d{3})+(?!\d))/g, '٬') + (decimal ? '٫' + decimal : '')).replace(/\d/g, digit => '۰۱۲۳۴۵۶۷۸۹'[digit]);
    };
    function init(charts) {
        if (!window.Chart) return;
        charts.forEach(chart => {
            const canvas = document.getElementById(`financial-chart-${chart.id}`);
            if (!canvas) return;
            const horizontal = chart.type === 'horizontal';
            const money = chart.unit === 'ریال';
            const scale = money ? 1e9 : 1;
            const unit = money ? 'میلیارد ریال' : chart.unit;
            new Chart(canvas, {
                type: horizontal ? 'bar' : chart.type,
                data: {labels: chart.labels, datasets: chart.series.map(series => ({
                    label: series.label,
                    data: series.values.map(value => value === null ? null : Number(value) / scale),
                    borderColor: series.color,
                    backgroundColor: chart.type === 'line' ? series.color : series.values.map(value => value !== null && Number(value) < 0 ? '#c84558' : series.color),
                    borderWidth: chart.type === 'line' ? 2 : 0,
                    borderRadius: 4, tension: 0, fill: false, spanGaps: false, pointRadius: 3
                }))},
                options: {
                    responsive: true, maintainAspectRatio: false, indexAxis: horizontal ? 'y' : 'x',
                    animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : {duration: 350},
                    interaction: {mode: 'index', intersect: false},
                    plugins: {legend: {position: 'bottom', rtl: true}, tooltip: {rtl: true, callbacks: {
                        label: context => `${chart.series[context.datasetIndex].label}: ${format(chart.series[context.datasetIndex].values[context.dataIndex])} ${chart.unit}`
                    }}},
                    scales: {
                        [horizontal ? 'x' : 'y']: {beginAtZero: true, title: {display: true, text: unit}, ticks: {callback: value => Number(value).toLocaleString('fa-IR'), ...(chart.unit === 'شرکت' ? {precision: 0} : {})}},
                        [horizontal ? 'y' : 'x']: {grid: {display: false}, ticks: {autoSkip: !horizontal, maxRotation: 45}}
                    }
                }
            });
        });
    }
    return {init, format};
})();
