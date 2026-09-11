@extends('layouts.base')

@section('title', 'مدیریت کارکنان و مدارک پرسنلی')

@section('content')
    @include('partials.alerts')

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>اطلاعات فرم نیاز به اصلاح دارد.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if($editingEmployee || auth()->user()->can('can-access', ['employees', 'insert']))
        @php($employeeForm = $editingEmployee)
        <section class="card mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">{{ $employeeForm ? 'ویرایش پرونده پرسنلی' : 'ثبت کارمند جدید' }}</h5>
                    <small class="text-muted">اطلاعات هویتی، شغلی و تماس را در یک پرونده نگهداری کنید.</small>
                </div>
                @if($employeeForm)
                    <a class="btn btn-label-secondary" href="{{ route('employees.index') }}">انصراف از ویرایش</a>
                @endif
            </div>
            <div class="card-body">
                <form method="POST" action="{{ $employeeForm ? route('employees.update', $employeeForm) : route('employees.store') }}">
                    @csrf
                    @if($employeeForm) @method('PATCH') @endif
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="personnel_code">کد پرسنلی *</label>
                            <input required class="form-control" id="personnel_code" name="personnel_code" value="{{ old('personnel_code', $employeeForm?->personnel_code) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="first_name">نام *</label>
                            <input required class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $employeeForm?->first_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="last_name">نام خانوادگی *</label>
                            <input required class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $employeeForm?->last_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="national_id">کد ملی</label>
                            <input class="form-control" id="national_id" name="national_id" inputmode="numeric" value="{{ old('national_id', $employeeForm?->national_id) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="father_name">نام پدر</label>
                            <input class="form-control" id="father_name" name="father_name" value="{{ old('father_name', $employeeForm?->father_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="birth_date">تاریخ تولد</label>
                            <input class="form-control" id="birth_date" name="birth_date" data-jdp autocomplete="off" placeholder="۱۴۰۰/۰۱/۰۱" value="{{ old('birth_date', $employeeForm?->birth_date) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="gender">جنسیت</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">انتخاب کنید</option>
                                <option value="male" @selected(old('gender', $employeeForm?->gender) === 'male')>مرد</option>
                                <option value="female" @selected(old('gender', $employeeForm?->gender) === 'female')>زن</option>
                                <option value="other" @selected(old('gender', $employeeForm?->gender) === 'other')>سایر</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="mobile">تلفن همراه</label>
                            <input class="form-control" id="mobile" name="mobile" inputmode="tel" value="{{ old('mobile', $employeeForm?->mobile) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="phone">تلفن ثابت</label>
                            <input class="form-control" id="phone" name="phone" inputmode="tel" value="{{ old('phone', $employeeForm?->phone) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="email">ایمیل</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $employeeForm?->email) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="hire_date">تاریخ استخدام</label>
                            <input class="form-control" id="hire_date" name="hire_date" data-jdp autocomplete="off" placeholder="۱۴۰۰/۰۱/۰۱" value="{{ old('hire_date', $employeeForm?->hire_date) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="employment_type">نوع همکاری</label>
                            <input class="form-control" id="employment_type" name="employment_type" placeholder="رسمی، قراردادی، مشاور و ..." value="{{ old('employment_type', $employeeForm?->employment_type) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="job_title">عنوان شغلی</label>
                            <input class="form-control" id="job_title" name="job_title" value="{{ old('job_title', $employeeForm?->job_title) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="department">واحد سازمانی</label>
                            <input class="form-control" id="department" name="department" value="{{ old('department', $employeeForm?->department) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="insurance_number">شماره بیمه</label>
                            <input class="form-control" id="insurance_number" name="insurance_number" value="{{ old('insurance_number', $employeeForm?->insurance_number) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="iban">شماره شبا</label>
                            <input class="form-control" id="iban" name="iban" dir="ltr" placeholder="IR000000000000000000000000" value="{{ old('iban', $employeeForm?->iban) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="postal_code">کد پستی</label>
                            <input class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code', $employeeForm?->postal_code) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="status">وضعیت *</label>
                            <select required class="form-select" id="status" name="status">
                                @foreach($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $employeeForm?->status ?? 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="address">نشانی</label>
                            <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $employeeForm?->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="notes">توضیحات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $employeeForm?->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button class="btn btn-primary" type="submit">
                            <i class="mdi mdi-content-save-outline me-1"></i>{{ $employeeForm ? 'ذخیره تغییرات' : 'ایجاد پرونده پرسنلی' }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endif

    <section class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1">فهرست کارکنان</h5>
                <small class="text-muted">{{ number_format($employees->total()) }} پرونده ثبت‌شده</small>
            </div>
            <form class="d-flex gap-2" method="GET" action="{{ route('employees.index') }}">
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="نام، کد پرسنلی، کد ملی یا واحد">
                <button class="btn btn-outline-primary" type="submit">جست‌وجو</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>کارمند</th><th>اطلاعات سازمانی</th><th>تماس</th><th>وضعیت</th><th>مدارک</th><th class="text-end">عملیات</th></tr></thead>
                <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td><strong>{{ $employee->full_name }}</strong><br><small class="text-muted">کد پرسنلی: {{ $employee->personnel_code }}@if($employee->national_id) | کد ملی: {{ $employee->national_id }}@endif</small></td>
                        <td>{{ $employee->job_title ?: '—' }}<br><small class="text-muted">{{ $employee->department ?: 'واحد ثبت نشده' }}</small></td>
                        <td>{{ $employee->mobile ?: '—' }}<br><small class="text-muted">{{ $employee->email ?: '' }}</small></td>
                        <td><span class="badge bg-label-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">{{ $statusLabels[$employee->status] ?? $employee->status }}</span></td>
                        <td>
                            <button class="btn btn-sm btn-label-info" type="button" data-bs-toggle="collapse" data-bs-target="#employee-documents-{{ $employee->id }}">
                                {{ $employee->documents->count() }} مدرک
                            </button>
                        </td>
                        <td class="text-end text-nowrap">
                            @can('can-access', ['employees', 'edit'])
                                <a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('employees.index', ['edit' => $employee->id, 'q' => request('q')]) }}" title="ویرایش"><i class="mdi mdi-pencil-outline"></i></a>
                            @endcan
                            @can('can-access', ['employees', 'delete'])
                                <form class="d-inline" method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('پرونده این کارمند بایگانی شود؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-icon btn-outline-danger" type="submit" title="بایگانی"><i class="mdi mdi-archive-outline"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                    <tr class="collapse" id="employee-documents-{{ $employee->id }}">
                        <td colspan="6" class="bg-light-subtle">
                            <div class="p-2">
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    @forelse($employee->documents as $document)
                                        <div class="border rounded p-2 bg-white d-flex align-items-center gap-2">
                                            <i class="mdi mdi-file-document-outline text-primary"></i>
                                            <span><strong>{{ $document->title }}</strong><br><small class="text-muted">{{ $documentCategories[$document->category] ?? $document->category }} · {{ number_format($document->size / 1024, 1) }} KB</small></span>
                                            <a class="btn btn-sm btn-icon btn-outline-secondary" href="{{ route('employees.documents.download', [$employee, $document]) }}" title="دانلود"><i class="mdi mdi-download"></i></a>
                                            @can('can-access', ['employees', 'delete'])
                                                <form method="POST" action="{{ route('employees.documents.destroy', [$employee, $document]) }}" onsubmit="return confirm('این مدرک حذف شود؟')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-icon btn-outline-danger" type="submit"><i class="mdi mdi-delete-outline"></i></button>
                                                </form>
                                            @endcan
                                        </div>
                                    @empty
                                        <span class="text-muted">هنوز مدرکی برای این کارمند بارگذاری نشده است.</span>
                                    @endforelse
                                </div>
                                @can('can-access', ['employees', 'insert'])
                                    <form class="row g-2 align-items-end" method="POST" enctype="multipart/form-data" action="{{ route('employees.documents.store', $employee) }}">
                                        @csrf
                                        <div class="col-md-3"><label class="form-label">نوع مدرک</label><select class="form-select" name="category" required>@foreach($documentCategories as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                                        <div class="col-md-3"><label class="form-label">عنوان مدرک</label><input class="form-control" name="title" required></div>
                                        <div class="col-md-4"><label class="form-label">فایل (حداکثر ۱۰ مگابایت)</label><input class="form-control" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" required></div>
                                        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">بارگذاری</button></div>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">پرونده‌ای یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())<div class="card-footer">{{ $employees->links('pagination::bootstrap-5') }}</div>@endif
    </section>
@endsection

@push('scripts')
    <script>document.addEventListener('DOMContentLoaded', () => window.jalaliDatepicker?.startWatch({autoShow: true, autoHide: true}));</script>
@endpush
