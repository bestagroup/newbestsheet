
<aside class="layout-menu menu-vertical menu bg-menu-theme" data-bg-class="bg-menu-theme" id="layout-menu" style="touch-action: none;user-select: none;-webkit-user-drag: none;-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">

    <div class="app-brand demo py-3">

        <a class="app-brand-link" href="{{Route('dashboard')}}">

            <span class="app-brand-logo demo">
                  <img src="{{ asset('assets/img/sinavclogo.png') }}" alt="توسعه دانش بنیان سینا" width="27">
            </span>
            <span class="app-brand-text demo menu-text fw-bold ms-2">SinaVC</span>
        </a>
        <a class="layout-menu-toggle menu-link text-large ms-auto" href="javascript:void(0);">
            <svg fill="none" height="22" viewbox="0 0 22 22" width="22" xmlns="http://www.w3.org/2000/svg">
                <path d="M11.4854 4.88844C11.0081 4.41121 10.2344 4.41121 9.75715 4.88844L4.51028 10.1353C4.03297 10.6126 4.03297 11.3865 4.51028 11.8638L9.75715 17.1107C10.2344 17.5879 11.0081 17.5879 11.4854 17.1107C11.9626 16.6334 11.9626 15.8597 11.4854 15.3824L7.96672 11.8638C7.48942 11.3865 7.48942 10.6126 7.96672 10.1353L11.4854 6.61667C11.9626 6.13943 11.9626 5.36568 11.4854 4.88844Z"
                    fill="currentColor" fill-opacity="0.6"></path>
                <path d="M15.8683 4.88844L10.6214 10.1353C10.1441 10.6126 10.1441 11.3865 10.6214 11.8638L15.8683 17.1107C16.3455 17.5879 17.1192 17.5879 17.5965 17.1107C18.0737 16.6334 18.0737 15.8597 17.5965 15.3824L14.0778 11.8638C13.6005 11.3865 13.6005 10.6126 14.0778 10.1353L17.5965 6.61667C18.0737 6.13943 18.0737 5.36568 17.5965 4.88844C17.1192 4.41121 16.3455 4.41121 15.8683 4.88844Z"
                    fill="currentColor" fill-opacity="0.38"></path>
            </svg>
        </a>
    </div>
    <div class="menu-inner-shadow"></div>
    <ul class="menu-inner py-1">

        @foreach($menupanels as $menupanel)
            @php
                $hasVisibleSubmenus = $menupanel->accessible_submenus->filter(fn($submenu) =>
                Gate::allows('can-access', [$submenu->slug, 'view']))->isNotEmpty();
                $shouldDisplay = $menupanel->is_public || $hasVisibleSubmenus || Gate::allows('can-access', [$menupanel->slug, 'view']);
                if (!$shouldDisplay) continue;
                $isActive = $menupanel->is_active ? 'active open bg-red' : '';
                $isToggle = $menupanel->submenu == 1 && $hasVisibleSubmenus;
            @endphp

            <li class="menu-item {{ $isActive }}">
                <a class="menu-link d-flex align-items-center {{ $isToggle ? 'menu-toggle' : '' }}"
                   href="{{ $isToggle ? 'javascript:void(0);' : url($menupanel->slug) }}">
                    <i class="menu-icon tf-icons mdi {{ $menupanel->icon }} ms-2"></i>
                    <div class="flex-grow-1">{{ $menupanel->label }}</div>
                    @if(!empty($menupanel->badge))
                        <div class="badge bg-primary rounded-pill ms-auto">{{ $menupanel->badge }}</div>
                    @endif
                </a>
                @if($isToggle)
                    <ul class="menu-sub px-2">
                        @foreach($menupanel->accessible_submenus as $submenu)
                            @if(Gate::allows('can-access', [$submenu->slug, 'view']))
                                <li class="menu-item {{ request()->segment(2) == $submenu->slug ? 'active' : '' }}">
                                    <a class="menu-link d-flex align-items-center"
                                       href="{{ url('panel/' . $submenu->slug) }}">
                                        <div>{{ $submenu->label }}</div>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</aside>
