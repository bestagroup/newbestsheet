@extends('layouts.base')
@section('title', 'حساب کاربری')
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .investee-portal { --portal-radius: 1rem; }
        .investee-portal .portal-shell { border-radius: 1.2rem; overflow: hidden; }
        .investee-portal .portal-hero { border: 0; border-radius: 1.15rem; background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), .13), rgba(var(--bs-info-rgb), .06)); }
        .investee-portal .portal-hero__icon { width: 54px; height: 54px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; }
        .investee-portal .portal-nav-wrap { overflow-x: auto; scrollbar-width: thin; padding-bottom: .15rem; }
        .investee-portal .portal-nav { min-width: max-content; gap: .5rem; padding: .25rem; }
        .investee-portal .portal-nav .nav-link { display: inline-flex; align-items: center; gap: .45rem; border-radius: .8rem; padding: .65rem .85rem; white-space: nowrap; color: var(--bs-body-color); background: rgba(var(--bs-secondary-rgb), .045); border: 1px solid transparent; }
        .investee-portal .portal-nav .nav-link:hover { border-color: rgba(var(--bs-primary-rgb), .18); color: var(--bs-primary); }
        .investee-portal .portal-nav .nav-link.active { box-shadow: 0 .35rem .9rem rgba(var(--bs-primary-rgb), .18); }
        .investee-portal .tab-pane > .card, .investee-portal .tab-pane > .row > [class*="col-"] > .card { border: 0 !important; border-radius: var(--portal-radius); box-shadow: 0 .3rem 1.1rem rgba(31,45,61,.065); }
        .investee-portal .form-label { font-weight: 600; margin-bottom: .45rem; }
        .investee-portal .form-control, .investee-portal .form-select { border-radius: .7rem; min-height: 42px; }
        .investee-portal textarea.form-control { min-height: auto; }
        .investee-portal .form-control:focus, .investee-portal .form-select:focus { box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .1); }
        .investee-portal .table { margin-bottom: 0; }
        .investee-portal .table thead th { background: rgba(var(--bs-primary-rgb), .045); font-weight: 700; border-bottom-width: 1px; }
        .investee-portal .table td, .investee-portal .table th { vertical-align: middle; padding-top: .8rem; padding-bottom: .8rem; }
        .investee-portal .btn { border-radius: .65rem; }
        .investee-portal .modal-content { border: 0; border-radius: 1rem; overflow: hidden; }
        .investee-portal .portal-alert-link { border-radius: .9rem; border: 1px solid rgba(var(--bs-warning-rgb), .25); background: rgba(var(--bs-warning-rgb), .07); }
        .investee-portal .investment-stepper { position: relative; }
        .investee-portal .investment-step { position: relative; display: grid; grid-template-columns: 52px minmax(0,1fr); gap: 1rem; padding-bottom: 1.25rem; }
        .investee-portal .investment-step:last-child { padding-bottom: 0; }
        .investee-portal .investment-step::before { content: ''; position: absolute; top: 48px; bottom: 0; right: 25px; width: 2px; background: var(--bs-border-color); }
        .investee-portal .investment-step:last-child::before { display: none; }
        .investee-portal .investment-step__marker { position: relative; z-index: 2; width: 48px; height: 48px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; border: 3px solid var(--bs-border-color); background: var(--bs-body-bg); color: var(--bs-secondary-color); box-shadow: 0 0 0 5px var(--bs-body-bg); }
        .investee-portal .investment-step.is-completed .investment-step__marker { background: var(--bs-success); border-color: var(--bs-success); color: #fff; }
        .investee-portal .investment-step.is-current .investment-step__marker { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; transform: scale(1.06); }
        .investee-portal .investment-step.is-rejected .investment-step__marker { background: var(--bs-danger); border-color: var(--bs-danger); color: #fff; }
        .investee-portal .investment-step.is-completed::before { background: var(--bs-success); }
        .investee-portal .investment-step__card { border: 1px solid var(--bs-border-color); border-radius: 1rem; padding: 1rem 1.1rem; background: var(--bs-body-bg); }
        .investee-portal .investment-step.is-current .investment-step__card { border-color: rgba(var(--bs-primary-rgb), .38); background: rgba(var(--bs-primary-rgb), .035); box-shadow: 0 .45rem 1.2rem rgba(var(--bs-primary-rgb), .10); }
        .investee-portal .investment-step.is-future .investment-step__card { background: rgba(var(--bs-secondary-rgb), .025); }
        .investee-portal .step-document-chip { border: 1px solid var(--bs-border-color); border-radius: .75rem; padding: .6rem .7rem; background: var(--bs-body-bg); }
        .investee-portal .step-document-files { max-height: 13rem; overflow-y: auto; }
        @media (max-width: 767.98px) {
            .investee-portal .portal-nav .nav-link { padding: .6rem .72rem; font-size: .82rem; }
            .investee-portal .portal-nav .nav-link i { font-size: 1.05rem; }
            .investee-portal .card-body { padding: 1rem; }
        }
    </style>
@endsection

@section('content')
<div class="container-fluid py-4 investee-portal">
    @if(auth()->user()->level === 'applicant' && isset($project))
        @php
            $portalCurrentStep = $investsteps->firstWhere('id', (int) $project->invest_step);
            $portalAlertCount = ($operationalAlerts ?? collect())->count();
        @endphp
        <div class="card portal-hero shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="portal-hero__icon bg-label-primary"><i class="mdi mdi-briefcase-check-outline"></i></span>
                        <div>
                            <div class="text-muted small mb-1">پرونده سرمایه‌گذاری</div>
                            <h5 class="mb-1 fw-bold">{{ $project->title ?: 'طرح سرمایه‌گذاری' }}</h5>
                            <div class="text-muted small">{{ $company?->company_name ?? $project->company_name ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-label-primary px-3 py-2">مرحله جاری: {{ $portalCurrentStep?->title ?? 'نامشخص' }}</span>
                        <span class="badge bg-label-success px-3 py-2">پیشرفت {{ (int) $project->progress_percentage }}٪</span>
                        <a href="{{ route('notifications.index') }}" class="btn btn-outline-warning position-relative">
                            <i class="mdi mdi-bell-alert-outline me-1"></i> اعلان‌ها
                            @if($portalAlertCount > 0)<span class="badge bg-danger ms-1">{{ $portalAlertCount }}</span>@endif
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($portalAlertCount > 0)
            <a href="{{ route('notifications.index') }}" class="portal-alert-link d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 mb-4 text-reset text-decoration-none">
                <div class="d-flex align-items-center gap-2"><i class="mdi mdi-clock-alert-outline text-warning mdi-24px"></i><div><strong>{{ $portalAlertCount }} هشدار فعال برای پرونده شما</strong><div class="small text-muted">جزئیات تمام هشدارهای KPI و تعهدات در مرکز اعلان‌ها نمایش داده می‌شود.</div></div></div>
                <span class="btn btn-sm btn-warning">مشاهده اعلان‌ها</span>
            </a>
        @endif
    @endif

    <div class="card border-0 shadow-sm portal-shell">
        <div class="card-header bg-transparent border-bottom p-2 p-md-3">@include('profile.nav_tabs')</div>
        <div class="card-body p-3 p-md-4">
            @if(isset($portalWarning))<div class="alert alert-warning">{{ $portalWarning }}</div>@endif
            <div class="tab-content p-0">
                @include('profile.tab_user_info')

                @if(auth()->user()->level === 'applicant' && isset($project))
                    @include('profile.tab_company_profile')
                    @include('profile.tab_project_profile')
                    @include('profile.tab_members')
                    @include('profile.tab_investment_steps')
                    @include('profile.tab_documents')
                    @include('profile.tab_minutes')
                    @include('profile.tab_guarantees')
                    @include('profile.tab_sales')
                    @include('profile.tab_contracts')
                    @include('profile.tab_payments')
                    @include('profile.tab_reports')
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const notify = (message, icon = 'success') => {
        if (window.AppAlert) {
            return icon === 'error' ? AppAlert.error(message) : AppAlert.success(message);
        }
        return window.Swal ? Swal.fire({text: message, icon}) : Promise.resolve();
    };

    async function jsonRequest(url, options = {}) {
        const response = await fetch(url, {
            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {})},
            ...options
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validation = data.errors ? Object.values(data.errors).flat().join('\n') : null;
            throw new Error(validation || data.message || 'عملیات با خطا مواجه شد.');
        }
        return data;
    }

    document.querySelectorAll('.investee-ajax-form').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const button = form.querySelector('[type="submit"]');
            if (button) button.disabled = true;
            try {
                const data = await jsonRequest(form.action, {method: 'POST', body: new FormData(form)});
                await notify(data.message || 'اطلاعات ذخیره شد.');
                window.location.reload();
            } catch (error) { notify(error.message, 'error'); }
            finally { if (button) button.disabled = false; }
        });
    });

    const state = document.getElementById('investeeState');
    const city = document.getElementById('investeeCity');
    state?.addEventListener('change', async function () {
        city.innerHTML = '<option value="">در حال بارگذاری...</option>';
        if (!this.value) { city.innerHTML = '<option value="">انتخاب کنید</option>'; return; }
        try {
            const response = await fetch(`{{ url('panel/getcities') }}/${this.value}`, {headers: {'Accept': 'application/json'}});
            const cities = await response.json();
            city.innerHTML = '<option value="">انتخاب کنید</option>' + cities.map(item => `<option value="${item.id}">${item.title}</option>`).join('');
        } catch (_) { city.innerHTML = '<option value="">خطا در دریافت شهرها</option>'; }
    });

    const investeeUploadModalElement = document.getElementById('investeeUploadModal');
    const investeeDocumentForm = document.getElementById('investeeDocumentForm');
    const investeeDocumentFiles = document.getElementById('investeeDocumentFiles');
    const investeeSelectedFiles = document.getElementById('investeeSelectedFiles');

    document.querySelectorAll('.investee-upload-trigger').forEach(button => {
        button.addEventListener('click', function () {
            investeeDocumentForm?.reset();
            document.getElementById('investeeDocumentSubject').value = this.dataset.subject;
            document.getElementById('investeeDocumentRequirement').value = this.dataset.requirement;
            document.getElementById('investeeUploadTitle').textContent = `بارگذاری ${this.dataset.title || 'سند'}`;
            const minimum = Number(this.dataset.minimum || 1);
            const uploaded = Number(this.dataset.uploaded || 0);
            const remaining = Math.max(0, minimum - uploaded);
            document.getElementById('investeeUploadHint').textContent = remaining > 0
                ? `برای تکمیل این مدرک دست‌کم ${remaining} فایل دیگر لازم است.`
                : 'می‌توانید فایل تکمیلی دیگری برای این مدرک ثبت کنید.';
            investeeSelectedFiles.textContent = 'هنوز فایلی انتخاب نشده است.';
            bootstrap.Modal.getOrCreateInstance(investeeUploadModalElement).show();
        });
    });

    investeeDocumentFiles?.addEventListener('change', function () {
        const selected = Array.from(this.files || []);
        investeeSelectedFiles.textContent = selected.length
            ? `${selected.length} فایل انتخاب شد: ${selected.map(file => file.name).join('، ')}`
            : 'هنوز فایلی انتخاب نشده است.';
    });

    investeeDocumentForm?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const button = this.querySelector('[type="submit"]');
        if (button) button.disabled = true;
        try {
            const data = await jsonRequest(this.action, {method: 'POST', body: new FormData(this)});
            await notify(data.message || 'فایل بارگذاری شد.');
            window.location.reload();
        } catch (error) {
            notify(error.message, 'error');
            if (button) button.disabled = false;
        }
    });

    document.querySelectorAll('.investee-document-delete').forEach(button => {
        button.addEventListener('click', async function () {
            const accepted = window.Swal ? (await Swal.fire({title: 'حذف سند؟', icon: 'warning', showCancelButton: true, confirmButtonText: 'حذف', cancelButtonText: 'انصراف'})).isConfirmed : confirm('حذف شود؟');
            if (!accepted) return;
            try {
                const data = await jsonRequest(`{{ url('panel/profile/documents') }}/${this.dataset.id}`, {method: 'DELETE'});
                await notify(data.message || 'سند حذف شد.');
                window.location.reload();
            } catch (error) { notify(error.message, 'error'); }
        });
    });

    @if(auth()->user()->level === 'applicant' && isset($project))
    const memberModalEl = document.getElementById('investeeMemberModal');
    const memberModal = memberModalEl ? bootstrap.Modal.getOrCreateInstance(memberModalEl) : null;
    const memberForm = document.getElementById('investeeMemberForm');
    document.getElementById('investeeMemberCreate')?.addEventListener('click', function () {
        memberForm.reset();
        document.getElementById('investeeMemberId').value = '';
        document.getElementById('investeeMemberMethod').value = 'POST';
        document.getElementById('investeeMemberActive').checked = true;
        memberModal?.show();
    });

    document.addEventListener('click', async function (event) {
        const editMember = event.target.closest('.investee-member-edit');
        if (editMember) {
            try {
                const result = await jsonRequest(`{{ url('panel/profile/members') }}/${editMember.dataset.id}/edit`);
                const member = result.data;
                memberForm.reset();
                document.getElementById('investeeMemberId').value = member.id;
                document.getElementById('investeeMemberMethod').value = 'PATCH';
                ['full_name','national_code','position','start_date','end_date'].forEach(field => {
                    if (memberForm.elements[field]) memberForm.elements[field].value = member[field] ?? '';
                });
                document.getElementById('investeeMemberActive').checked = Boolean(member.is_active);
                memberModal?.show();
            } catch (error) { notify(error.message, 'error'); }
            return;
        }
        const deleteMember = event.target.closest('.investee-member-delete');
        if (deleteMember) {
            const accepted = window.Swal ? (await Swal.fire({title:'حذف عضو تیم؟', icon:'warning', showCancelButton:true, confirmButtonText:'حذف', cancelButtonText:'انصراف'})).isConfirmed : confirm('حذف شود؟');
            if (!accepted) return;
            try {
                await jsonRequest(`{{ url('panel/profile/members') }}/${deleteMember.dataset.id}`, {method:'DELETE'});
                window.location.reload();
            } catch (error) { notify(error.message, 'error'); }
        }
    });

    memberForm?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const id = document.getElementById('investeeMemberId').value;
        const action = id ? `{{ url('panel/profile/members') }}/${id}` : '{{ route('profile.members.store') }}';
        try {
            const data = await jsonRequest(action, {method:'POST', body:new FormData(this)});
            memberModal?.hide();
            await notify(data.message || 'اطلاعات عضو تیم ذخیره شد.');
            window.location.reload();
        } catch (error) { notify(error.message, 'error'); }
    });

    const saleModalEl = document.getElementById('investeeSaleModal');
    const saleModal = saleModalEl ? bootstrap.Modal.getOrCreateInstance(saleModalEl) : null;
    const saleForm = document.getElementById('investeeSaleForm');
    const salesTable = window.jQuery && document.getElementById('investeeSalesTable') ? $('#investeeSalesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('profile.sales.index') }}',
        columns: [
            {data:'count_customers', name:'count_customers'}, {data:'count_sales', name:'count_sales'},
            {data:'production_count', name:'production_count'}, {data:'amount_sales', name:'amount_sales'},
            {data:'monthly_income', name:'monthly_income'}, {data:'current_cost', name:'current_cost'},
            {data:'financial_cost', name:'financial_cost'}, {data:'date', name:'date'},
            {data:'action', name:'action', orderable:false, searchable:false}
        ],
        language: {url: '{{ asset('assets/vendor/js/fa.json') }}'}
    }) : null;

    document.getElementById('investeeSaleCreate')?.addEventListener('click', function () {
        saleForm.reset();
        document.getElementById('investeeSaleId').value = '';
        document.getElementById('investeeSaleMethod').value = 'POST';
        saleModal?.show();
    });

    document.addEventListener('click', async function (event) {
        const edit = event.target.closest('.investee-sale-edit');
        if (edit) {
            try {
                const result = await jsonRequest(`{{ url('panel/profile/sales') }}/${edit.dataset.id}/edit`);
                const sale = result.data;
                saleForm.reset();
                document.getElementById('investeeSaleId').value = sale.id;
                document.getElementById('investeeSaleMethod').value = 'PATCH';
                ['count_customers','count_sales','production_count','amount_sales','monthly_income','current_cost','financial_cost','description'].forEach(field => {
                    if (saleForm.elements[field]) saleForm.elements[field].value = sale[field] ?? '';
                });
                saleForm.elements.date.value = (sale.date || '').substring(0, 10);
                saleModal?.show();
            } catch (error) { notify(error.message, 'error'); }
            return;
        }
        const del = event.target.closest('.investee-sale-delete');
        if (del) {
            const accepted = window.Swal ? (await Swal.fire({title:'حذف گزارش فروش؟', icon:'warning', showCancelButton:true, confirmButtonText:'حذف', cancelButtonText:'انصراف'})).isConfirmed : confirm('حذف شود؟');
            if (!accepted) return;
            try { await jsonRequest(`{{ url('panel/profile/sales') }}/${del.dataset.id}`, {method:'DELETE'}); salesTable?.ajax.reload(null, false); }
            catch (error) { notify(error.message, 'error'); }
        }
    });

    saleForm?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const id = document.getElementById('investeeSaleId').value;
        const action = id ? `{{ url('panel/profile/sales') }}/${id}` : '{{ route('profile.sales.store') }}';
        try {
            const data = await jsonRequest(action, {method:'POST', body:new FormData(this)});
            saleModal?.hide(); salesTable?.ajax.reload(null, false); notify(data.message || 'ثبت شد.');
        } catch (error) { notify(error.message, 'error'); }
    });
    @endif

    const hash = window.location.hash;
    if (hash) {
        const trigger = document.querySelector(`[data-bs-target="${hash}"]`);
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(button => button.addEventListener('shown.bs.tab', event => history.replaceState(null, '', event.target.dataset.bsTarget)));
});
</script>
@endpush
