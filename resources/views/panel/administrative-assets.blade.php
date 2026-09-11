@extends('layouts.base')

@section('title', 'مدیریت کالاها و اموال')

@section('content')
    @include('partials.alerts')

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>اطلاعات فرم نیاز به اصلاح دارد.</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($editingAsset || auth()->user()->can('can-access', ['assets', 'insert']))
        @php($assetForm = $editingAsset)
        <section class="card mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><h5 class="mb-1">{{ $assetForm ? 'ویرایش کالا/مال' : 'ثبت کالا یا مال جدید' }}</h5><small class="text-muted">شناسنامه اموال، محل استقرار و تحویل‌گیرنده را ثبت کنید.</small></div>
                @if($assetForm)<a class="btn btn-label-secondary" href="{{ route('assets.index') }}">انصراف از ویرایش</a>@endif
            </div>
            <div class="card-body">
                <form method="POST" action="{{ $assetForm ? route('assets.update', $assetForm) : route('assets.store') }}">
                    @csrf
                    @if($assetForm) @method('PATCH') @endif
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label" for="asset_code">کد مال *</label><input required class="form-control" id="asset_code" name="asset_code" value="{{ old('asset_code', $assetForm?->asset_code) }}"></div>
                        <div class="col-md-3"><label class="form-label" for="name">عنوان کالا/مال *</label><input required class="form-control" id="name" name="name" value="{{ old('name', $assetForm?->name) }}"></div>
                        <div class="col-md-3"><label class="form-label" for="category">دسته‌بندی</label><input class="form-control" id="category" name="category" placeholder="رایانه، مبلمان، تجهیزات و ..." value="{{ old('category', $assetForm?->category) }}"></div>
                        <div class="col-md-3"><label class="form-label" for="property_tag">پلاک اموال</label><input class="form-control" id="property_tag" name="property_tag" value="{{ old('property_tag', $assetForm?->property_tag) }}"></div>
                        <div class="col-md-3"><label class="form-label" for="brand">برند</label><input class="form-control" id="brand" name="brand" value="{{ old('brand', $assetForm?->brand) }}"></div>
                        <div class="col-md-3"><label class="form-label" for="model">مدل</label><input class="form-control" id="model" name="model" value="{{ old('model', $assetForm?->model) }}"></div>
                        <div class="col-md-3"><label class="form-label" for="serial_number">شماره سریال</label><input class="form-control" id="serial_number" name="serial_number" value="{{ old('serial_number', $assetForm?->serial_number) }}"></div>
                        <div class="col-md-2"><label class="form-label" for="quantity">تعداد *</label><input required type="number" min="1" class="form-control" id="quantity" name="quantity" value="{{ old('quantity', $assetForm?->quantity ?? 1) }}"></div>
                        <div class="col-md-1"><label class="form-label" for="unit">واحد *</label><input required class="form-control" id="unit" name="unit" value="{{ old('unit', $assetForm?->unit ?? 'عدد') }}"></div>
                        <div class="col-md-3"><label class="form-label" for="acquisition_date">تاریخ تحصیل/خرید</label><input class="form-control" id="acquisition_date" name="acquisition_date" data-jdp autocomplete="off" placeholder="۱۴۰۰/۰۱/۰۱" value="{{ old('acquisition_date', $assetForm?->acquisition_date) }}"></div>
                        <div class="col-md-3"><label class="form-label" for="purchase_cost">بهای خرید (ریال)</label><input class="form-control" id="purchase_cost" name="purchase_cost" inputmode="numeric" value="{{ old('purchase_cost', $assetForm?->purchase_cost) }}"></div>
                        <div class="col-md-3"><label class="form-label" for="location">محل استقرار</label><input class="form-control" id="location" name="location" value="{{ old('location', $assetForm?->location) }}"></div>
                        <div class="col-md-3"><label class="form-label" for="custodian_employee_id">تحویل‌گیرنده</label><select class="form-select" id="custodian_employee_id" name="custodian_employee_id"><option value="">بدون تحویل‌گیرنده</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) old('custodian_employee_id', $assetForm?->custodian_employee_id) === (string) $employee->id)>{{ $employee->full_name }} ({{ $employee->personnel_code }})</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label" for="condition">وضعیت فیزیکی *</label><select required class="form-select" id="condition" name="condition">@foreach($conditionLabels as $value => $label)<option value="{{ $value }}" @selected(old('condition', $assetForm?->condition ?? 'good') === $value)>{{ $label }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label" for="status">وضعیت بهره‌برداری *</label><select required class="form-select" id="status" name="status">@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(old('status', $assetForm?->status ?? 'in_use') === $value)>{{ $label }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label" for="notes">توضیحات</label><textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $assetForm?->notes) }}</textarea></div>
                    </div>
                    <div class="mt-3 text-end"><button class="btn btn-primary" type="submit"><i class="mdi mdi-content-save-outline me-1"></i>{{ $assetForm ? 'ذخیره تغییرات' : 'ثبت در دفتر اموال' }}</button></div>
                </form>
            </div>
        </section>
    @endif

    <section class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><h5 class="mb-1">دفتر کالاها و اموال</h5><small class="text-muted">{{ number_format($assets->total()) }} رکورد فعال</small></div>
            <form class="d-flex gap-2" method="GET" action="{{ route('assets.index') }}"><input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="کد، عنوان، سریال یا محل"><button class="btn btn-outline-primary" type="submit">جست‌وجو</button></form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>کد و عنوان</th><th>مشخصات</th><th>تعداد/ارزش</th><th>استقرار و تحویل</th><th>وضعیت</th><th class="text-end">عملیات</th></tr></thead>
                <tbody>
                @forelse($assets as $asset)
                    <tr>
                        <td><strong>{{ $asset->name }}</strong><br><small class="text-muted">کد: {{ $asset->asset_code }}@if($asset->property_tag) | پلاک: {{ $asset->property_tag }}@endif</small></td>
                        <td>{{ collect([$asset->brand, $asset->model])->filter()->implode(' / ') ?: '—' }}<br><small class="text-muted">{{ $asset->serial_number ? 'سریال: '.$asset->serial_number : ($asset->category ?: '') }}</small></td>
                        <td>{{ number_format($asset->quantity) }} {{ $asset->unit }}<br><small class="text-muted">{{ $asset->purchase_cost !== null ? number_format((float) $asset->purchase_cost).' ریال' : 'بها ثبت نشده' }}</small></td>
                        <td>{{ $asset->location ?: '—' }}<br><small class="text-muted">{{ $asset->custodian?->full_name ?: 'بدون تحویل‌گیرنده' }}</small></td>
                        <td><span class="badge bg-label-primary">{{ $statusLabels[$asset->status] ?? $asset->status }}</span><br><small>{{ $conditionLabels[$asset->condition] ?? $asset->condition }}</small></td>
                        <td class="text-end text-nowrap">
                            @can('can-access', ['assets', 'edit'])<a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('assets.index', ['edit' => $asset->id, 'q' => request('q')]) }}" title="ویرایش"><i class="mdi mdi-pencil-outline"></i></a>@endcan
                            @can('can-access', ['assets', 'delete'])<form class="d-inline" method="POST" action="{{ route('assets.destroy', $asset) }}" onsubmit="return confirm('این رکورد از دفتر فعال بایگانی شود؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger" type="submit" title="بایگانی"><i class="mdi mdi-archive-outline"></i></button></form>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">رکوردی یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($assets->hasPages())<div class="card-footer">{{ $assets->links('pagination::bootstrap-5') }}</div>@endif
    </section>
@endsection

@push('scripts')
    <script>document.addEventListener('DOMContentLoaded', () => window.jalaliDatepicker?.startWatch({autoShow: true, autoHide: true}));</script>
@endpush
