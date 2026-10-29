@extends('layouts.frontend')

@section('content')

{{-- Students > File Student > tab Notifikasi (App\Http\Controllers\Student\StudentFileNotificationController). --}}
@php
    $numbers = old('admin_whatsapp_numbers', $setting->admin_whatsapp_numbers ?: ['']);
    $emails = old('admin_emails', $setting->admin_emails ?: ['']);
    $days = old('reminder_days', $setting->reminderDays() ?: \App\Models\DocumentNotificationSetting::DEFAULT_REMINDER_DAYS);
    $hour = (int) old('reminder_hour', $setting->reminder_hour ?? 10);
    $on = fn (string $field) => (bool) old($field, $setting->{$field});
@endphp

<div class="middle-content container-xxl p-0">

    <div class="layout-top-spacing"></div>
    @include('student.files._tabs', ['active' => 'notifications'])

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1">Pengaturan belum tersimpan:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('student.files.notifications.update') }}">
        @csrf
        @method('PUT')
        <fieldset @disabled(! $canEdit)>

        {{-- ================= Card 1: info ke admin ================= --}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <h5 class="mb-1"><i class="bi bi-person-badge"></i> Info ke Admin saat Student Upload Dokumen</h5>
                <p class="text-muted small mb-4">
                    Admin langsung diberi tahu setiap kali student mengunggah dokumen. Beberapa file yang diunggah sekaligus digabung jadi 1 pesan.
                </p>

                {{-- WhatsApp admin --}}
                <div class="border rounded p-3 mb-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="admin_whatsapp_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="admin_whatsapp_enabled" name="admin_whatsapp_enabled" value="1"
                            data-toggle-target="#admin-wa-box" @checked($on('admin_whatsapp_enabled'))>
                        <label class="form-check-label fw-bold" for="admin_whatsapp_enabled">Kirim lewat WhatsApp</label>
                    </div>
                    <div id="admin-wa-box" class="mt-3" @if (! $on('admin_whatsapp_enabled')) hidden @endif>
                        <label class="form-label small text-muted">Nomor WhatsApp admin</label>
                        <div data-list="wa">
                            @foreach ($numbers as $number)
                                <div class="input-group mb-2" data-row>
                                    <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
                                    <input type="text" name="admin_whatsapp_numbers[]" value="{{ $number }}" class="form-control" placeholder="mis. 081234567890" inputmode="tel">
                                    <button type="button" class="btn btn-outline-danger" data-remove title="Hapus nomor">&times;</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-add="wa"><i class="bi bi-plus"></i> Tambah nomor</button>
                    </div>
                </div>

                {{-- Email admin --}}
                <div class="border rounded p-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="admin_email_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="admin_email_enabled" name="admin_email_enabled" value="1"
                            data-toggle-target="#admin-email-box" @checked($on('admin_email_enabled'))>
                        <label class="form-check-label fw-bold" for="admin_email_enabled">Kirim lewat Email</label>
                    </div>
                    <div id="admin-email-box" class="mt-3" @if (! $on('admin_email_enabled')) hidden @endif>
                        <label class="form-label small text-muted">Email admin</label>
                        <div data-list="email">
                            @foreach ($emails as $email)
                                <div class="input-group mb-2" data-row>
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="admin_emails[]" value="{{ $email }}" class="form-control" placeholder="mis. admin@inastudy.com">
                                    <button type="button" class="btn btn-outline-danger" data-remove title="Hapus email">&times;</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-add="email"><i class="bi bi-plus"></i> Tambah email</button>
                    </div>
                </div>

                @if ($canEdit)
                    <div class="d-flex align-items-center gap-2 mt-3">
                        <button type="submit" form="notification-test-form" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-send"></i> Kirim Tes ke Admin
                        </button>
                        <span class="text-muted small">Simpan pengaturan dulu, lalu kirim pesan contoh untuk memastikan WhatsApp & email admin sudah sampai.</span>
                    </div>
                @endif

                <div class="bg-light rounded p-3 mt-3 small">
                    <div class="fw-bold mb-1">Contoh pesan:</div>
                    📄 <b>Dokumen baru diunggah</b><br>
                    Budi Santoso (Peking University – Computer Science) baru saja mengunggah: Passport, Ijazah SMA.<br>
                    Cek di: <span class="text-primary">[link aplikasi]</span>
                </div>
            </div>
        </div>

        {{-- ================= Card 2: pengingat ke student ================= --}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <h5 class="mb-1"><i class="bi bi-alarm"></i> Pengingat ke Student yang Dokumennya Belum Lengkap</h5>
                <p class="text-muted small mb-4">
                    Student diingatkan secara otomatis pada jam yang dipilih, dihitung sejak student selesai mengisi Formulir.
                    Pengingat berhenti sendiri begitu semua dokumen wajib sudah diunggah.
                </p>

                <div class="border rounded p-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="reminder_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="reminder_enabled" name="reminder_enabled" value="1"
                            data-toggle-target="#reminder-box" @checked($on('reminder_enabled'))>
                        <label class="form-check-label fw-bold" for="reminder_enabled">Aktifkan pengingat</label>
                    </div>

                    <div id="reminder-box" class="mt-3" @if (! $on('reminder_enabled')) hidden @endif>
                        <label class="form-label small text-muted d-block">Kirim lewat</label>
                        <div class="d-flex flex-wrap gap-4 mb-3">
                            <div class="form-check">
                                <input type="hidden" name="reminder_whatsapp" value="0">
                                <input class="form-check-input" type="checkbox" id="reminder_whatsapp" name="reminder_whatsapp" value="1" @checked($on('reminder_whatsapp'))>
                                <label class="form-check-label" for="reminder_whatsapp"><i class="bi bi-whatsapp"></i> WhatsApp student</label>
                            </div>
                            <div class="form-check">
                                <input type="hidden" name="reminder_email" value="0">
                                <input class="form-check-input" type="checkbox" id="reminder_email" name="reminder_email" value="1" @checked($on('reminder_email'))>
                                <label class="form-check-label" for="reminder_email"><i class="bi bi-envelope"></i> Email student</label>
                            </div>
                        </div>

                        <label class="form-label small text-muted d-block">Jadwal pengingat (hari setelah Formulir diisi)</label>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-2" data-list="day">
                            @foreach ($days as $day)
                                <div class="input-group input-group-sm" style="width: 130px;" data-row>
                                    <span class="input-group-text">H+</span>
                                    <input type="number" name="reminder_days[]" value="{{ $day }}" min="1" max="{{ $maxDay }}" class="form-control text-center">
                                    <button type="button" class="btn btn-outline-danger" data-remove title="Hapus jadwal">&times;</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mb-3" data-add="day"><i class="bi bi-plus"></i> Tambah jadwal</button>
                        <div class="form-text mt-0 mb-3">Contoh: H+1 = sehari setelah student mengisi Formulir. Maksimal 10 jadwal, paling lama H+{{ $maxDay }}.</div>

                        <label for="reminder_hour" class="form-label small text-muted d-block">Jam kirim (WIB)</label>
                        <select id="reminder_hour" name="reminder_hour" class="form-select form-select-sm" style="width: 130px;">
                            @foreach ($hourOptions as $option)
                                <option value="{{ $option }}" @selected($hour === $option)>{{ sprintf('%02d.00', $option) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="bg-light rounded p-3 mt-3 small">
                    <div class="fw-bold mb-1">Contoh pesan:</div>
                    Hai Kak Budi 👋<br>
                    Kami dari Tim INA Study mau mengingatkan, masih ada beberapa dokumen yang belum Kakak unggah untuk pendaftaran di
                    Peking University: <b>Ijazah SMA, Transkrip Nilai</b>.<br>
                    Yuk dilengkapi supaya proses pendaftaran Kakak bisa segera kami lanjutkan 😊 <span class="text-primary">[link upload]</span><br>
                    Kalau ada kendala, balas pesan ini ya, kami siap bantu!
                </div>
            </div>
        </div>

        {{-- ================= Card 3: dokumen lengkap ================= --}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <h5 class="mb-1"><i class="bi bi-patch-check"></i> Konfirmasi Upload & Dokumen Lengkap</h5>
                <p class="text-muted small mb-4">Pesan ke student setelah upload, dan pesan satu kali begitu semua dokumen wajib sudah lengkap.</p>

                <div class="border rounded p-3 mb-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="upload_confirm_student" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="upload_confirm_student" name="upload_confirm_student" value="1" @checked($on('upload_confirm_student'))>
                        <label class="form-check-label fw-bold" for="upload_confirm_student">Konfirmasi ke student setiap kali upload</label>
                    </div>
                    <div class="text-muted small mt-2">
                        Dikirim lewat kanal yang sama dengan pengingat (WhatsApp / Email student). Kalau upload itu membuat dokumen lengkap,
                        cukup pesan terima kasih di bawah yang dikirim.<br>
                        Contoh: <i>Hai Kak Budi 👋 Dokumen Passport, Ijazah SMA sudah kami terima, terima kasih ya! Tim kami akan segera memeriksanya.
                        Masih ada yang perlu dilengkapi: Transkrip Nilai, Surat Rekomendasi. Yuk diunggah juga 😊</i>
                    </div>
                </div>

                <div class="border rounded p-3 mb-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="complete_notify_student" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="complete_notify_student" name="complete_notify_student" value="1" @checked($on('complete_notify_student'))>
                        <label class="form-check-label fw-bold" for="complete_notify_student">Ucapan terima kasih ke student</label>
                    </div>
                    <div class="text-muted small mt-2">
                        Dikirim lewat kanal yang sama dengan pengingat (WhatsApp / Email student).<br>
                        Contoh: <i>Terima kasih Kak Budi! 🎉 Semua dokumen sudah lengkap, tim kami akan segera memeriksanya.</i>
                    </div>
                </div>

                <div class="border rounded p-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="complete_notify_admin" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="complete_notify_admin" name="complete_notify_admin" value="1" @checked($on('complete_notify_admin'))>
                        <label class="form-check-label fw-bold" for="complete_notify_admin">Info ke admin</label>
                    </div>
                    <div class="text-muted small mt-2">
                        Dikirim ke nomor & email admin di card pertama.<br>
                        Contoh: <i>✅ Budi Santoso (Peking University – Computer Science) sudah melengkapi semua dokumen.</i>
                    </div>
                </div>
            </div>
        </div>

        </fieldset>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div class="text-muted small">
                @if ($setting->updatedBy)
                    Terakhir diubah oleh {{ $setting->updatedBy->name }}, {{ $setting->updated_at?->translatedFormat('d M Y H:i') }}
                @endif
            </div>
            @if ($canEdit)
                <button type="submit" class="btn btn-primary px-4">Simpan Pengaturan</button>
            @else
                <span class="text-muted small">Anda hanya punya akses lihat.</span>
            @endif
        </div>
    </form>

    {{-- Form terpisah untuk tombol "Kirim Tes" (form tidak boleh bersarang di dalam form pengaturan). --}}
    @if ($canEdit)
        <form method="POST" action="{{ route('student.files.notifications.test') }}" id="notification-test-form" class="d-none">@csrf</form>
    @endif

    {{-- Template baris baru untuk tombol "Tambah nomor/email". --}}
    <template id="row-wa">
        <div class="input-group mb-2" data-row>
            <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
            <input type="text" name="admin_whatsapp_numbers[]" class="form-control" placeholder="mis. 081234567890" inputmode="tel">
            <button type="button" class="btn btn-outline-danger" data-remove title="Hapus nomor">&times;</button>
        </div>
    </template>
    <template id="row-day">
        <div class="input-group input-group-sm" style="width: 130px;" data-row>
            <span class="input-group-text">H+</span>
            <input type="number" name="reminder_days[]" min="1" max="{{ $maxDay }}" class="form-control text-center">
            <button type="button" class="btn btn-outline-danger" data-remove title="Hapus jadwal">&times;</button>
        </div>
    </template>
    <template id="row-email">
        <div class="input-group mb-2" data-row>
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="admin_emails[]" class="form-control" placeholder="mis. admin@inastudy.com">
            <button type="button" class="btn btn-outline-danger" data-remove title="Hapus email">&times;</button>
        </div>
    </template>
</div>

{{-- Layout ini tidak punya @stack('scripts'), jadi script ditaruh langsung di akhir konten. --}}
<script>
    (function () {
        // Saklar: tampilkan/sembunyikan isian di bawahnya.
        document.querySelectorAll('[data-toggle-target]').forEach(function (toggle) {
            var box = document.querySelector(toggle.dataset.toggleTarget);
            toggle.addEventListener('change', function () { box.hidden = !toggle.checked; });
        });

        // Tambah baris nomor/email.
        document.querySelectorAll('[data-add]').forEach(function (button) {
            button.addEventListener('click', function () {
                var list = document.querySelector('[data-list="' + button.dataset.add + '"]');
                var row = document.getElementById('row-' + button.dataset.add).content.cloneNode(true);
                list.appendChild(row);
                list.lastElementChild.querySelector('input').focus();
            });
        });

        // Hapus baris; kalau tinggal 1, cukup dikosongkan.
        document.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-remove]');
            if (!remove) return;
            var row = remove.closest('[data-row]');
            var list = row.parentElement;
            if (list.querySelectorAll('[data-row]').length > 1) {
                row.remove();
            } else {
                row.querySelector('input').value = '';
            }
        });
    })();
</script>

@endsection
