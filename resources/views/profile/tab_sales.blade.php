<div class="tab-pane fade" id="navs-sales-card" role="tabpanel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h5 class="mb-1 fw-bold">گزارش فروش و عملکرد</h5><small class="text-muted">اطلاعات دوره‌ای فروش، درآمد و هزینه‌های عملیاتی طرح</small></div>
        <button class="btn btn-primary" id="investeeSaleCreate" type="button"><i class="mdi mdi-plus-circle-outline me-1"></i>ثبت گزارش فروش</button>
    </div>
    <div class="card"><div class="card-body"><div class="table-responsive">
        <table id="investeeSalesTable" class="table w-100">
            <thead><tr><th>مشتریان</th><th>تعداد فروش</th><th>تولید</th><th>مبلغ فروش</th><th>درآمد ماهانه</th><th>هزینه جاری</th><th>هزینه اداری/مالی</th><th>تاریخ</th><th>عملیات</th></tr></thead>
        </table>
    </div></div></div>
</div>

<div class="modal fade" id="investeeSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><div><h5 class="modal-title mb-1">گزارش فروش و عملکرد</h5><small class="text-muted">اطلاعات عملکرد دوره را ثبت یا به‌روزرسانی کنید</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="investeeSaleForm" method="POST">
            @csrf <input type="hidden" name="_method" id="investeeSaleMethod" value="POST"><input type="hidden" id="investeeSaleId">
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">تعداد مشتریان</label><input type="number" min="0" class="form-control" name="count_customers" required></div>
                <div class="col-md-4"><label class="form-label">تعداد فروش</label><input type="number" min="0" class="form-control" name="count_sales"></div>
                <div class="col-md-4"><label class="form-label">تعداد تولید</label><input type="number" min="0" class="form-control" name="production_count"></div>
                <div class="col-md-6"><label class="form-label">مبلغ فروش</label><input class="form-control price-input" name="amount_sales"></div>
                <div class="col-md-6"><label class="form-label">درآمد ماهانه</label><input class="form-control price-input" name="monthly_income"></div>
                <div class="col-md-6"><label class="form-label">مجموع هزینه‌های جاری</label><input class="form-control price-input" name="current_cost"></div>
                <div class="col-md-6"><label class="form-label">هزینه اداری/مالی</label><input class="form-control price-input" name="financial_cost"></div>
                <div class="col-md-6"><label class="form-label">تاریخ گزارش</label><input data-jdp class="form-control" name="date" required></div>
                <div class="col-12"><label class="form-label">توضیحات</label><textarea class="form-control" name="description" rows="3"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button><button class="btn btn-primary" type="submit"><i class="mdi mdi-content-save-outline me-1"></i>ذخیره</button></div>
        </form>
    </div></div>
</div>
