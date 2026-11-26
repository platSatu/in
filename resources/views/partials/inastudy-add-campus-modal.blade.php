{{--
    Popup "Add to InaStudy" / "Tambah Kampus" (18 November 2026): sama dengan "My University List" siswa
    di depan -- pilih 1-2 kampus ke daftar, lihat biayanya (DP 50% per kampus kalau lebih dari 1), lalu
    pilih cara bayar. Server tetap memvalidasi semuanya (ManualApplicationRegistrar::registerMany).

    Dipakai di: index Student (siswa yang belum punya aplikasi) & detail Aplikasi Kuliah (Tambah Kampus).
    Variabel: $registerUniversities (ManualApplicationRegistrar::options()), $registerDegreeOrder.
    Tombol pembuka: data-bs-toggle="modal" data-bs-target="#addInaStudyModal" + data-add-url,
    data-student-name, data-student-whatsapp, data-has-account (1/0), data-existing (JSON kampus terdaftar).
--}}
        <style>
            #addInaStudyModal .ina-slots { display:flex; gap:4px; justify-content:flex-end; }
            #addInaStudyModal .ina-slots span { width:26px; height:5px; border-radius:9px; background:#e3e6ee; }
            #addInaStudyModal .ina-slots span.is-on { background:#2196f3; }
            #addInaStudyModal .ina-item { display:flex; align-items:center; gap:12px; border:1px solid #e9ecf3; border-radius:10px; padding:10px 12px; margin-top:8px; }
            #addInaStudyModal .ina-logo { width:38px; height:38px; flex-shrink:0; border-radius:9px; background:#e7f1fd; color:#1b6fc2; display:grid; place-items:center; font-weight:700; }
            #addInaStudyModal .ina-pick { border:1px dashed #cfd5e3; border-radius:10px; padding:12px; margin-top:12px; }
            #addInaStudyModal .ina-stat { background:#f5f7fb; border-radius:10px; padding:10px 12px; }
            #addInaStudyModal .ina-stat .num { font-size:1.15rem; font-weight:700; }
            #addInaStudyModal .ina-pay { border:1px solid #e3e6ee; border-radius:10px; padding:10px 12px; display:flex; gap:10px; cursor:pointer; margin-bottom:8px; }
            #addInaStudyModal .ina-pay.is-on { border-color:#2196f3; background:#f4f9ff; }
            #addInaStudyModal .ina-pay.is-off { opacity:.55; cursor:not-allowed; }
        </style>
        <div class="modal fade" id="addInaStudyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content text-start">
                    <form id="addInaStudyForm" method="POST" action="">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <h6 class="modal-title mb-0">Daftarkan ke InaStudy</h6>
                                <small class="text-muted" id="addInaStudyStudentName"></small>
                            </div>
                            <div class="ms-auto me-3 text-end">
                                <div class="ina-slots" id="addInaStudySlots"></div>
                                <small class="text-muted" id="addInaStudySlotText"></small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="addInaStudyExisting" class="mb-2"></div>

                            <div id="addInaStudyList"></div>
                            <div id="addInaStudyEmpty" class="text-muted small py-2">Belum ada kampus di daftar. Pilih kampus di bawah ini.</div>

                            <div class="ina-pick" id="addInaStudyPicker">
                                <div class="small fw-semibold mb-2">Tambah kampus</div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select id="addInaStudyUniversity" class="form-select form-select-sm">
                                            <option value="">Pilih universitas</option>
                                            @foreach ($registerUniversities as $university)
                                                <option value="{{ $university['id'] }}">{{ $university['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="addInaStudyDegree" class="form-select form-select-sm" disabled>
                                            <option value="">Pilih degree</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="addInaStudyCourse" class="form-select form-select-sm" disabled>
                                            <option value="">Pilih jurusan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" id="addInaStudyIntakeYear" class="form-control form-control-sm" title="Intake year"
                                            value="{{ now()->year }}" min="{{ now()->year }}" max="{{ now()->year + 5 }}">
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addInaStudyAddBtn"><i class="bi bi-plus-lg"></i> Masukkan ke daftar</button>
                                    <small class="text-danger" id="addInaStudyPickError"></small>
                                </div>
                            </div>

                            <div class="row g-2 mt-3">
                                <div class="col-6"><div class="ina-stat"><small class="text-muted">Dibayar sekarang</small><div class="num" id="addInaStudyPayNow">Rp 0</div></div></div>
                                <div class="col-6"><div class="ina-stat"><small class="text-muted">Sisa (ditagih nanti)</small><div class="num" id="addInaStudyRemaining">Rp 0</div></div></div>
                            </div>
                            <div class="small text-muted mt-2" id="addInaStudyNote"></div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-5">
                                    <label class="form-label small mb-1" for="addInaStudyWhatsapp">No. WhatsApp siswa</label>
                                    <input type="text" name="whatsapp" id="addInaStudyWhatsapp" maxlength="20" class="form-control form-control-sm" placeholder="08xxxxxxxxxx" required>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-label small mb-1">Cara bayar Registration Fee</div>
                                    <label class="ina-pay" id="addInaStudyPayLink">
                                        <input type="radio" name="payment" value="link" class="form-check-input mt-1">
                                        <span><span class="fw-semibold small">Kirim link bayar ke siswa</span><br>
                                            <small class="text-muted" id="addInaStudyPayLinkHint">Siswa bayar sendiri dari portalnya. Formulir terbuka setelah lunas.</small></span>
                                    </label>
                                    <label class="ina-pay" id="addInaStudyPayManual">
                                        <input type="radio" name="payment" value="manual" class="form-check-input mt-1">
                                        <span><span class="fw-semibold small">Siswa sudah transfer manual</span><br>
                                            <small class="text-muted">Langsung ditandai lunas, Formulir &amp; Dokumen langsung terbuka.</small></span>
                                    </label>
                                </div>
                            </div>
                            <div id="addInaStudyHidden"></div>
                        </div>
                        <div class="modal-footer">
                            <small class="text-danger me-auto" id="addInaStudySubmitError"></small>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-info btn-sm" id="addInaStudySubmit">Daftarkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<script>
    (function () {
            // Popup Add to InaStudy (18 November 2026): daftar kampus seperti cart siswa di depan.
            const modal = document.getElementById('addInaStudyModal');
            if (modal) {
                const universities = @json($registerUniversities);
                const degreeOrder = @json($registerDegreeOrder);
                const MAX = {{ \App\Http\Controllers\StudentPortal\ApplyController::CART_MAX }};
                const el = function (id) { return document.getElementById('addInaStudy' + id); };
                const form = el('Form');
                let state = { items: [], existing: [], hasAccount: false };

                const rupiah = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };
                const esc = function (text) { const d = document.createElement('div'); d.textContent = text == null ? '' : String(text); return d.innerHTML; };
                const coursesOf = function (id) { const u = universities.find(function (x) { return x.id === id; }); return u ? (u.courses || []) : []; };
                const fill = function (select, placeholder, rows) {
                    select.innerHTML = '';
                    select.appendChild(new Option(placeholder, ''));
                    rows.forEach(function (row) { select.appendChild(new Option(row.label, row.value)); });
                    select.disabled = rows.length === 0;
                };

                el('University').addEventListener('change', function () {
                    const courses = coursesOf(this.value);
                    fill(el('Degree'), 'Pilih degree', degreeOrder.filter(function (d) {
                        return courses.some(function (c) { return c.degree === d; });
                    }).map(function (d) { return { value: d, label: d }; }));
                    fill(el('Course'), 'Pilih jurusan', []);
                    el('PickError').textContent = '';
                });
                el('Degree').addEventListener('change', function () {
                    const degree = this.value;
                    fill(el('Course'), 'Pilih jurusan', coursesOf(el('University').value)
                        .filter(function (c) { return c.degree === degree; })
                        .map(function (c) { return { value: c.id, label: c.label }; }));
                    el('PickError').textContent = '';
                });

                el('AddBtn').addEventListener('click', function () {
                    const universityId = el('University').value;
                    const university = universities.find(function (u) { return u.id === universityId; });
                    const course = coursesOf(universityId).find(function (c) { return c.id === el('Course').value; });
                    const year = parseInt(el('IntakeYear').value, 10);
                    const minYear = parseInt(el('IntakeYear').min, 10);
                    const maxYear = parseInt(el('IntakeYear').max, 10);
                    const error = el('PickError');

                    if (!university || !course) { error.textContent = 'Pilih universitas, degree, dan jurusan dulu.'; return; }
                    if (!year || year < minYear || year > maxYear) { error.textContent = 'Intake year harus antara ' + minYear + ' dan ' + maxYear + '.'; return; }
                    const same = function (x) { return String(x.course_id) === String(course.id) && parseInt(x.intake_year, 10) === year; };
                    if (state.existing.some(same)) { error.textContent = 'Siswa ini sudah terdaftar di jurusan ini untuk intake ' + year + '.'; return; }
                    if (state.items.some(same)) { error.textContent = 'Jurusan ini sudah ada di daftar.'; return; }
                    if (state.items.length >= MAX) { error.textContent = 'Maksimal ' + MAX + ' kampus sekali daftar.'; return; }

                    state.items.push({
                        university_id: university.id, university: university.name, degree: course.degree,
                        course_id: course.id, label: course.label, intake: course.intake || '', intake_year: year, fee: parseInt(course.fee, 10) || 0,
                    });
                    error.textContent = '';
                    el('University').value = '';
                    fill(el('Degree'), 'Pilih degree', []);
                    fill(el('Course'), 'Pilih jurusan', []);
                    render();
                });

                el('List').addEventListener('click', function (event) {
                    const button = event.target.closest('[data-remove]');
                    if (!button) return;
                    state.items.splice(parseInt(button.dataset.remove, 10), 1);
                    render();
                });

                [el('PayLink'), el('PayManual')].forEach(function (label) {
                    label.addEventListener('click', function (event) {
                        if (label.classList.contains('is-off')) { event.preventDefault(); return; }
                        setTimeout(render, 0);
                    });
                });

                function render() {
                    // Sama dengan cart di depan: lebih dari 1 kampus aktif (termasuk yang sudah terdaftar) = DP 50%.
                    const deposit = state.items.length + state.existing.length > 1;
                    const charge = function (fee) { return deposit ? Math.ceil(fee / 2) : fee; };

                    el('Slots').innerHTML = Array.from({ length: MAX }, function (_, i) {
                        return '<span class="' + (i < state.items.length ? 'is-on' : '') + '"></span>';
                    }).join('');
                    el('SlotText').textContent = state.items.length + ' / ' + MAX + ' kampus';

                    el('Existing').innerHTML = state.existing.length
                        ? '<div class="small text-muted"><i class="bi bi-clock-history"></i> Sudah terdaftar: ' + state.existing.map(function (x) {
                            return esc(x.label) + ' (' + (x.paid ? 'sudah bayar' : 'belum bayar') + ')';
                        }).join('; ') + '</div>'
                        : '';

                    el('List').innerHTML = state.items.map(function (item, index) {
                        const price = item.fee > 0
                            ? (deposit ? '<s class="text-muted small">' + rupiah(item.fee) + '</s><br><strong>' + rupiah(charge(item.fee)) + '</strong> <small class="text-muted">DP 50%</small>'
                                       : '<strong>' + rupiah(item.fee) + '</strong><br><small class="text-muted">Registration Fee</small>')
                            : '<small class="text-danger">Fee belum diatur</small>';
                        return '<div class="ina-item">'
                            + '<div class="ina-logo">' + esc(item.university.charAt(0).toUpperCase()) + '</div>'
                            + '<div class="flex-grow-1" style="min-width:0;"><div class="fw-semibold">' + esc(item.university) + '</div>'
                            + '<small class="text-muted">' + esc(item.label) + ' (' + esc(item.degree) + ') &middot; Intake ' + esc((item.intake ? item.intake + ' ' : '') + item.intake_year) + '</small></div>'
                            + '<div class="text-end text-nowrap">' + price + '</div>'
                            + '<button type="button" class="btn btn-sm btn-link text-muted" data-remove="' + index + '" title="Hapus" aria-label="Hapus ' + esc(item.university) + '"><i class="bi bi-trash3"></i></button>'
                            + '</div>';
                    }).join('');
                    el('Empty').style.display = state.items.length ? 'none' : '';
                    el('Picker').style.display = state.items.length >= MAX ? 'none' : '';

                    const payNow = state.items.reduce(function (sum, item) { return sum + charge(item.fee); }, 0);
                    const full = state.items.reduce(function (sum, item) { return sum + item.fee; }, 0);
                    el('PayNow').textContent = rupiah(payNow);
                    el('Remaining').textContent = rupiah(full - payNow);

                    const notes = [];
                    if (deposit) notes.push('Siswa ini terdaftar di lebih dari 1 kampus, jadi tiap kampus cukup DP 50% dulu. Sisanya ditagih lewat "Tagih Pelunasan" di halaman aplikasinya.');
                    const unpaidBefore = state.existing.filter(function (x) { return !x.paid; }).length;
                    if (unpaidBefore && form.elements.payment.value === 'link') notes.push(unpaidBefore + ' kampus yang sudah terdaftar tapi belum dibayar ikut ditagih di link bayar yang sama.');
                    if (state.items.some(function (item) { return item.fee <= 0; })) notes.push('Ada jurusan yang Registration Fee-nya belum diatur, jadi belum bisa dibayar.');
                    el('Note').innerHTML = notes.map(esc).join('<br>');

                    el('PayLink').classList.toggle('is-off', !state.hasAccount);
                    el('PayLinkHint').textContent = state.hasAccount
                        ? 'Siswa bayar sendiri dari portalnya. Formulir terbuka setelah lunas.'
                        : 'Siswa ini belum punya akun login. Buatkan akunnya dulu, atau pilih transfer manual.';
                    el('PayLink').classList.toggle('is-on', form.elements.payment.value === 'link');
                    el('PayManual').classList.toggle('is-on', form.elements.payment.value === 'manual');

                    el('Hidden').innerHTML = state.items.map(function (item, index) {
                        return ['university_id', 'degree', 'course_id', 'intake_year'].map(function (key) {
                            const name = key === 'course_id' ? 'degree_intake_id' : key;
                            return '<input type="hidden" name="items[' + index + '][' + name + ']" value="' + esc(item[key]) + '">';
                        }).join('');
                    }).join('');
                    el('Submit').textContent = state.items.length > 1 ? 'Daftarkan ' + state.items.length + ' kampus' : 'Daftarkan';
                    el('SubmitError').textContent = '';
                }

                form.addEventListener('submit', function (event) {
                    let message = '';
                    if (!state.items.length) message = 'Masukkan minimal 1 kampus ke daftar dulu.';
                    else if (!form.elements.payment.value) message = 'Pilih cara bayar Registration Fee.';
                    else if (form.elements.payment.value === 'manual' && state.items.some(function (item) { return item.fee <= 0; })) message = 'Registration Fee jurusan yang dipilih belum diatur, jadi belum bisa ditandai lunas.';
                    else if (form.elements.payment.value === 'manual' && !confirm('Tandai Registration Fee ' + el('PayNow').textContent + ' lunas? Pastikan uang transfer sudah masuk.')) { event.preventDefault(); return; }
                    if (message) { event.preventDefault(); el('SubmitError').textContent = message; return; }
                    el('Submit').disabled = true;
                });

                modal.addEventListener('show.bs.modal', function (event) {
                    const trigger = event.relatedTarget;
                    if (!trigger) return;
                    form.reset();
                    form.setAttribute('action', trigger.getAttribute('data-add-url') || '');
                    let existing = [];
                    try { existing = JSON.parse(trigger.getAttribute('data-existing') || '[]'); } catch (e) {}
                    state = { items: [], existing: existing, hasAccount: trigger.getAttribute('data-has-account') === '1' };
                    el('StudentName').textContent = (trigger.getAttribute('data-student-name') || '') + (trigger.getAttribute('data-student-whatsapp') ? ' · ' + trigger.getAttribute('data-student-whatsapp') : '');
                    el('Whatsapp').value = trigger.getAttribute('data-student-whatsapp') || '';
                    form.elements.payment.value = state.hasAccount ? 'link' : 'manual';
                    fill(el('Degree'), 'Pilih degree', []);
                    fill(el('Course'), 'Pilih jurusan', []);
                    el('PickError').textContent = '';
                    el('Submit').disabled = false;
                    render();
                });
            }
    })();
</script>
