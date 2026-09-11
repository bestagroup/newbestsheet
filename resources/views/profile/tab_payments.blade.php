<div class="tab-pane fade" id="navs-payments" role="tabpanel">
    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>مرحله/سریال</th><th>نوع</th><th>مبلغ</th><th>تاریخ</th><th>توضیحات</th></tr></thead><tbody>
        @forelse($finances as $finance)
            <tr><td>{{ $finance->serial ?: '—' }}</td><td>{{ $finance->finance_type ?: '—' }}</td><td>{{ number_format((float)($finance->amount ?? 0)) }}</td><td>{{ $finance->date ?: '—' }}</td><td>{{ $finance->description ?: '—' }}</td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-4">پرداختی ثبت نشده است.</td></tr>@endforelse
        </tbody>
    </table></div></div></div>
</div>
