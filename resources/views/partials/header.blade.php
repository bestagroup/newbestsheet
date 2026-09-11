
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="layout-navbar-nav-left d-flex align-items-center">
    <!-- Burger menu (for mobile) -->
    <a class="nav-item nav-link px-0 me-xl-4 d-xl-none layout-menu-toggle" href="javascript:void(0)">
      <i class="mdi mdi-menu mdi-24px"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <ul class="navbar-nav flex-row align-items-center ms-auto">

      <!-- Search -->
{{--      <li class="nav-item me-3">--}}
{{--        <a class="nav-link btn btn-icon rounded-pill" href="#">--}}
{{--          <i class="mdi mdi-magnify mdi-24px"></i>--}}
{{--        </a>--}}
{{--      </li>--}}

      <!-- Notifications -->
      <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3">
        <a class="nav-link btn btn-icon rounded-pill position-relative" href="#" data-bs-toggle="dropdown" aria-expanded="false" aria-label="اعلان‌ها">
          <i class="mdi mdi-bell-outline mdi-24px"></i>
          @if(($headerUnreadNotificationCount ?? 0) > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm">
              {{ min($headerUnreadNotificationCount, 99) }}
            </span>
          @endif
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 390px; max-width: min(92vw, 430px);">
          <li class="dropdown-menu-header px-3 pt-3 pb-2">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <div>
                <h6 class="mb-0 fw-bold">اعلان‌ها و هشدارها</h6>
                <small class="text-muted">آخرین رویدادهای نیازمند توجه شما</small>
              </div>
              @if(($headerUnreadNotificationCount ?? 0) > 0)
                <button type="button" id="headerMarkAllNotificationsRead" class="btn btn-sm btn-outline-primary">خواندن همه</button>
              @endif
            </div>
          </li>
          <li><div class="dropdown-divider my-0"></div></li>
          <li style="max-height: 420px; overflow-y: auto;">
            <div class="list-group list-group-flush">
              @forelse(($headerNotifications ?? collect()) as $notification)
                @php
                  $severity = (string) ($notification->data['severity'] ?? 'info');
                  $severityClass = match ($severity) {
                    'overdue' => 'danger',
                    'critical' => 'warning',
                    'warning' => 'warning',
                    'success' => 'success',
                    default => 'info',
                  };
                  $category = match ((string) ($notification->data['category'] ?? 'system')) {
                    'sla' => 'SLA / سررسید',
                    'workflow' => 'فرایند',
                    'calendar' => 'تقویم',
                    'correspondence' => 'مکاتبات',
                    'minute' => 'صورتجلسه',
                    default => 'سیستم',
                  };
                @endphp
                <button type="button"
                        class="list-group-item list-group-item-action border-0 px-3 py-3 header-notification-open {{ $notification->read_at ? '' : 'bg-label-primary' }}"
                        data-id="{{ $notification->id }}"
                        data-url="{{ $notification->data['url'] ?? route('notifications.index') }}">
                  <div class="d-flex gap-3 align-items-start text-start">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-label-{{ $severityClass }}" style="width: 40px; height: 40px; flex: 0 0 40px;">
                      <i class="mdi {{ $notification->data['icon'] ?? 'mdi-bell-outline' }} mdi-20px"></i>
                    </span>
                    <div class="flex-grow-1 min-w-0">
                      <div class="d-flex justify-content-between gap-2 align-items-start">
                        <div class="fw-semibold text-body">{{ $notification->data['title'] ?? 'اعلان' }}</div>
                        @if(!$notification->read_at)<span class="badge bg-primary rounded-pill">جدید</span>@endif
                      </div>
                      <small class="text-muted d-block mt-1" style="line-height: 1.7;">{{ \Illuminate\Support\Str::limit($notification->data['message'] ?? '', 110) }}</small>
                      <div class="d-flex align-items-center gap-2 mt-2">
                        <span class="badge bg-label-{{ $severityClass }}">{{ $category }}</span>
                        <small class="text-muted">{{ jdate($notification->created_at)->ago() }}</small>
                      </div>
                    </div>
                  </div>
                </button>
              @empty
                <div class="text-center text-muted py-4 px-3"><i class="mdi mdi-bell-check-outline mdi-36px d-block mb-2"></i>اعلان جدیدی وجود ندارد.</div>
              @endforelse
            </div>
          </li>
          <li><div class="dropdown-divider my-0"></div></li>
          <li class="p-2">
            <a href="{{ route('notifications.index') }}" class="btn btn-primary btn-sm w-100">
              <i class="mdi mdi-bell-ring-outline me-1"></i> مشاهده مرکز اعلان‌ها
            </a>
          </li>
        </ul>
      </li>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
          document.querySelectorAll('.header-notification-open').forEach(function (button) {
            button.addEventListener('click', async function () {
              await fetch(`{{ url('panel/notifications') }}/${this.dataset.id}/read`, {
                method: 'POST', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}
              });
              window.location.href = this.dataset.url;
            });
          });
          document.getElementById('headerMarkAllNotificationsRead')?.addEventListener('click', async function (event) {
            event.preventDefault(); event.stopPropagation();
            await fetch('{{ route('notifications.read-all') }}', {
              method: 'POST', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}
            });
            window.location.reload();
          });
        });
      </script>

      <!-- Theme Toggle -->
      <li class="nav-item me-3">
        <form method="POST" action="{{ route('toggle-theme') }}" class="d-inline">
          @csrf
          <button type="submit" class="nav-link btn btn-icon rounded-pill" title="تغییر تم" aria-label="تغییر تم">
            @if(session('theme') === 'theme-default-dark')
              <i class="mdi mdi-weather-sunny mdi-24px"></i>
            @else
              <i class="mdi mdi-weather-night mdi-24px"></i>
            @endif
          </button>
        </form>
      </li>

      <!-- User Dropdown -->
      <li class="nav-item navbar-dropdown dropdown-user dropdown">
        <a class="nav-link dropdown-toggle hide-arrow" href="#" data-bs-toggle="dropdown">
          <div class="avatar avatar-online">
              @if(Auth::user()->gender == 1)
                  <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle" />
              @elseif(Auth::user()->gender == 2)
                  <img src="{{ asset('assets/img/avatars/8.png') }}" alt class="w-px-40 h-auto rounded-circle" />
              @else
                  <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle" />
              @endif
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="#">
              <div class="d-flex">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar avatar-online">
                      @if(Auth::user()->gender == 1)
                          <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle" />
                      @elseif(Auth::user()->gender == 2)
                          <img src="{{ asset('assets/img/avatars/8.png') }}" alt class="w-px-40 h-auto rounded-circle" />
                      @else
                          <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 h-auto rounded-circle" />
                      @endif
                  </div>
                </div>
                <div class="flex-grow-1">
                  <span class="fw-medium d-block" style="font-size: 0.7rem">{{Auth::user()->name}}</span>
                  <small class="text-muted">مدیر</small>
                </div>
              </div>
            </a>
          </li>
          <li>
            <div class="dropdown-divider"></div>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('profile') }}">
              <i class="mdi mdi-account-outline me-2"></i>
              <span class="align-middle">پروفایل من</span>
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="#">
              <i class="mdi mdi-cog me-2"></i>
              <span class="align-middle">تنظیمات</span>
            </a>
          </li>
          <li>
              <a href="" class="dropdown-item" onclick="submitLogout(event)">
                  <i class="mdi mdi-logout me-2"></i>
                  <span class="align-middle">خروج</span>
              </a>
          </li>
            <script>
                function submitLogout(event) {
                    event.preventDefault();

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('logout') }}';

                    const csrfToken = '{{ csrf_token() }}';

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfToken;

                    form.appendChild(csrfInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            </script>

        </ul>
      </li>
    </ul>
  </div>
</nav>
