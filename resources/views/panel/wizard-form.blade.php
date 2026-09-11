@extends('layouts.base')

@section('title', 'فرم ثبت طرح')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bs-stepper@1.7.0/dist/css/bs-stepper.min.css">
    <style>
        body { direction: rtl; }
        .wizard-2col-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 0;
            margin: 40px auto;
            max-width: 900px;
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 0 20px #edeaff38;
            padding: 0;
            min-height: 450px;
        }
        .wizard-stepper-panel {
            min-width: 300px;
            max-width: 340px;
            background: #f8f8fe;
            border-radius: 0 1.2rem 1.2rem 0;
            padding: 2.2rem 1rem 2.2rem 1.2rem;
            box-shadow: 0 0 0.7rem #edeaff24;
            overflow-y: auto;
            height: 100%;
        }
        .bs-stepper-header {
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }
        .step-trigger {
            width: 100%;
            display: flex;
            flex-direction: row-reverse;
            gap: .7rem;
            align-items: flex-start;
            background: none;
            border: none;
            padding: .4rem 0;
            text-align: right;
            justify-content: flex-end;
            transition: background .15s;
        }
        .step.active .step-trigger, .step:hover .step-trigger { background: #edeaff; border-radius: 0.6rem;}
        .bs-stepper-circle {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #696cff;
            color: #696cff;
            font-weight: bold;
            display: flex; justify-content: center; align-items: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            transition: background .15s, color .15s;
        }
        .step.active .bs-stepper-circle {
            background: #696cff;
            color: #fff;
        }
        .step.crossed + .line { background: #696cff; }
        .line {
            width: 2px;
            height: 32px;
            margin: .2rem auto;
            background: #d2d2e2;
        }
        .bs-stepper-label {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            text-align: right;
        }
        .bs-stepper-title {
            font-weight: bold;
            font-size: 1.06rem;
        }
        .bs-stepper-subtitle {
            font-size: 0.93rem;
            color: #888;
        }
        /* فرم سمت چپ همیشه جای ثابت */
        .wizard-form-panel {
            flex: 1 1 0%;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 3.2rem 1rem;
        }
        .wizard-form-box {
            width: 380px;
            background: #fafaff;
            border-radius: 1.1rem;
            box-shadow: 0 4px 24px #e4e8fc2e;
            padding: 2.2rem 2rem 1.2rem 2rem;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            min-height: 290px;
            animation: fadein .24s;
        }
        @keyframes fadein {
            from { opacity:0; transform: translateY(24px);}
            to   { opacity:1; transform: none;}
        }
        .wizard-form-box h5 {text-align: center; font-weight: bold; margin-bottom: .8rem;}
        .wizard-form-box .form-label {text-align: right;}
        .wizard-form-box .btn {min-width: 80px;}
        .wizard-form-box .btn + .btn {margin-right: .7rem;}
        .wizard-form-box .btn[disabled] {opacity: .6;}
        .wizard-form-box .form-control::placeholder { color: #aaa; }

        @media (max-width: 900px) {
            .wizard-2col-container {flex-direction: column-reverse; max-width: 98vw;}
            .wizard-stepper-panel, .wizard-form-panel, .wizard-form-box {border-radius: 1.2rem;}
            .wizard-form-panel, .wizard-stepper-panel {padding: 1.2rem 0.5rem;}
            .wizard-form-box {width: 98%; padding:1.2rem;}
        }
    </style>
@endpush

@section('content')
    <div class="wizard-2col-container">
        @php
            $steps = [
                ['title'=>'بررسی اولیه','subtitle'=>'ثبت اولیه طرح و مدارک'],
                ['title'=>'غربالگری','subtitle'=>'بررسی شرایط پایه'],
                ['title'=>'ارزیابی اولیه','subtitle'=>'امتیاز تیم و بازار'],
                ['title'=>'ارزیابی موشکافانه','subtitle'=>'تحلیل عمیق طرح'],
                ['title'=>'تائیدیه مدیرعامل سینا وی‌سی','subtitle'=>'تصمیم مدیرعامل اول'],
                ['title'=>'تائیدیه مدیرعامل دانشمند','subtitle'=>'تصمیم مدیرعامل دوم'],
                ['title'=>'تصویب هیئت مدیره سینا وی‌سی','subtitle'=>'بررسی نهایی هیئت مدیره'],
                ['title'=>'ارزش‌گذاری','subtitle'=>'محاسبه ارزش طرح'],
                ['title'=>'ارائه در کمیته ارزش‌گذاری','subtitle'=>'جلسه کمیته ارزش‌گذاری'],
                ['title'=>'توافق قراردادی','subtitle'=>'مذاکره و توافق قرارداد'],
                ['title'=>'تصویب قرارداد','subtitle'=>'تصویب رسمی قرارداد'],
                ['title'=>'عقد قرارداد','subtitle'=>'امضای نهایی قرارداد'],
                ['title'=>'پایان دوره ارزش‌آفرینی','subtitle'=>'پایان عملیات ارزش‌آفرینی'],
                ['title'=>'خروج از طرح','subtitle'=>'اتمام و تسویه طرح'],
            ];
        @endphp
        {{-- Stepper Panel (Right) --}}
        <div class="wizard-stepper-panel">
            <div class="bs-stepper vertical" id="bigStepper">
                <div class="bs-stepper-header">
                    @foreach ($steps as $i => $step)
                        <div class="step{{ $i==0 ? ' active' : '' }}" data-target="#step-{{ $i+1 }}">
                            <button class="step-trigger" type="button">
                                <span class="bs-stepper-circle">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="bs-stepper-label">
                                <span class="bs-stepper-title">{{ $step['title'] }}</span>
                                <span class="bs-stepper-subtitle">{{ $step['subtitle'] }}</span>
                            </span>
                            </button>
                        </div>
                        @if(!$loop->last)
                            <div class="line"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        {{-- Form Panel (Left, always fixed position!) --}}
        <div class="wizard-form-panel">
            <form method="POST" onsubmit="return false" style="width:100%">
                @csrf
                @foreach ($steps as $i => $step)
                    <div id="step-{{ $i+1 }}" class="wizard-form-box step-content{{ $i==0 ? ' active' : '' }}" style="{{ $i==0 ? '' : 'display:none' }}">
                        <h5>{{ $step['title'] }}</h5>
                        <div class="mb-2 text-center" style="font-size: .97rem; color:#555;">{{ $step['subtitle'] }}</div>
                        <div class="mb-3">
                            <label class="form-label">یادداشت / توضیح مرحله</label>
                            <textarea class="form-control" rows="3" placeholder="توضیحات مربوط به مرحله {{ $step['title'] }}"></textarea>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button"
                                    class="btn btn-outline-secondary btn-prev"
                                {{ $i==0 ? 'disabled' : '' }}>قبلی</button>
                            @if($i+1 < count($steps))
                                <button type="button" class="btn btn-primary btn-next">بعدی</button>
                            @else
                                <button type="submit" class="btn btn-success">ثبت نهایی</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bs-stepper@1.7.0/dist/js/bs-stepper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const steps = document.querySelectorAll('.step');
            const contents = document.querySelectorAll('.wizard-form-box.step-content');
            let current = 0;

            function showStep(idx) {
                steps.forEach((s, i) => s.classList.toggle('active', i===idx));
                contents.forEach((c, i) => {
                    if(i===idx){
                        c.classList.add('active');
                        c.style.display = '';
                    } else {
                        c.classList.remove('active');
                        c.style.display = 'none';
                    }
                });
                // اسکرول به مرحله فعال
                const activeStep = document.querySelector('.step.active');
                if(activeStep && activeStep.scrollIntoView) activeStep.scrollIntoView({block:'center', behavior:'smooth'});
            }

            // Step click
            steps.forEach((step, i) => {
                step.querySelector('.step-trigger').addEventListener('click', function () {
                    showStep(i);
                    current = i;
                });
            });
            // Next/Prev btn
            document.querySelectorAll('.btn-next').forEach((btn, i) => {
                btn.addEventListener('click', function() {
                    if(current < steps.length-1) {
                        showStep(current+1);
                        current++;
                    }
                });
            });
            document.querySelectorAll('.btn-prev').forEach((btn, i) => {
                btn.addEventListener('click', function() {
                    if(current > 0) {
                        showStep(current-1);
                        current--;
                    }
                });
            });

            showStep(current);
        });
    </script>
@endpush
