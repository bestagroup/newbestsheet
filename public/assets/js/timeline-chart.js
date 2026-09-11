    /* =========  Jalali <-> Gregorian (self-contained, no external deps)  ========= */
    function _div(a,b){ return Math.floor(a/b); }
    function _g2d(gy, gm, gd){
    var a = _div(14-gm,12), y = gy + 4800 - a, m = gm + 12*a - 3;
    return gd + _div(153*m+2,5) + 365*y + _div(y,4) - _div(y,100) + _div(y,400) - 32045;
}
    function _d2g(jdn){
    var j = jdn + 32044,
    g = _div(j,146097),
    dg = j % 146097,
    c = _div((_div(dg,36524)+1)*3,4),
    dc = dg - c*36524,
    b = _div(dc,1461),
    db = dc % 1461,
    a = _div((_div(db,365)+1)*3,4),
    da = db - a*365,
    y = g*400 + c*100 + b*4 + a,
    m = _div(da*5 + 308,153) - 2,
    d = da - _div((m+4)*153,5) + 122;
    return { gy: y - 4800 + _div(m+2,12), gm: (m+2)%12 + 1, gd: d + 1 };
}
    function _jalCal(jy){
    var breaks=[-61,9,38,199,426,686,756,818,1111,1181,1210,1635,2060,2097,2192,2262,2324,2394,2456,3178],
    bl=breaks.length, gy=jy+621, leapJ=-14, jp=breaks[0], jm, jump, n, i;
    if (jy < jp || jy >= breaks[bl-1]) throw new Error('Invalid JY');
    for(i=1;i<bl;i++){ jm=breaks[i]; jump=jm-jp; if(jy<jm) break; leapJ += _div(jump,33)*8 + _div(jump%33,4); jp=jm; }
    n = jy - jp; leapJ += _div(n,33)*8 + _div((n%33)+3,4); if((jump%33)==4 && jump-n==4) leapJ++;
    var leapG = _div(gy,4) - _div((_div(gy,100)+1)*3,4) - 150;
    var march = 20 + leapJ - leapG;
    return { gy: gy, march: march };
}
    function _j2d(jy,jm,jd){
    var r=_jalCal(jy), jdn1f=_g2d(r.gy,3,r.march);
    return jdn1f + (jm-1)*31 - _div(jm-1,7)*(jm-7) + jd - 1;
}
    function _d2j(jdn){
    var g=_d2g(jdn), jy=g.gy-621, r=_jalCal(jy), jdn1f=_g2d(r.gy,3,r.march), k=jdn - jdn1f, jm, jd;
    if (k>=0){
    if (k<=185){ jm=1+_div(k,31); jd=(k%31)+1; return {jy:jy, jm:jm, jd:jd}; }
    k-=186; jm=7+_div(k,30); jd=(k%30)+1; return {jy:jy, jm:jm, jd:jd};
} else {
    jy-=1; r=_jalCal(jy); jdn1f=_g2d(r.gy,3,r.march); k=jdn - jdn1f;
    if (k<=185){ jm=1+_div(k,31); jd=(k%31)+1; return {jy:jy, jm:jm, jd:jd}; }
    k-=186; jm=7+_div(k,30); jd=(k%30)+1; return {jy:jy, jm:jm, jd:jd};
}
}
    function faDigits(v){ return String(v).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[+d]); }
    function jStr2ts(j){ // "YYYY-MM-DD" (Jalali) -> ms timestamp (Gregorian)
    var m=/^(\d{4})-(\d{1,2})-(\d{1,2})$/.exec(j); if(!m) throw new Error('Bad Jalali date: '+j);
    var jy=+m[1], jm=+m[2], jd=+m[3], g=_d2g(_j2d(jy,jm,jd));
    return new Date(g.gy, g.gm-1, g.gd).getTime();
}
    function ts2jStr(ts){ var j=_d2j(_g2d(new Date(ts).getFullYear(), new Date(ts).getMonth()+1, new Date(ts).getDate()));
    var mm=('0'+j.jm).slice(-2), dd=('0'+j.jd).slice(-2); return faDigits(j.jy+'/'+mm+'/'+dd);
}
    /* =========  /Jalali helpers  ========= */

    (function initTimeline(){
    // 1) نمونه داده (می‌تونی از بک‌اند پرش کنی)
    const TASKS = [
{ title:'نمونه‌سازی',      start_jalali:'1403-01-11', end_jalali:'1403-02-12', color:'#7C83FF' },
{ title:'برنامه‌های توسعه', start_jalali:'1403-02-01', end_jalali:'1403-03-20', color:'#63D471' },
{ title:'افزایش سرمایه',   start_jalali:'1403-01-25', end_jalali:'1403-04-31', color:'#FFA938' },
{ title:'جذب مشتری جدید',  start_jalali:'1403-02-15', end_jalali:'1403-05-20', color:'#FFCA2C' }
    ];
    // شمارنده
    const cnt=document.getElementById('tasksCount'); if (cnt) cnt.textContent = faDigits(TASKS.length);

    // 2) تبدیل داده برای Apex
    const series=[{
    data: TASKS.map((t,i)=>({
    y:[ jStr2ts(t.start_jalali), jStr2ts(t.end_jalali) ],
    x: faDigits(i+1),
    fillColor: t.color,
    _meta: t
}))
}];

    // 3) تم
    const isDark = document.documentElement.getAttribute('data-theme') === 'theme-default-dark';
    const palette = {
    grid:   isDark ? '#2e3039' : '#eef0f4',
    text:   isDark ? '#c7c9d1' : '#667085',
    title:  isDark ? '#eef2f6' : '#111827'
};
    const J_MONTHS=['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];

    const YEAR = +(TASKS[0]?.start_jalali.split('-')[0] || '1403');
    const X_MIN = jStr2ts(`${YEAR}-01-01`);
    const X_MAX = jStr2ts(`${YEAR+1}-01-01`); // اول سال بعد
    // 4) تنظیمات نمودار
    const options={
    chart:{ type:'rangeBar', height:300, parentHeightOffset:0, toolbar:{show:false},
    fontFamily:'Vazirmatn, IRANSans, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif'
},
    series: series,
    plotOptions:{ bar:{ horizontal:true, distributed:true, borderRadius:12, startingShape:'rounded', endingShape:'rounded',
    dataLabels:{ hideOverflowingLabels:false } } },
    dataLabels:{ enabled:true, style:{ fontSize:'12px', fontWeight:700 },
    formatter:function(_val,ctx){ return ctx.w.config.series[ctx.seriesIndex].data[ctx.dataPointIndex]._meta.title; } },
    xaxis:{
    type:'datetime', axisBorder:{show:false}, axisTicks:{show:false},
    labels:{ style:{ colors:palette.text, fontSize:'12px' },
    formatter:function(valueTs){
    // تبدیل میلادیِ تیک به ماه جلالی
    const g = new Date(Number(valueTs));
    const j = _d2j(_g2d(g.getFullYear(), g.getMonth()+1, g.getDate()));
    return J_MONTHS[(j.jm-1)|0];
}
},
    tooltip:{enabled:false}
},
    yaxis: {
    opposite: true, // 👈 محور Y بره سمت راست (هماهنگ با RTL)
    labels: {
    // عرض کافی بده تا «ردیف ۱۲» کامل دیده شه
    minWidth: 90,
    maxWidth: 140,
    // چون محور سمت راسته، کمی به داخل نمودار هل بدیم
    offsetX: -10,
    style: { colors: palette.title, fontSize: '12px', fontWeight: 600 },
    formatter: function (label) {
    return 'ردیف ' + label; // برچسب فارسی
}
}
},

    grid:{ borderColor:palette.grid, strokeDashArray:6, xaxis:{lines:{show:true}}, yaxis:{lines:{show:false}},
    padding:{ top:8, left:16, right:16, bottom:8 } },
    tooltip:{ theme:isDark?'dark':'light', x:{show:false},
    y:{ title:{formatter:()=>''},
    formatter:function(_v,ctx){
    const row=ctx.w.config.series[ctx.seriesIndex].data[ctx.dataPointIndex]._meta;
    return row.title+' | '+row.start_jalali+' — '+row.end_jalali;
} },
    marker:{show:false}
},
    legend:{show:false},
    states:{ hover:{filter:{type:'none'}}, active:{filter:{type:'none'}} }
};

    // 5) رندر
    const el=document.getElementById('projectTimelineChart');
    if (!el) return;
    new ApexCharts(el, options).render();
})();
