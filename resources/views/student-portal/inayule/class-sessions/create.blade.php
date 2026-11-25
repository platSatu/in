@extends('layouts.frontend')
@section('content')

{{--
    Form Ajukan Pemakaian Credit + Pilih Jadwal (25 November 2026).
    Siswa memilih paket -> pengajar -> lama belajar -> tanggal -> jam.
    Pilihan jam datang dari ClassSessionWorkflowService::scheduleOptions()
    (lewat route inayule.class-sessions.schedule-options) dan dicek ulang
    saat submit. Credit BELUM terpotong di sini -- baru setelah admin approve.
--}}

<div class="col-lg-7 mx-auto layout-spacing inayule-sub">
    @include('student-portal.inayule._subpage', ['backTab' => 'kelas'])
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area">
            <h4 class="mb-1">Ajukan Jadwal Kelas</h4>
            <p class="text-muted mb-4">Pilih paket, pengajar, dan jam belajar yang cocok. 1 credit = 1 jam kelas.</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('inayule.class-sessions.store') }}" id="bookingForm">
                @csrf

                {{-- 1. Paket --}}
                <div class="iy-step">
                    <div class="iy-step-title"><span class="iy-step-no">1</span> Paket yang dipakai</div>
                    <select name="course_package_id" class="form-select" required data-schedule-input>
                        <option value="">-- Pilih Paket --</option>
                        @foreach ($packages as $package)
                            @php($sisa = (float) ($remainingByPackage[$package->id] ?? 0))
                            <option value="{{ $package->id }}" @selected(old('course_package_id') === $package->id) @disabled($sisa <= 0)>
                                {{ $package->detailLabel() }} &middot; sisa {{ rtrim(rtrim(number_format($sisa, 2, ',', '.'), '0'), ',') }} credit
                            </option>
                        @endforeach
                    </select>
                    @if ($packages->isEmpty())
                        <small class="text-muted">Anda belum punya paket. Beli paket dulu di tab Buy Packages.</small>
                    @else
                        <small class="text-muted">Anda akan belajar bersama siswa lain dengan paket yang sama. Ingin ikut kelas paket lain? Upgrade paket Anda di tab Status.</small>
                    @endif
                </div>

                {{-- 2. Pengajar --}}
                <div class="iy-step">
                    <div class="iy-step-title"><span class="iy-step-no">2</span> Pengajar</div>
                    <select name="teacher_user_id" class="form-select" required data-schedule-input>
                        <option value="">-- Pilih Pengajar --</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected(old('teacher_user_id', $usualTeacherId) === $teacher->id)>
                                {{ $teacher->name }}{{ $teacher->id === $usualTeacherId ? ' (pengajar Anda)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($teachers->isEmpty())
                        <small class="text-muted">Belum ada pengajar yang membuka jadwal di cabang Anda. Silakan hubungi admin.</small>
                    @endif
                </div>

                {{-- 3. Lama belajar --}}
                <div class="iy-step">
                    <div class="iy-step-title"><span class="iy-step-no">3</span> Lama belajar</div>
                    <div class="iy-choice-row">
                        @foreach (['1' => '1 jam', '1.5' => '1 jam 30 menit', '2' => '2 jam'] as $credit => $label)
                            <label class="iy-choice">
                                <input type="radio" name="credit_amount" value="{{ $credit }}" required data-schedule-input
                                    @checked(old('credit_amount', '1') === (string) $credit)>
                                <span><strong>{{ $label }}</strong><small>{{ $credit }} credit</small></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- 4. Tanggal & jam --}}
                <div class="iy-step">
                    <div class="iy-step-title"><span class="iy-step-no">4</span> Tanggal &amp; jam kelas</div>
                    <input type="date" id="classDate" class="form-control mb-3" data-schedule-input
                        min="{{ $firstDate->format('Y-m-d') }}" max="{{ $lastDate->format('Y-m-d') }}"
                        value="{{ old('class_start_at') ? substr(old('class_start_at'), 0, 10) : '' }}">
                    <small class="text-muted d-block mb-2">Kelas tersedia Senin - Sabtu, sesuai jam buka pengajar.</small>

                    <input type="hidden" name="class_start_at" id="classStartAt" value="{{ old('class_start_at') }}">
                    <div id="slotArea" class="iy-slot-area">
                        <p class="text-muted mb-0">Lengkapi pilihan di atas, lalu pilih tanggal untuk melihat jam yang tersedia.</p>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan untuk pengajar (opsional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: mau fokus latihan percakapan">{{ old('notes') }}</textarea>
                </div>

                <div class="alert alert-light border small" id="bookingSummary" hidden></div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBooking" disabled>Ajukan</button>
                    <a href="{{ route('inayule.index') }}#kelas" class="btn btn-outline-secondary">Batal</a>
                </div>
                <small class="text-muted d-block mt-2">Credit belum terpotong sekarang. Credit baru terpotong setelah pengajar dan admin menyetujui kelas Anda.</small>
            </form>
        </div>
    </div>
</div>

<style>
    .inayule-sub .iy-step { margin-bottom: 22px; }
    .inayule-sub .iy-step-title { font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
    .inayule-sub .iy-step-no { width: 24px; height: 24px; border-radius: 50%; background: var(--ina-red); color: #fff;
        display: inline-flex; align-items: center; justify-content: center; font-size: 13px; }
    .inayule-sub .iy-choice-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .inayule-sub .iy-choice { cursor: pointer; margin: 0; }
    .inayule-sub .iy-choice input { position: absolute; opacity: 0; pointer-events: none; }
    .inayule-sub .iy-choice span { display: flex; flex-direction: column; padding: 10px 14px; border: 1px solid #e0e6ed; border-radius: 12px; min-width: 120px; }
    .inayule-sub .iy-choice small { color: #888ea8; }
    .inayule-sub .iy-choice input:checked + span { border-color: var(--ina-red); background: #fff1f1; color: var(--ina-red); }
    .inayule-sub .iy-slot-group { font-size: 13px; font-weight: 700; color: #515365; margin: 10px 0 6px; }
    .inayule-sub .iy-slot-grid { display: flex; flex-wrap: wrap; gap: 8px; }
    .inayule-sub .iy-slot { border: 1px solid #e0e6ed; border-radius: 12px; padding: 8px 12px; background: #fff; text-align: left; line-height: 1.3; }
    .inayule-sub .iy-slot small { display: block; color: #888ea8; }
    .inayule-sub .iy-slot.is-selected { border-color: var(--ina-red); background: #fff1f1; color: var(--ina-red); }
</style>

<script>
(function () {
    var form = document.getElementById('bookingForm');
    var dateInput = document.getElementById('classDate');
    var startInput = document.getElementById('classStartAt');
    var slotArea = document.getElementById('slotArea');
    var submitButton = document.getElementById('submitBooking');
    var summary = document.getElementById('bookingSummary');
    var optionsUrl = @json(route('inayule.class-sessions.schedule-options'));
    var preselected = startInput.value;

    function value(name) {
        var field = form.querySelector('[name="' + name + '"]:checked') || form.querySelector('[name="' + name + '"]');
        return field ? field.value : '';
    }

    function message(text) {
        slotArea.innerHTML = '<p class="text-muted mb-0">' + text + '</p>';
    }

    function choose(button, start, description) {
        slotArea.querySelectorAll('.iy-slot').forEach(function (el) { el.classList.remove('is-selected'); });
        button.classList.add('is-selected');
        startInput.value = start;
        submitButton.disabled = false;
        summary.hidden = false;
        summary.textContent = description;
    }

    function slotButton(start, title, note, description) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'iy-slot';
        button.innerHTML = '<strong>' + title + '</strong>' + (note ? '<small>' + note + '</small>' : '');
        button.addEventListener('click', function () { choose(button, start, description); });
        if (start === preselected) { choose(button, start, description); }
        return button;
    }

    function group(title, buttons) {
        if (!buttons.length) { return; }
        var heading = document.createElement('div');
        heading.className = 'iy-slot-group';
        heading.textContent = title;
        var grid = document.createElement('div');
        grid.className = 'iy-slot-grid';
        buttons.forEach(function (button) { grid.appendChild(button); });
        slotArea.appendChild(heading);
        slotArea.appendChild(grid);
    }

    function loadSlots() {
        startInput.value = '';
        submitButton.disabled = true;
        summary.hidden = true;

        var params = {
            teacher_user_id: value('teacher_user_id'),
            course_package_id: value('course_package_id'),
            credit_amount: value('credit_amount'),
            date: dateInput.value
        };

        if (!params.teacher_user_id || !params.course_package_id || !params.credit_amount) {
            return message('Lengkapi pilihan paket, pengajar, dan lama belajar terlebih dahulu.');
        }
        if (!params.date) {
            return message('Pilih tanggal untuk melihat jam yang tersedia.');
        }

        message('Mencari jam yang tersedia...');

        fetch(optionsUrl + '?' + new URLSearchParams(params), { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (options) {
                slotArea.innerHTML = '';
                var teacherName = form.querySelector('[name="teacher_user_id"] option:checked').textContent.trim();
                var dayLabel = new Date(params.date + 'T00:00').toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

                group('Gabung kelas yang sudah ada', (options.join || []).map(function (slot) {
                    var seatsLeft = slot.quota - slot.filled;
                    return slotButton(slot.start, slot.label, slot.filled + ' siswa · sisa ' + seatsLeft + ' kursi',
                        'Anda akan bergabung di kelas ' + teacherName + ', ' + dayLabel + ' pukul ' + slot.label + ' bersama ' + slot.filled + ' siswa lain.');
                }));

                group('Buka jam baru', (options['new'] || []).map(function (slot) {
                    return slotButton(slot.start, slot.label, 'masih kosong',
                        'Anda membuka kelas baru dengan ' + teacherName + ', ' + dayLabel + ' pukul ' + slot.label + '. Siswa lain dengan paket yang sama bisa ikut bergabung.');
                }));

                if (!slotArea.children.length) {
                    message('Belum ada jam yang tersedia di tanggal ini. Coba tanggal lain atau pilih pengajar lain.');
                }
            })
            .catch(function () { message('Jam belum bisa dimuat. Periksa koneksi Anda lalu coba lagi.'); });
    }

    form.querySelectorAll('[data-schedule-input]').forEach(function (field) {
        field.addEventListener('change', loadSlots);
    });

    if (dateInput.value) { loadSlots(); }
})();
</script>
@endsection
