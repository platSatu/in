@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('quiz.university-profile.index') }}">University Profile</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('quiz.university-profile.update', $data->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="university_id" class="mb-2">University</label>
                            <select class="form-select @error('university_id') is-invalid @enderror" id="university_id" name="university_id">
                                <option value="">Choose university...</option>
                                @foreach ($universities as $university)
                                    <option value="{{ $university->id }}"
                                        {{ old('university_id', $data->university_id) == $university->id ? 'selected' : '' }}>
                                        {{ $university->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('university_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="field" class="mb-2">Field</label>
                            <input type="text" class="form-control @error('field') is-invalid @enderror" id="field"
                                name="field" value="{{ old('field', $data->field) }}"
                                placeholder="Enter field (e.g. IT, Medicine, Business)...">
                            @error('field')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="degree_title" class="mb-2">Degree Title</label>
                            <input type="text" class="form-control @error('degree_title') is-invalid @enderror"
                                id="degree_title" name="degree_title" value="{{ old('degree_title', $data->degree_title) }}"
                                placeholder="mis. Aircraft Design and Engineering">
                            @error('degree_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" style="color:#6c757d;">Gelar akademik yang didapat, boleh beda dari nama Major. Boleh dikosongkan.</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="key_courses" class="mb-2">Key Courses</label>
                            <textarea class="form-control @error('key_courses') is-invalid @enderror" id="key_courses"
                                name="key_courses" rows="4"
                                placeholder="Daftar mata kuliah, mis. Engineering Mechanics / Aircraft Structural Design / Flight Dynamics">{{ old('key_courses', $data->key_courses) }}</textarea>
                            @error('key_courses')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" style="color:#6c757d;">Boleh dikosongkan.</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="entry_requirements" class="mb-2">Entry Requirements</label>
                            <textarea class="form-control @error('entry_requirements') is-invalid @enderror" id="entry_requirements"
                                name="entry_requirements" rows="4"
                                placeholder="Syarat masuk, mis. Master degree students under age 35 / Non-Chinese National">{{ old('entry_requirements', $data->entry_requirements) }}</textarea>
                            @error('entry_requirements')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" style="color:#6c757d;">Boleh dikosongkan.</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="min_budget" class="mb-2">Min Budget</label>
                            <input type="number" min="0" class="form-control @error('min_budget') is-invalid @enderror"
                                id="min_budget" name="min_budget" value="{{ old('min_budget', $data->min_budget) }}"
                                placeholder="0">
                            @error('min_budget')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="max_budget" class="mb-2">Max Budget</label>
                            <input type="number" min="0" class="form-control @error('max_budget') is-invalid @enderror"
                                id="max_budget" name="max_budget" value="{{ old('max_budget', $data->max_budget) }}"
                                placeholder="0">
                            @error('max_budget')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr>
                    <label class="form-label">Degree &amp; Intake</label>
                    <div class="form-text mb-2" style="color:#6c757d;">Boleh dikosongkan, atau isi lebih dari satu kombinasi (mis. Bachelor - September - 4 Years, Master - March - 2.5 Years).</div>

                    @php
                        $existingDegrees = old('degree_intakes', $data->degrees->map(function ($d) {
                            return ['degree' => $d->degree, 'intake' => $d->intake, 'duration' => $d->duration];
                        })->toArray());
                        if (empty($existingDegrees)) {
                            $existingDegrees = [['degree' => null, 'intake' => null, 'duration' => null]];
                        }
                    @endphp

                    <div id="degreeIntakeRows">
                        @foreach ($existingDegrees as $index => $row)
                            <div class="degree-intake-row border rounded p-3 mb-3">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Degree</label>
                                        <input type="text" name="degree_intakes[{{ $index }}][degree]"
                                            class="form-control @error('degree_intakes.' . $index . '.degree') is-invalid @enderror"
                                            value="{{ old('degree_intakes.' . $index . '.degree', $row['degree'] ?? '') }}"
                                            placeholder="Degree">
                                        @error('degree_intakes.' . $index . '.degree')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Intake</label>
                                        <input type="text" name="degree_intakes[{{ $index }}][intake]"
                                            class="form-control @error('degree_intakes.' . $index . '.intake') is-invalid @enderror"
                                            value="{{ old('degree_intakes.' . $index . '.intake', $row['intake'] ?? '') }}"
                                            placeholder="Intake">
                                        @error('degree_intakes.' . $index . '.intake')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Duration</label>
                                        <input type="text" name="degree_intakes[{{ $index }}][duration]"
                                            class="form-control @error('degree_intakes.' . $index . '.duration') is-invalid @enderror"
                                            value="{{ old('degree_intakes.' . $index . '.duration', $row['duration'] ?? '') }}"
                                            placeholder="mis. 4 Years">
                                        @error('degree_intakes.' . $index . '.duration')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-12">
                                        <button type="button" class="btn btn-outline-danger btn-remove-row w-100">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="btnAddDegreeIntakeRow" class="btn btn-outline-primary mb-4">
                        + Tambah Degree/Intake
                    </button>

                    <hr>
                    <label class="form-label">Payment</label>
                    <div class="form-text mb-2" style="color:#6c757d;">Boleh dikosongkan, atau isi lebih dari satu item biaya (mis. Registration Fee - Indonesia, Tuition Fee - China). Mata uang mengikuti lokasi bayar yang dipilih.</div>

                    @php
                        $existingPayments = old('payments', $data->payments->map(function ($p) {
                            return ['location' => $p->location, 'name' => $p->name, 'amount' => $p->amount, 'fee_type' => $p->fee_type];
                        })->toArray());
                        if (empty($existingPayments)) {
                            $existingPayments = [['location' => null, 'name' => null, 'amount' => null, 'fee_type' => null]];
                        }
                    @endphp

                    <div id="paymentRows">
                        @foreach ($existingPayments as $index => $row)
                            <div class="payment-row border rounded p-3 mb-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Lokasi Bayar</label>
                                        <select name="payments[{{ $index }}][location]"
                                            class="form-select @error('payments.' . $index . '.location') is-invalid @enderror">
                                            <option value="">Choose...</option>
                                            <option value="indonesia" {{ ($row['location'] ?? '') === 'indonesia' ? 'selected' : '' }}>Indonesia (Rp)</option>
                                            <option value="china" {{ ($row['location'] ?? '') === 'china' ? 'selected' : '' }}>China (元)</option>
                                        </select>
                                        @error('payments.' . $index . '.location')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nama Biaya</label>
                                        <input type="text" name="payments[{{ $index }}][name]"
                                            class="form-control @error('payments.' . $index . '.name') is-invalid @enderror"
                                            value="{{ old('payments.' . $index . '.name', $row['name'] ?? '') }}"
                                            placeholder="mis. Registration Fee">
                                        @error('payments.' . $index . '.name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Jumlah</label>
                                        <input type="number" min="0" name="payments[{{ $index }}][amount]"
                                            class="form-control @error('payments.' . $index . '.amount') is-invalid @enderror"
                                            value="{{ old('payments.' . $index . '.amount', $row['amount'] ?? '') }}"
                                            placeholder="0">
                                        @error('payments.' . $index . '.amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tipe Biaya</label>
                                        <select name="payments[{{ $index }}][fee_type]"
                                            class="form-select @error('payments.' . $index . '.fee_type') is-invalid @enderror">
                                            <option value="">Choose...</option>
                                            <option value="registration_fee" {{ ($row['fee_type'] ?? '') === 'registration_fee' ? 'selected' : '' }}>Registration Fee</option>
                                            <option value="tuition_fee" {{ ($row['fee_type'] ?? '') === 'tuition_fee' ? 'selected' : '' }}>Tuition Fee</option>
                                            <option value="dormitory_fee" {{ ($row['fee_type'] ?? '') === 'dormitory_fee' ? 'selected' : '' }}>Dormitory Fee</option>
                                            <option value="deposit_china" {{ ($row['fee_type'] ?? '') === 'deposit_china' ? 'selected' : '' }}>Deposit Fee (China)</option>
                                            <option value="other" {{ ($row['fee_type'] ?? '') === 'other' ? 'selected' : '' }}>Lainnya</option>
                                        </select>
                                        <div class="form-text" style="font-size:11.5px;">Dipakai fitur Apply Kampus untuk otomatis mendeteksi Registration Fee.</div>
                                        @error('payments.' . $index . '.fee_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-12">
                                        <button type="button" class="btn btn-outline-danger btn-remove-payment-row w-100">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="btnAddPaymentRow" class="btn btn-outline-primary mb-4">
                        + Tambah Payment
                    </button>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="language" class="mb-2">Language</label>
                            <input type="text" class="form-control @error('language') is-invalid @enderror" id="language"
                                name="language" value="{{ old('language', $data->language) }}" placeholder="Enter language...">
                            @error('language')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="row">
                    <div class="col-xxl-12 col-xl-8 col-lg-8 col-md-7 mt-xxl-0 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-xxl-12 mb-4">
                                    <label for="scholarship_available">Scholarship Available</label>
                                    <select class="form-select @error('scholarship_available') is-invalid @enderror"
                                        id="scholarship_available" name="scholarship_available">
                                        <option value="">Choose...</option>
                                        <option value="1"
                                            {{ old('scholarship_available', (string) ((int) $data->scholarship_available)) === '1' ? 'selected' : '' }}>
                                            Yes
                                        </option>
                                        <option value="0"
                                            {{ old('scholarship_available', (string) ((int) $data->scholarship_available)) === '0' ? 'selected' : '' }}>
                                            No
                                        </option>
                                    </select>
                                    @error('scholarship_available')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="status">Status</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                        <option value="">Choose...</option>
                                        <option value="active"
                                            {{ old('status', $data->status) === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive"
                                            {{ old('status', $data->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-12 col-xl-4 col-lg-4 col-md-5 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-sm-12 mb-3">
                                    <button type="submit" class="btn btn-success w-100">Update Profile</button>
                                </div>
                                <div class="col-sm-12">
                                    {{-- Cancel balik ke halaman profile University-nya, sama arahnya
                                         dengan redirect setelah Update di atas. --}}
                                    <a href="{{ route('quiz.university.show', $data->university_id) }}"
                                        class="btn btn-outline-secondary w-100">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

{{-- Template baris baru, dipakai JS saat klik "Tambah Degree/Intake" (sama
     persis polanya dengan quiz.university-profile.create). --}}
<template id="degreeIntakeRowTemplate">
    <div class="degree-intake-row border rounded p-3 mb-3">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Degree</label>
                <input type="text" name="degree_intakes[__INDEX__][degree]" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Intake</label>
                <input type="text" name="degree_intakes[__INDEX__][intake]" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Duration</label>
                <input type="text" name="degree_intakes[__INDEX__][duration]" class="form-control" placeholder="mis. 4 Years">
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-12">
                <button type="button" class="btn btn-outline-danger btn-remove-row w-100">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</template>

{{-- Template baris baru Payment, dipakai JS saat klik "Tambah Payment". --}}
<template id="paymentRowTemplate">
    <div class="payment-row border rounded p-3 mb-3">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Lokasi Bayar</label>
                <select name="payments[__INDEX__][location]" class="form-select">
                    <option value="">Choose...</option>
                    <option value="indonesia">Indonesia (Rp)</option>
                    <option value="china">China (元)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nama Biaya</label>
                <input type="text" name="payments[__INDEX__][name]" class="form-control" placeholder="mis. Registration Fee">
            </div>
            <div class="col-md-2">
                <label class="form-label">Jumlah</label>
                <input type="number" min="0" name="payments[__INDEX__][amount]" class="form-control" placeholder="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipe Biaya</label>
                <select name="payments[__INDEX__][fee_type]" class="form-select">
                    <option value="">Choose...</option>
                    <option value="registration_fee">Registration Fee</option>
                    <option value="tuition_fee">Tuition Fee</option>
                    <option value="dormitory_fee">Dormitory Fee</option>
                    <option value="deposit_china">Deposit Fee (China)</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-12">
                <button type="button" class="btn btn-outline-danger btn-remove-payment-row w-100">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    (function () {
        // rowIndex dimulai dari jumlah baris Degree/Intake yang sudah ada
        // (bukan hardcode 1 seperti di create), supaya index baris baru
        // tidak bentrok sama baris existing.
        var rowIndex = {{ count($existingDegrees) }};
        var container = document.getElementById('degreeIntakeRows');
        var template = document.getElementById('degreeIntakeRowTemplate');

        document.getElementById('btnAddDegreeIntakeRow').addEventListener('click', function () {
            var html = template.innerHTML.replaceAll('__INDEX__', rowIndex);
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            container.appendChild(wrapper.firstElementChild);
            rowIndex++;
        });

        container.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-remove-row')) {
                var rows = container.querySelectorAll('.degree-intake-row');
                if (rows.length > 1) {
                    e.target.closest('.degree-intake-row').remove();
                } else {
                    // Baris terakhir tetap dibiarkan ada, tapi boleh kosong
                    // (degree/intake/duration nullable) — jadi cukup dikosongkan saja.
                    var inputs = e.target.closest('.degree-intake-row').querySelectorAll('input');
                    inputs.forEach(function (input) { input.value = ''; });
                }
            }
        });
    })();

    (function () {
        var rowIndex = {{ count($existingPayments) }};
        var container = document.getElementById('paymentRows');
        var template = document.getElementById('paymentRowTemplate');

        document.getElementById('btnAddPaymentRow').addEventListener('click', function () {
            var html = template.innerHTML.replaceAll('__INDEX__', rowIndex);
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            container.appendChild(wrapper.firstElementChild);
            rowIndex++;
        });

        container.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-remove-payment-row')) {
                var rows = container.querySelectorAll('.payment-row');
                if (rows.length > 1) {
                    e.target.closest('.payment-row').remove();
                } else {
                    // Baris terakhir tetap dibiarkan ada, tapi boleh kosong
                    // (semua field payment nullable) — jadi cukup dikosongkan saja.
                    var row = e.target.closest('.payment-row');
                    row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                    row.querySelectorAll('select').forEach(function (select) { select.value = ''; });
                }
            }
        });
    })();
</script>

@endsection
