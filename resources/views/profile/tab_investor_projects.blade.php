@php
    // فرض بر این است که پروژه‌ها از کنترلر به عنوان $investments ارسال شده‌اند
    // هر پروژه شامل: title، company_name، amount_invested، ownership_percent، status، created_at
    $investments = [
        [
            'title' => 'سامانه مدیریت قراردادها',
            'company_name' => 'شرکت دانش‌بنیان پویش‌یار',
            'amount_invested' => 1500000000,
            'ownership_percent' => 18,
            'status' => 'در حال بررسی',
            'created_at' => '1403/02/10'
        ],
        [
            'title' => 'پلتفرم تحلیل بازار مالی',
            'company_name' => 'استارتاپ نئواینوست',
            'amount_invested' => 2200000000,
            'ownership_percent' => 25,
            'status' => 'تکمیل شده',
            'created_at' => '1402/11/28'
        ],
    ];
@endphp

<div class="tab-pane fade justify-content-center" id="navs-investor-projects-card" role="tabpanel">
    <h5 class="text-start mb-4">پروژه‌های سرمایه‌گذاری‌شده</h5>

    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
            <thead class="table-light">
            <tr>
                <th>عنوان پروژه</th>
                <th>شرکت مجری</th>
                <th>مبلغ سرمایه‌گذاری (تومان)</th>
                <th>درصد مالکیت</th>
                <th>وضعیت</th>
                <th>تاریخ سرمایه‌گذاری</th>
                <th>جزئیات</th>
            </tr>
            </thead>
            <tbody>
            @foreach($investments as $item)
                <tr>
                    <td>{{ $item['title'] }}</td>
                    <td>{{ $item['company_name'] }}</td>
                    <td>{{ number_format($item['amount_invested']) }}</td>
                    <td>{{ $item['ownership_percent'] }}٪</td>
                    <td>
                        @if($item['status'] === 'تکمیل شده')
                            <span class="badge bg-success">{{ $item['status'] }}</span>
                        @elseif($item['status'] === 'در حال بررسی')
                            <span class="badge bg-warning">{{ $item['status'] }}</span>
                        @else
                            <span class="badge bg-secondary">{{ $item['status'] }}</span>
                        @endif
                    </td>
                    <td>{{ $item['created_at'] }}</td>
                    <td>
                        <a href="#" class="btn btn-sm btn-outline-primary">
                            مشاهده
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
