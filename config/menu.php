<?php

/**
 * Registry menu/permission modul admin. SATU sumber data dipakai untuk 3 hal:
 *  1. Katalog tabel `permissions` (lihat App\Models\Permission::syncFromRegistry()).
 *  2. Pengecekan akses per-route (lihat App\Http\Middleware\PermissionMiddleware,
 *     'key' di sini = argumen pertama middleware `permission:<key>` di routes/web.php).
 *  3. Rendering sidebar (resources/views/layouts/partials/sidebar.blade.php).
 *
 * 'key' HARUS sama dengan prefix nama route grup-nya di routes/web.php (mis.
 * grup route 'company.division.*' -> key 'company.division') supaya 1 modul
 * di sini konsisten dengan 1 grup middleware permission di sana.
 *
 * 'group' = judul submenu di sidebar (urutan array di bawah = urutan tampil).
 * Entry dengan 'menu' => false artinya modul itu tetap terdaftar sebagai
 * permission (route-nya tetap bisa di-akses admin yang di-grant), tapi
 * sengaja TIDAK punya link di sidebar — sama seperti kondisi sebelum
 * perubahan ini (modul-modul ini juga belum pernah ditautkan di sidebar).
 *
 * Menambah modul baru di kemudian hari: tambah 1 entry di sini, lalu jalankan
 * `php artisan permissions:sync` supaya tabel permissions ikut ter-update.
 */

return [
    // ================= Pembayaran =================
    ['key' => 'pembayaran.category', 'label' => 'Category', 'route' => 'pembayaran.category.index', 'group' => 'Pembayaran', 'icon' => 'list'],
    ['key' => 'pembayaran.form', 'label' => 'Setting Forms', 'route' => 'pembayaran.form.index', 'group' => 'Pembayaran', 'icon' => 'list'],
    ['key' => 'pembayaran.form-link', 'label' => 'Form to Users', 'route' => 'pembayaran.form-link.index', 'group' => 'Pembayaran', 'icon' => 'list'],

    // ================= Absensi =================
    ['key' => 'absensi.attendance', 'label' => 'Absensi', 'route' => 'absensi.attendance.index', 'group' => 'Absensi', 'icon' => 'list'],
    ['key' => 'absensi.attendance-setting', 'label' => 'Settings', 'route' => 'absensi.attendance-setting.index', 'group' => 'Absensi', 'icon' => 'list'],
    ['key' => 'absensi.attendance-user-qr-code', 'label' => 'QrCode User', 'route' => 'absensi.attendance-user-qr-code.index', 'group' => 'Absensi', 'icon' => 'list'],
    ['key' => 'absensi.academic-calendar', 'label' => 'Academic Calendar', 'route' => 'absensi.academic-calendar.index', 'group' => 'Absensi', 'icon' => 'list'],

    // ================= Students =================
    ['key' => 'student.student', 'label' => 'Data Student', 'route' => 'student.student.index', 'group' => 'Students', 'icon' => 'list'],

    // ================= Invitation =================
    // Sebelumnya menu "Invitations" ini statis (hardcoded, tanpa pengecekan
    // permission apa pun) di sidebar.blade.php & rute-nya cuma dibungkus
    // 'auth' polos — jadi SEMUA user yang login (role apa pun) otomatis bisa
    // akses & CRUD, tidak bisa dibatasi lewat checklist di halaman edit Role.
    // Didaftarkan di sini supaya modul ini ikut sistem permission yang sama
    // dengan modul lain (bisa dicentang per role) & tampil dinamis di
    // sidebar cuma untuk role yang di-grant.
    ['key' => 'invitation', 'label' => 'Invitations', 'route' => 'dashboard.invitation.index', 'group' => 'Invitation', 'icon' => 'list'],

    // ================= Quiz =================
    ['key' => 'quiz.form', 'label' => 'Forms', 'route' => 'quiz.form.index', 'group' => 'Quiz', 'icon' => 'list'],
    ['key' => 'quiz.form-section', 'label' => 'Form Sections', 'route' => 'quiz.form-section.index', 'group' => 'Quiz', 'icon' => 'list'],
    ['key' => 'quiz.form-question', 'label' => 'Form Questions', 'route' => 'quiz.form-question.index', 'group' => 'Quiz', 'icon' => 'list'],
    ['key' => 'quiz.form-question-option', 'label' => 'Form Question Options', 'route' => 'quiz.form-question-option.index', 'group' => 'Quiz', 'icon' => 'list'],
    ['key' => 'quiz.form-submission', 'label' => 'Form Submission', 'route' => 'quiz.form-submission.index', 'group' => 'Quiz', 'icon' => 'list'],
    ['key' => 'quiz.form-answer', 'label' => 'Form Answer', 'route' => 'quiz.form-answer.index', 'group' => 'Quiz', 'icon' => 'list'],
    ['key' => 'quiz.whatsapp-template', 'label' => 'Whatsapp Template', 'route' => 'quiz.whatsapp-template.index', 'group' => 'Quiz', 'icon' => 'list'],
    ['key' => 'quiz.class-schedule', 'label' => 'Class Schedule', 'route' => 'quiz.class-schedule.index', 'group' => 'Quiz', 'icon' => 'list'],

    // ================= University =================
    ['key' => 'country', 'label' => 'Country', 'route' => 'country.index', 'group' => 'University', 'icon' => 'list'],
    ['key' => 'city', 'label' => 'City', 'route' => 'city.index', 'group' => 'University', 'icon' => 'list'],
    ['key' => 'quiz.major', 'label' => 'Major', 'route' => 'quiz.major.index', 'group' => 'University', 'icon' => 'list'],
    ['key' => 'quiz.university', 'label' => 'University', 'route' => 'quiz.university.index', 'group' => 'University', 'icon' => 'list'],
    ['key' => 'quiz.university-profile', 'label' => 'University Profile', 'route' => 'quiz.university-profile.index', 'group' => 'University', 'icon' => 'list'],
    ['key' => 'quiz.university-album', 'label' => 'University Album', 'route' => 'quiz.university-album.index', 'group' => 'University', 'icon' => 'list'],
    ['key' => 'quiz.university-album-photo', 'label' => 'University Album Photo', 'route' => 'quiz.university-album-photo.index', 'group' => 'University', 'icon' => 'list'],
    ['key' => 'quiz.setting-university', 'label' => 'Setting University', 'route' => 'quiz.setting-university.index', 'group' => 'University', 'icon' => 'list'],
    ['key' => 'quiz.university-application', 'label' => 'University Applications', 'route' => 'quiz.university-application.index', 'group' => 'University', 'icon' => 'list'],

    // ================= Company =================
    ['key' => 'company.profile', 'label' => 'Company Profile', 'route' => 'company.profile.index', 'group' => 'Company', 'icon' => 'list'],
    ['key' => 'company.branch', 'label' => 'Company Branch', 'route' => 'company.branch.index', 'group' => 'Company', 'icon' => 'list'],
    ['key' => 'company.division', 'label' => 'Division / Unit', 'route' => 'company.division.index', 'group' => 'Company', 'icon' => 'list'],
    ['key' => 'user', 'label' => 'Data User', 'route' => 'user.index', 'group' => 'Company', 'icon' => 'list'],
    ['key' => 'roleuser', 'label' => 'Role to user', 'route' => 'roleuser.index', 'group' => 'Company', 'icon' => 'list'],
    ['key' => 'roles', 'label' => 'Roles', 'route' => 'roles.index', 'group' => 'Company', 'icon' => 'list'],
    ['key' => 'historyuserlogin', 'label' => 'User Login', 'route' => 'historyuserlogin.index', 'group' => 'Company', 'icon' => 'list'],
    ['key' => 'activity-log', 'label' => 'Activity Log', 'route' => 'activity-log.index', 'group' => 'Company', 'icon' => 'list'],

    // ================= Zoom =================
    ['key' => 'zoom.meeting', 'label' => 'Kelola Meeting', 'route' => 'zoom.meeting.index', 'group' => 'Zoom', 'icon' => 'list'],

    // ================= Course =================
    // Urutan sesuai dependency: Type -> Class -> Level -> Package (lihat
    // routes/web.php grup 'course.*' & migration 2026_09_07_100000 dst).
    ['key' => 'course.type', 'label' => 'Course Type', 'route' => 'course.type.index', 'group' => 'Course', 'icon' => 'list'],
    ['key' => 'course.class', 'label' => 'Course Class', 'route' => 'course.class.index', 'group' => 'Course', 'icon' => 'list'],
    ['key' => 'course.level', 'label' => 'Course Level', 'route' => 'course.level.index', 'group' => 'Course', 'icon' => 'list'],
    ['key' => 'course.package', 'label' => 'Course Package', 'route' => 'course.package.index', 'group' => 'Course', 'icon' => 'list'],

    // ================= Settings =================
    ['key' => 'settings.zoom', 'label' => 'Setting Zoom', 'route' => 'settings.zoom.index', 'group' => 'Settings', 'icon' => 'list'],
    ['key' => 'settings.payment-gateway', 'label' => 'Payment Gateway', 'route' => 'settings.payment-gateway.index', 'group' => 'Settings', 'icon' => 'list'],
    ['key' => 'settings.whatsapp-gateway', 'label' => 'WhatsApp Gateway', 'route' => 'settings.whatsapp-gateway.index', 'group' => 'Settings', 'icon' => 'list'],
    ['key' => 'qrcodes', 'label' => 'Generate Link to Qrcode', 'route' => 'qrcodes.index', 'group' => 'Settings', 'icon' => 'list'],

    // ========= Terdaftar sbg permission, sengaja TANPA link sidebar =========
    // (route-nya sudah ada & tetap di-scope 'role:superadmin' style sebelumnya,
    // tapi memang belum pernah ditautkan di menu manapun)
    ['key' => 'package', 'label' => 'Package', 'menu' => false],
    ['key' => 'category-application', 'label' => 'Category Application', 'menu' => false],
    ['key' => 'deposit', 'label' => 'Deposit', 'menu' => false],
    ['key' => 'transaction', 'label' => 'Transaction', 'menu' => false],
    ['key' => 'vouchers', 'label' => 'Vouchers', 'menu' => false],
];
