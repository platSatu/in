<div class="sidebar-wrapper sidebar-theme">
    <nav id="sidebar">
        <div class="navbar-nav theme-brand flex-row  text-center">
            <div class="nav-logo">
                <div class="nav-item theme-logo">
                    @php
                        // Cache-busting: tempel query string berisi waktu-modifikasi file logo,
                        // supaya begitu Logo.png diganti, browser otomatis ambil versi baru
                        // (bukan versi lama yang ke-cache) tanpa perlu hard refresh manual.
                        $logoDiskPath = public_path('frontend/img/Logo.png');
                        $logoVersion = file_exists($logoDiskPath) ? filemtime($logoDiskPath) : time();
                    @endphp
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('frontend/img/Logo.png') }}?v={{ $logoVersion }}" class="navbar-logo"
                            alt="InaStudy New Logo">
                    </a>
                </div>
                <div class="nav-item theme-text">
                    <a href="{{ route('dashboard') }}" class="nav-link"> INASTUDY </a>
                </div>
            </div>
            <div class="nav-item sidebar-toggle">
                <div class="btn-toggle sidebarCollapse">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="feather feather-chevrons-left">
                        <polyline points="11 17 6 12 11 7"></polyline>
                        <polyline points="18 17 13 12 18 7"></polyline>
                    </svg>
                </div>
            </div>
        </div>
        <div class="profile-info">
            <div class="user-info">
                <div class="profile-img">
                    <img src="{{ auth()->user()?->image ? asset(auth()->user()->image) : asset('frontend') . '/src/assets/img/profile-30.png' }}"
                        alt="avatar">
                </div>
                <div class="profile-content">
                    <h6 class="">{{ auth()->user()?->name ?? '-' }}</h6>
                    <p class="">{{ auth()->user()?->roles?->pluck('name')?->implode(', ') ?: '-' }}</p>
                </div>
            </div>
        </div>

        <div class="shadow-bottom"></div>
        <ul class="list-unstyled menu-categories" id="accordionExample">
            <li class="menu">
                <a href="{{ route('dashboard') }}" data-bs-toggle="collapse" aria-expanded="false"
                    class="dropdown-toggle">
                    <div class="">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="feather feather-home">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span>Dashboard</span>
                    </div>
                </a>

            </li>

            <li class="menu menu-heading">
                <div class="heading"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="feather feather-minus">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg><span>APPLICATIONS</span></div>
            </li>
            <li class="menu">
                <a href="#menuKursus" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <div class="">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="feather feather-list">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                        <span>Invitations</span>
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="feather feather-chevron-right">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </a>
                <ul class="collapse submenu list-unstyled" id="menuKursus" data-bs-parent="#accordionExample">
                    {{-- <li>
                        <a href="{{ route('user.index') }}"> Data User Login</a>
                    </li>
                    <li>
                        <a href="{{ route('roles.index') }}"> Roles</a>
                    </li>
                    <li>
                        <a href="{{ route('roleuser.index') }}"> Role to user</a>
                    </li> --}}
                    <li>
                        <a href="{{ route('dashboard.invitation.index') }}"> Invitations</a>
                    </li>
                </ul>
            </li>

            {{--
                Menu di bawah ini di-render dinamis dari config/menu.php, digabung
                dengan App\Concerns\HasScopedAccess::canAccessPermission() (via
                App\Models\User, di-mixin ke auth()->user()). Satu entry di
                config/menu.php = satu <li> di dalam grup 'group'-nya; grup hanya
                dirender kalau minimal 1 item di dalamnya boleh diakses user yang
                sedang login. Ini menggantikan blok statis
                "@if hasRole('superadmin')" yang lama — sekaligus memperbaiki bug
                lama: dulu grup Pembayaran & Absensi TIDAK ikut dibungkus
                pengecekan role sama sekali, jadi tampil ke semua user yang login
                walau route-nya sendiri sudah dibatasi role:superadmin.
            --}}
            @php
                $sidebarMenuGroups = collect();
                if (auth()->check()) {
                    $sidebarUser = auth()->user();
                    $sidebarMenuGroups = collect(config('menu', []))
                        ->filter(fn ($item) => ($item['menu'] ?? true) !== false)
                        ->filter(fn ($item) => !empty($item['key']) && !empty($item['route']))
                        ->filter(fn ($item) => $sidebarUser->canAccessPermission($item['key']))
                        ->groupBy('group');
                }
            @endphp
            @foreach ($sidebarMenuGroups as $sidebarGroupLabel => $sidebarItems)
                @php
                    $sidebarMenuId = 'menu' . \Illuminate\Support\Str::studly(\Illuminate\Support\Str::slug($sidebarGroupLabel));
                @endphp
                <li class="menu">
                    <a href="#{{ $sidebarMenuId }}" data-bs-toggle="collapse" aria-expanded="false"
                        class="dropdown-toggle">
                        <div class="">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-list">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            <span>{{ $sidebarGroupLabel }}</span>
                        </div>
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </a>
                    <ul class="collapse submenu list-unstyled" id="{{ $sidebarMenuId }}"
                        data-bs-parent="#accordionExample">
                        @foreach ($sidebarItems as $sidebarItem)
                            <li>
                                <a href="{{ route($sidebarItem['route']) }}"> {{ $sidebarItem['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    </nav>
</div>
