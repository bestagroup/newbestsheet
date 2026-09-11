@extends('layouts.auth')
@section('title', 'ایجاد حساب و ثبت طرح')

@section('content')
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-4">
            <!-- Register Card -->
            <div class="card p-2">
                <div class="app-brand justify-content-center mt-5">
                    <a href="{{ url('/') }}" class="app-brand-link gap-2">
                        <span class="app-brand-logo demo">
                            <img src="{{ asset('assets/img/sinavclogo.png') }}" alt="توسعه دانش بنیان سینا" width="40">
                        </span>
                        <span class="app-brand-text demo text-heading fw-bold">توسعه دانش بنیان سینا</span>
                    </a>
                </div>

                <div class="card-body mt-2">
                    <h4 class="mb-2 fw-semibold">ایجاد حساب و ثبت طرح</h4>
                    <p class="mb-4">لطفاً اطلاعات زیر را با دقت وارد کنید</p>


                    @include('partials.alerts')


                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>خطا در ارسال فرم:</strong>
                            <ul class="mb-0 mt-2 pe-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="formAuthentication" class="mb-3" method="POST" action="{{ route('fullregister') }}" novalidate>
                        @csrf

                        <div class="form-floating form-floating-outline mb-3">
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" placeholder=" نام طرح" value="{{ old('title') }}" required>
                            <label for="title"> نام طرح</label>
                            @error('title')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-floating form-floating-outline mb-3">
                            <input type="text" class="form-control @error('CEO') is-invalid @enderror" id="CEO" name="CEO" placeholder=" نام مدیرعامل" value="{{ old('CEO') }}" required>
                            <label for="CEO"> نام مدیرعامل</label>
                            @error('CEO')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-floating form-floating-outline mb-3">
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" placeholder="شماره همراه" value="{{ old('phone') }}" required>
                            <label for="phone">شماره همراه</label>
                            @error('phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-floating form-floating-outline mb-3">
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="آدرس ایمیل" value="{{ old('email') }}" required>
                            <label for="email">آدرس ایمیل</label>
                            @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-password-toggle">
                                <div class="input-group input-group-merge">
                                    <div class="form-floating form-floating-outline">
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="رمز عبور" required>
                                        <label for="password">رمز عبور</label>
                                        @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <span class="input-group-text cursor-pointer"><i class="mdi mdi-eye-off-outline"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-password-toggle">
                                <div class="input-group input-group-merge">
                                    <div class="form-floating form-floating-outline">
                                        <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" placeholder="تأیید رمز عبور" required>
                                        <label for="password_confirmation">تکرار رمز عبور</label>
                                        @error('password_confirmation')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <span class="input-group-text cursor-pointer"><i class="mdi mdi-eye-off-outline"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 d-flex justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input @error('terms_accepted') is-invalid @enderror" type="checkbox" id="terms-accepted" name="terms_accepted" {{ old('terms_accepted') ? 'checked' : '' }} required>
                                <label class="form-check-label" for="terms-accepted">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">شرایط و قوانین</a> را با دقت مطالعه نموده‌ام.
                                </label>
                                @error('terms_accepted')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <button class="btn btn-primary d-grid w-100" type="submit">{{ __('ثبت اطلاعات') }}</button>
                        </div>
                    </form>

                    <p class="text-center">
                        <span>حساب کاربری دارید؟</span>
                        <a href="{{ route('login') }}">
                            <span>ورود به حساب</span>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">شرایط و قوانین</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div class="terms-and-conditions p-3" dir="rtl" lang="fa">
                        <h5 class="fw-bold mb-3">شرایط و قوانین ارسال طرح به شرکت توسعه دانش ‌بنیان سینا (سینا وی‌سی)</h5>

                        <h6 class="fw-bold">۱) پذیرش شرایط</h6>
                        <p>ارسال هرگونه اطلاعات، فایل یا فرم به معنای مطالعه، فهم و پذیرش کامل این شرایط و قوانین است. در صورت عدم موافقت با هر بخش از این سند، لطفاً از ارسال طرح خودداری کنید.</p>

                        <h6 class="fw-bold mt-3">۲) هدف از ارسال فایل</h6>
                        <ul class="mb-3">
                            <li>فایل ارسالی صرفاً برای <strong>آشنایی اولیه</strong> با تیم و کلیات طرح شماست.</li>
                            <li>تکمیل و ارسال فایل به منزله‌ی <strong>تضمین برگزاری جلسه</strong> با سینا وی‌سی نیست.</li>
                            <li>طرح شما توسط کارشناسان بررسی می‌شود و در صورت پذیرش اولیه، برای هماهنگی‌های بعدی با شما تماس گرفته خواهد شد.</li>
                        </ul>

                        <h6 class="fw-bold">۳) صداقت و شفافیت اطلاعات</h6>
                        <ul class="mb-3">
                            <li>شما تأیید می‌کنید که تمامی اطلاعات ارائه‌شده <strong>دقیق، شفاف و صادقانه</strong> است.</li>
                            <li>مسئولیت صحت ادعاها، نتایج، آمار و مستندات ارائه‌شده به عهده شماست.</li>
                        </ul>

                        <h6 class="fw-bold">۴) الزامات اولیه پذیرش بررسی</h6>
                        <ol class="mb-3">
                            <li><strong>وجود تیم:</strong> درخواست‌های بدون تیم بررسی نخواهند شد. ممکن است در جلسات بعدی حضور همه اعضای کلیدی الزامی باشد.</li>
                            <li><strong>وضعیت اجرا:</strong> ایده‌های اجرا نشده بررسی نمی‌شوند. طرح باید حداقل دارای <strong>دمو/نمونه اولیه/MVP</strong> باشد. طرح‌های بدون کاربر نهایی ممکن است به سختی پذیرفته شوند.</li>
                            <li><strong>حوزه فعالیت:</strong> طرح باید در <strong>زنجیره ارزش بنیاد مستضعفان</strong> قرار داشته باشد. قبل از ارسال، این موضوع را بررسی و تأیید کنید.</li>
                            <li><strong>سقف سرمایه درخواستی:</strong> سرمایه درخواستی باید <strong>کمتر از ۲۰۰ میلیارد ریال</strong> باشد.</li>
                        </ol>

                        <h6 class="fw-bold">۵) چارچوب سرمایه‌گذاری و سهام</h6>
                        <ul class="mb-3">
                            <li>سرمایه‌پذیر اعلام می‌کند آمادگی دارد تا سقف <strong>حداکثر ۴۹٪</strong> سهام را مطابق آیین‌نامه سرمایه‌گذاری سینا وی‌سی واگذار کند.</li>
                            <li>سینا وی‌سی ممکن است طی مذاکرات آتی تا سقف <strong>۴۹٪</strong> از شرکت سرمایه‌پذیر سهام دریافت کند.</li>
                        </ul>

                        <h6 class="fw-bold">۶) انواع حمایت‌های ممکن (به صلاحدید سینا وی‌سی)</h6>
                        <p class="mb-2">در صورت پذیرش، روش‌های حمایت می‌تواند بسته به صلاحدید شامل موارد زیر باشد:</p>
                        <ul class="mb-3">
                            <li>تأمین مالی</li>
                            <li>تأمین خط تولید</li>
                            <li>تأمین فضای انبارداری</li>
                            <li>تضمین بازار</li>
                            <li>پوشش ریسک</li>
                            <li>خدمات مشاوره کسب‌وکار</li>
                        </ul>

                        <h6 class="fw-bold">۷) ماهیت فعالیت سینا وی‌سی</h6>
                        <p>کسب‌وکار سینا وی‌سی <strong>سرمایه‌گذاری</strong> است و علاقه‌ای به اجرای مستقیم ایده‌ها ندارد. اطمینان داشته باشید که اطلاعات شما به‌منظور ارزیابی طرح استفاده شده و <strong>ایده شما دزدیده نخواهد شد</strong>.</p>

                        <h6 class="fw-bold">۸) تعهدات سرمایه‌پذیر در زمان ارسال</h6>
                        <ul class="mb-3">
                            <li>اعلام وجود تیم منسجم و هماهنگ و معرفی اعضای کلیدی.</li>
                            <li>اعلام وجود <strong>MVP</strong> یا دمو عملیاتی و ارائه مستندات مرتبط.</li>
                            <li>تأیید قرار داشتن طرح در حوزه زنجیره ارزش بنیاد مستضعفان.</li>
                            <li>اعلام آمادگی برای واگذاری سهام تا سقف ۴۹٪ و پذیرش سقف خروج ۵ ساله.</li>
                        </ul>

                        <h6 class="fw-bold">۹) حقوق مالکیت فکری و محرمانگی</h6>
                        <ul class="mb-3">
                            <li>مالکیت فکری کلیه محتواها و مستندات ارائه‌شده متعلق به شماست.</li>
                            <li>سینا وی‌سی اطلاعات ارسالی را برای ارزیابی طرح نگهداری می‌کند و تلاش می‌شود از افشا یا استفاده خارج از این چارچوب جلوگیری شود. ارائه اطلاعات حساس <strong>صرفاً در حد ضرورت</strong> توصیه می‌شود.</li>
                            <li>در صورت نیاز به محرمانگی مضاعف، امکان <strong>انعقاد توافق‌نامه عدم افشا (NDA)</strong> در مراحل بعدی وجود دارد.</li>
                        </ul>

                        <h6 class="fw-bold">۱۰) فرآیند ارزیابی و تصمیم‌گیری</h6>
                        <ol class="mb-3">
                            <li>بررسی اولیه فایل ارسالی توسط کارشناسان.</li>
                            <li>در صورت پذیرش اولیه، برگزاری جلسات معرفی و امکان درخواست حضور تمامی اعضای تیم.</li>
                            <li>انجام ارزیابی‌های فنی، مالی، حقوقی و بازار.</li>
                            <li>تعیین نوع و میزان حمایت (در صورت پذیرش نهایی) و ورود به مذاکرات قراردادی.</li>
                        </ol>

                        <h6 class="fw-bold">۱۱) محدودیت‌ها و سلب مسئولیت</h6>
                        <ul class="mb-3">
                            <li>ارسال اطلاعات تضمین‌کننده <strong>سرمایه‌گذاری</strong> نیست.</li>
                            <li>سینا وی‌سی حق <strong>رد هر طرح</strong> را در هر مرحله از فرآیند برای خود محفوظ می‌دارد.</li>
                        </ul>

                        <h6 class="fw-bold">۱۲) به‌روزرسانی شرایط</h6>
                        <p>سینا وی‌سی می‌تواند این شرایط و قوانین را به‌روز کند. نسخه‌های جدید پس از انتشار، جایگزین نسخه‌های قبلی خواهند شد. ادامه فرآیند ارسال به منزله پذیرش نسخه‌های به‌روز است.</p>

                        <h6 class="fw-bold">۱۳) تماس و پشتیبانی</h6>
                        <p>برای پرسش‌های مرتبط با شرایط و قوانین، فرآیند ارسال یا وضعیت بررسی، از طریق راه‌های ارتباطی معرفی‌شده در وب‌سایت رسمی سینا وی‌سی اقدام کنید.</p>

                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
{{--                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">قبول دارم</button>--}}
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const togglePasswordElements = document.querySelectorAll('.form-password-toggle');

            togglePasswordElements.forEach(function (wrapper) {
                const toggleButton = wrapper.querySelector('.input-group-text');
                const inputField = wrapper.querySelector('input');
                const icon = toggleButton.querySelector('i');

                toggleButton.addEventListener('click', function () {
                    const type = inputField.type === 'password' ? 'text' : 'password';
                    inputField.type = type;
                    icon.classList.toggle('mdi-eye-outline');
                    icon.classList.toggle('mdi-eye-off-outline');
                });
            });
        });
    </script>


    <script>
        @if (session('success'))
        toastr.success(@json(session('success')));
        @endif
        @if (session('error'))
        toastr.error(@json(session('error')));
        @endif
        @if ($errors->any())
        @foreach ($errors->all() as $error)
        toastr.error(@json($error));
        @endforeach
        @endif
    </script>
@endsection
