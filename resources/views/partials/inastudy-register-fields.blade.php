{{--
    Dropdown bertingkat Universitas -> Degree -> Jurusan (Course) untuk Register
    InaStudy manual (1 Oktober 2026). Dipakai bersama oleh:
    - halaman InaStudy siswa (student-portal.inastudy.index);
    - popup "Add to InaStudy" admin di index Student (student.student.index).
    Data dari App\Services\InaStudy\ManualApplicationRegistrar::options().

    Variabel: $prefix (prefix id unik per halaman), $universities, $degreeOrder,
    $withOld (isi ulang dari old() setelah validasi gagal; default true),
    $defaultWhatsapp (nomor awal kolom WhatsApp; default kosong).
    Isian Intake Year & WhatsApp sama dengan form Apply (student-portal.apply.show).
    Reset form (form.reset()) otomatis mengosongkan & menyembunyikan Degree/Jurusan.
--}}
@php
    $withOld = $withOld ?? true;
    $defaultWhatsapp = $defaultWhatsapp ?? '';
@endphp
<div class="row g-3" id="{{ $prefix }}Fields">
    <div class="col-md-4">
        <label class="form-label">Universitas</label>
        <select name="university_id" id="{{ $prefix }}University" class="form-select @error('university_id') is-invalid @enderror" required>
            <option value="">-- Pilih Universitas --</option>
            @foreach ($universities as $university)
                <option value="{{ $university['id'] }}" @selected($withOld && old('university_id') == $university['id'])>{{ $university['name'] }}</option>
            @endforeach
        </select>
        @error('university_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4" id="{{ $prefix }}DegreeWrapper" style="display:none;">
        <label class="form-label">Degree</label>
        <select name="degree" id="{{ $prefix }}Degree" class="form-select @error('degree') is-invalid @enderror" required>
            <option value="">-- Pilih Degree --</option>
        </select>
        @error('degree')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4" id="{{ $prefix }}CourseWrapper" style="display:none;">
        <label class="form-label">Jurusan</label>
        <select name="degree_intake_id" id="{{ $prefix }}Course" class="form-select @error('degree_intake_id') is-invalid @enderror" required>
            <option value="">-- Pilih Jurusan --</option>
        </select>
        @error('degree_intake_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Intake Year</label>
        <input type="number" name="intake_year" id="{{ $prefix }}IntakeYear" class="form-control @error('intake_year') is-invalid @enderror"
            value="{{ $withOld ? old('intake_year', now()->year) : now()->year }}" min="{{ now()->year }}" max="{{ now()->year + 5 }}" required>
        @error('intake_year')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">No. WhatsApp</label>
        <input type="text" name="whatsapp" id="{{ $prefix }}Whatsapp" maxlength="20" class="form-control @error('whatsapp') is-invalid @enderror"
            value="{{ $withOld ? old('whatsapp', $defaultWhatsapp) : $defaultWhatsapp }}" placeholder="08xxxxxxxxxx" required>
        @error('whatsapp')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<script>
(function () {
    const universities = @json($universities);
    const degreeOrder = @json($degreeOrder);
    const uniSelect = document.getElementById(@json($prefix . 'University'));
    const degreeWrapper = document.getElementById(@json($prefix . 'DegreeWrapper'));
    const degreeSelect = document.getElementById(@json($prefix . 'Degree'));
    const courseWrapper = document.getElementById(@json($prefix . 'CourseWrapper'));
    const courseSelect = document.getElementById(@json($prefix . 'Course'));

    function coursesFor(universityId) {
        const university = universities.find(function (u) { return u.id === universityId; });
        return university ? (university.courses || []) : [];
    }

    function fill(select, placeholder, items, selected) {
        select.innerHTML = '';
        select.appendChild(new Option(placeholder, ''));
        items.forEach(function (item) {
            select.appendChild(new Option(item.label, item.value, false, String(item.value) === String(selected)));
        });
    }

    function populateDegrees(universityId, selectedDegree) {
        const courses = coursesFor(universityId);
        const degrees = degreeOrder.filter(function (degree) {
            return courses.some(function (course) { return course.degree === degree; });
        });
        fill(degreeSelect, '-- Pilih Degree --', degrees.map(function (d) { return { value: d, label: d }; }), selectedDegree);
        degreeWrapper.style.display = degrees.length ? '' : 'none';
        fill(courseSelect, '-- Pilih Jurusan --', [], null);
        courseWrapper.style.display = 'none';
    }

    function populateCourses(universityId, degree, selectedCourseId) {
        const courses = coursesFor(universityId).filter(function (course) { return course.degree === degree; });
        fill(courseSelect, '-- Pilih Jurusan --', courses.map(function (c) { return { value: c.id, label: c.label }; }), selectedCourseId);
        courseWrapper.style.display = courses.length ? '' : 'none';
    }

    uniSelect.addEventListener('change', function () { populateDegrees(this.value, null); });
    degreeSelect.addEventListener('change', function () { populateCourses(uniSelect.value, this.value, null); });

    if (uniSelect.form) {
        uniSelect.form.addEventListener('reset', function () {
            setTimeout(function () { populateDegrees('', null); degreeWrapper.style.display = 'none'; }, 0);
        });
    }

    @if ($withOld && old('university_id'))
        populateDegrees(@json(old('university_id')), @json(old('degree')));
        populateCourses(@json(old('university_id')), @json(old('degree')), @json(old('degree_intake_id')));
    @endif
})();
</script>
