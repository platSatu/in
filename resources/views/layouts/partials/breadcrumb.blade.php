{{--
    === BREADCRUMB DINAMIS ===
    Breadcrumb ini dibangun OTOMATIS dari nama route yang lagi aktif
    (mis. "quiz.form-question-option.edit"), bukan ditulis manual di tiap
    view. Jadi kalau ada halaman admin baru, breadcrumb-nya otomatis muncul
    benar tanpa perlu nambah HTML apapun di view halaman itu.

    Pola nama route di app ini: {section...}.{action}, contoh:
    - "quiz.form-question-option.edit" -> section: quiz.form-question-option, action: edit
    - "settings.payment-gateway.index" -> section: settings.payment-gateway, action: index
    - "tenant.index"                   -> section: tenant, action: index
    - "student.student.show"           -> section: student.student, action: show

    Segmen pertama section (mis. "quiz", "settings", "company") dianggap
    nama grup/modul dan TIDAK ditampilkan (supaya tidak jadi "Quiz > Form
    Question", cukup "Form Question") KECUALI section-nya cuma 1 segmen.
    Untuk kasus yang auto-humanize-nya kurang pas (typo, singkatan,
    ambigu setelah modul dibuang, branding kapitalisasi), dikoreksi lewat
    $sectionLabelOverrides di bawah — satu tempat, bukan di tiap view.
--}}
@php
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
    $isDashboard = $routeName === 'dashboard';

    $actionLabels = [
        'create' => 'Create',
        'edit' => 'Edit',
        'show' => 'Detail',
        'add-user' => 'Add User',
        'generate-qr' => 'Generate QR',
        'redeem' => 'Redeem',
        'resend' => 'Resend',
        'myData' => 'My Data',
    ];

    // Override label section (key = "section" hasil gabung segmen SEBELUM
    // segmen pertama dibuang, dipisah titik) yang auto-humanize-nya kurang pas.
    $sectionLabelOverrides = [
        'quiz.form' => 'Quiz Form', // "Form" saja terlalu umum/ambigu
        'student.student' => 'Data Student',
        'settings.whatsapp-gateway' => 'WhatsApp Gateway',
        'roleuser' => 'Role User',
        'historyuserlogin' => 'History User Login',
        'qrcodes' => 'QR Codes',
        'profile-bussines' => 'Business Profile',
        'company.profile' => 'Company Profile',
        'company.branch' => 'Company Branch',
        'company.division' => 'Company Division',
        'pembayaran.category' => 'Payment Category',
        'pembayaran.form' => 'Payment Form',
        'pembayaran.form-link' => 'Payment Form Link',
        'absensi.attendance-user-qr-code' => 'Attendance QR Code',
    ];

    $segments = $routeName ? explode('.', $routeName) : [];
    $actionSegment = count($segments) > 1 ? array_pop($segments) : null;

    // $segments sekarang cuma sisa "section" (sudah di-pop di atas). Dedupe
    // buat kasus kayak "student.student" (prefix modul == nama resource-nya).
    $sectionSegments = array_values(array_unique($segments));
    $sectionKey = implode('.', $sectionSegments);

    $sectionLabel = null;

    if ($sectionKey !== '') {
        if (isset($sectionLabelOverrides[$sectionKey])) {
            $sectionLabel = $sectionLabelOverrides[$sectionKey];
        } else {
            // Buang segmen pertama (nama modul/grup) kalau section-nya lebih dari 1 segmen.
            $labelSegments = count($sectionSegments) > 1
                ? array_slice($sectionSegments, 1)
                : $sectionSegments;

            $sectionLabel = collect($labelSegments)
                ->map(fn ($seg) => ucwords(str_replace(['-', '_'], ' ', $seg)))
                ->implode(' ');
        }
    }

    $sectionIndexRoute = $sectionKey . '.index';
    $crumbs = [];

    if (!$isDashboard && $sectionLabel !== null) {
        $crumbs[] = [
            'label' => $sectionLabel,
            // Section jadi link ke index-nya HANYA kalau kita lagi bukan di
            // halaman index itu sendiri, dan route index-nya memang ada.
            'route' => ($actionSegment && $actionSegment !== 'index' && \Illuminate\Support\Facades\Route::has($sectionIndexRoute))
                ? $sectionIndexRoute
                : null,
        ];
    }

    if ($actionSegment && $actionSegment !== 'index') {
        $crumbs[] = [
            'label' => $actionLabels[$actionSegment] ?? ucwords(str_replace(['-', '_'], ' ', $actionSegment)),
            'route' => null,
        ];
    }
@endphp

<div class="page-meta">
    <nav class="breadcrumb-style-one" aria-label="breadcrumb">
        <ol class="breadcrumb">
            @if($isDashboard)
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            @else
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
            @endif

            @foreach ($crumbs as $crumb)
                <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}" @if($loop->last) aria-current="page" @endif>
                    @if(!$loop->last && $crumb['route'])
                        <a href="{{ route($crumb['route']) }}">{{ $crumb['label'] }}</a>
                    @else
                        {{ $crumb['label'] }}
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
</div>
