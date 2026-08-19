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
                        <img src="{{ asset('frontend/img/Logo.png') }}?v={{ $logoVersion }}" class="navbar-logo" alt="InaStudy New Logo">
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
                    <img src="{{ auth()->user()?->image ? asset(auth()->user()->image) : asset('frontend') . '/src/assets/img/profile-30.png' }}" alt="avatar">
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
                        <span>Academic</span>
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
                    <li>
                        <a href="{{ route('user.index') }}"> Data User Login</a>
                    </li>
                    <li>
                        <a href="{{ route('roles.index') }}"> Roles</a>
                    </li>
                    <li>
                        <a href="{{ route('roleuser.index') }}"> Role to user</a>
                    </li>
                     <li>
                        <a href="{{ route('dashboard.invitation.index') }}"> Invitations</a>
                    </li>
                </ul>
            </li>

            <li class="menu">
                <a href="#menuPembayaran" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
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
                        <span>Pembayaran</span>
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="feather feather-chevron-right">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </a>
                <ul class="collapse submenu list-unstyled" id="menuPembayaran" data-bs-parent="#accordionExample">
                    <li>
                        <a href="{{ route('pembayaran.category.index') }}"> Category</a>
                    </li>
                    <li>
                        <a href="{{ route('pembayaran.form.index') }}"> Setting Forms</a>
                    </li>
                    <li>
                        <a href="{{ route('pembayaran.form-link.index') }}"> Form to Users</a>
                    </li>
                </ul>
            </li>

            <li class="menu">
                <a href="#menuAbsensi" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
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
                        <span>Absensi</span>
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="feather feather-chevron-right">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </a>
                <ul class="collapse submenu list-unstyled" id="menuAbsensi" data-bs-parent="#accordionExample">
                    <li>
                        <a href="{{ route('absensi.attendance.index') }}"> Absensi </a>
                    </li>
                    <li>
                        <a href="{{ route('absensi.attendance-setting.index') }}"> Settings</a>
                    </li>
<li>
                        <a href="{{ route('absensi.attendance-user-qr-code.index') }}"> QrCode User</a>
                    </li>
                    <li>
                        <a href="{{ route('absensi.academic-calendar.index') }}"> Academic Calendar</a>
                    </li>
                </ul>
            </li>


            @if (auth()->check() && auth()->user()->hasRole('superadmin'))
              <li class="menu">
                    <a href="#menuStudent" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
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
                            <span>Students</span>
                        </div>
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </a>
                    <ul class="collapse submenu list-unstyled" id="menuStudent" data-bs-parent="#accordionExample">
                        <li>
                            <a href="{{ route('student.student.index') }}"> Data Student</a>
                        </li>
                      

                    </ul>
                </li>
                <li class="menu">
                    <a href="#menuCompany" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
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
                            <span>Company</span>
                        </div>
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </a>
                    <ul class="collapse submenu list-unstyled" id="menuCompany" data-bs-parent="#accordionExample">
                        <li>
                            <a href="{{ route('company.profile.index') }}"> Company Profile </a>
                        </li>
                        <li>
                            <a href="{{ route('company.branch.index') }}"> Company Branch </a>
                        </li>
                        <li>
                            <a href="{{ route('company.division.index') }}"> Company Division </a>
                        </li>
                    </ul>
                </li>
                <li class="menu">
                    <a href="#menuQuiz" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
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
                            <span>Quiz / University</span>
                        </div>
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </a>
                    <ul class="collapse submenu list-unstyled" id="menuQuiz" data-bs-parent="#accordionExample">
                        <li>
                            <a href="{{ route('quiz.form.index') }}"> Forms</a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.form-question.index') }}"> Form Questions</a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.form-question-option.index') }}"> Form Question Options</a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.form-submission.index') }}"> Form Submission </a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.form-answer.index') }}"> Form Answer </a>
                        </li>
                       <li>
                            <a href="{{ route('city.index') }}"> City </a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.major.index') }}"> Major </a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.university.index') }}"> University </a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.university-profile.index') }}"> University Profile </a>
                        </li>
                       
                        <li>
                            <a href="{{ route('quiz.university-album.index') }}"> University Album</a>
                        </li>
                         <li>
                            <a href="{{ route('quiz.university-album-photo.index') }}"> University Album Photo</a>
                        </li>
                        <li>
                            <a href="{{ route('quiz.whatsapp-template.index') }}"> Whatsapp Template </a>
                        </li>
                         <li>
                            <a href="{{ route('quiz.setting-university.index') }}"> Setting University </a>
                        </li>
                         <li>
                            <a href="{{ route('qrcodes.index') }}"> Generate Link to Qrcode </a>
                        </li>
                    </ul>
                </li>
                <li class="menu">
                    <a href="#menuUsers" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
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
                            <span>Users</span>
                        </div>
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </a>
                    <ul class="collapse submenu list-unstyled" id="menuUsers" data-bs-parent="#accordionExample">
                        <li>
                            <a href="{{ route('user.index') }}"> Data User</a>
                        </li>
                        <li>
                            <a href="{{ route('roles.index') }}"> Roles</a>
                        </li>
                        <li>
                            <a href="{{ route('roleuser.index') }}"> Role to user</a>
                        </li>
                        <li>
                            <a href="{{ route('historyuserlogin.index') }}"> User Login </a>
                        </li>
                    </ul>
                </li>
                <li class="menu">
                    <a href="#menuSettings" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
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
                            <span>Settings</span>
                        </div>
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                    </a>
                    <ul class="collapse submenu list-unstyled" id="menuSettings" data-bs-parent="#accordionExample">
                        <li>
                            <a href="{{ route('tenant.index') }}"> Bussines Profile</a>
                        </li>
                        <li>
                            <a href="{{ route('settings.payment-gateway.index') }}"> Payment Gateway</a>
                        </li>
                        <li>
                            <a href="{{ route('settings.whatsapp-gateway.index') }}"> WhatsApp Gateway</a>
                        </li>
                    </ul>
                </li>
            @endif
        </ul>
    </nav>
</div>
