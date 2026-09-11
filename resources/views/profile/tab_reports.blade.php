<div class="tab-pane fade" id="navs-reports" role="tabpanel">
    <div class="card"><div class="card-body text-start">
        <h5 class="mb-3">صورت‌های مالی ثبت‌شده</h5>
        <div class="table-responsive"><table class="table align-middle">
            <thead><tr><th>دوره</th><th>فروش خالص</th><th>سود ناخالص</th><th>سود/زیان خالص</th><th>کل دارایی</th><th>حقوق صاحبان سهام</th></tr></thead><tbody>
            @forelse($financialStatements as $statement)
                <tr><td>{{ $statement->year }}/{{ str_pad((string)$statement->month, 2, '0', STR_PAD_LEFT) }}</td><td>{{ number_format((float)($statement->net_sales ?? 0)) }}</td><td>{{ number_format((float)($statement->gross_profit ?? 0)) }}</td><td>{{ number_format((float)($statement->net_profit ?? 0)) }}</td><td>{{ number_format((float)($statement->total_assets ?? 0)) }}</td><td>{{ number_format((float)($statement->total_equity ?? 0)) }}</td></tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-4">صورت مالی ثبت نشده است.</td></tr>@endforelse
            </tbody>
        </table></div>
    </div></div>
</div>
