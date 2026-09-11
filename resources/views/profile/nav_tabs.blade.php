<div class="portal-nav-wrap">
    <ul class="nav nav-pills portal-nav flex-nowrap" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#navs-user-card" type="button"><i class="mdi mdi-account-outline"></i><span>اطلاعات کاربری</span></button></li>
        @if(auth()->user()->level === 'applicant' && isset($project))
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-company-card" type="button"><i class="mdi mdi-office-building-outline"></i><span>شرکت و طرح</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-project-profile-card" type="button"><i class="mdi mdi-chart-timeline-variant"></i><span>وضعیت پرونده</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-members-card" type="button"><i class="mdi mdi-account-group-outline"></i><span>اعضای تیم</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-investment-card" type="button"><i class="mdi mdi-source-branch"></i><span>مراحل سرمایه‌گذاری</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-documents-card" type="button"><i class="mdi mdi-folder-multiple-outline"></i><span>اسناد</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-minutes-card" type="button"><i class="mdi mdi-text-box-check-outline"></i><span>صورتجلسات</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-guarantee-card" type="button"><i class="mdi mdi-calendar-alert-outline"></i><span>تعهدات</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-sales-card" type="button"><i class="mdi mdi-chart-line"></i><span>فروش</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-contracts-card" type="button"><i class="mdi mdi-file-sign"></i><span>قراردادها</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-payments" type="button"><i class="mdi mdi-cash-multiple"></i><span>پرداخت‌ها</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-reports" type="button"><i class="mdi mdi-finance"></i><span>گزارش مالی</span></button></li>
        @endif
    </ul>
</div>
