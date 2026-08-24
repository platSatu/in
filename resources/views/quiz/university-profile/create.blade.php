@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('quiz.university-profile.store') }}" method="POST">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="university_id" class="mb-2">University</label>

                            @php
                                $lockedUniversityId = old('university_id', $selectedUniversityId ?? null);
                                $lockedUniversity = $lockedUniversityId ? $universities->firstWhere('id', $lockedUniversityId) : null;
                            @endphp

                            @if($lockedUniversity && !$errors->has('university_id'))
                                <input type="text" class="form-control" value="{{ $lockedUniversity->name }}" disabled readonly>
                                <input type="hidden" name="university_id" value="{{ $lockedUniversity->id }}">
                                <div class="form-text">
                                    Profile ini akan dikaitkan ke university di atas.
                                    <a href="{{ route('quiz.university-profile.create') }}">Ganti university</a>
                                </div>
                            @else
                                <select class="form-select @error('university_id') is-invalid @enderror" id="university_id" name="university_id">
                                    <option value="">Choose university...</option>
                                    @foreach ($universities as $university)
                                        <option value="{{ $university->id }}" {{ $lockedUniversityId == $university->id ? 'selected' : '' }}>
                                            {{ $university->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('university_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label class="mb-2">Major</label>

                            {{--
                                Major diturunkan otomatis dari university yang dipilih di atas
                                (University -> Major), bukan diketik manual lagi. Field ini
                                sengaja tidak punya "name" — nilainya dihitung ulang di server
                                dari university_id supaya tidak bisa dipalsukan lewat form.
                            --}}
                            @php
                                $lockedMajor = $lockedUniversity->major ?? null;
                            @endphp

                            <input type="text" class="form-control" value="{{ $lockedMajor->name ?? '-' }}" disabled readonly>
                            <div class="form-text">
                                @if($lockedUniversity)
                                    {{ $lockedMajor ? 'Diturunkan dari Major university ini.' : 'University ini belum punya Major.' }}
                                @else
                                    Pilih university dulu untuk melihat Major-nya.
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="degree_title" class="mb-2">Degree Title</label>
                            <input type="text" class="form-control @error('degree_title') is-invalid @enderror"
                                id="degree_title" name="degree_title" value="{{ old('degree_title') }}"
                                placeholder="mis. Aircraft Design and Engineering">
                            @error('degree_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" style="color:#6c757d;">Gelar akademik yang didapat, boleh beda dari nama Major di atas. Boleh dikosongkan.</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="key_courses" class="mb-2">Key Courses</label>
                            <textarea class="form-control @error('key_courses') is-invalid @enderror" id="key_courses"
                                name="key_courses" rows="4"
                                placeholder="Daftar mata kuliah, mis. Engineering Mechanics / Aircraft Structural Design / Flight Dynamics">{{ old('key_courses') }}</textarea>
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
                                placeholder="Syarat masuk, mis. Master degree students under age 35 / Non-Chinese National">{{ old('entry_requirements') }}</textarea>
                            @error('entry_requirements')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" style="color:#6c757d;">Boleh dikosongkan.</div>
                        </div>
                    </div>

                    <hr>
                    <label class="form-label">Degree &amp; Intake</label>
                    <div class="form-text mb-2" style="color:#6c757d;">Boleh dikosongkan, atau isi lebih dari satu kombinasi (mis. Bachelor - September - 4 Years, Master - March - 2.5 Years).</div>

                    <div id="degreeIntakeRows">

                        {{-- Baris awal (index 0) --}}
                        <div class="degree-intake-row border rounded p-3 mb-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Degree</label>
                                    <input type="text" name="degree_intakes[0][degree]"
                                        class="form-control @error('degree_intakes.0.degree') is-invalid @enderror"
                                        value="{{ old('degree_intakes.0.degree') }}" placeholder="Degree">
                                    @error('degree_intakes.0.degree')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Intake</label>
                                    <input type="text" name="degree_intakes[0][intake]"
                                        class="form-control @error('degree_intakes.0.intake') is-invalid @enderror"
                                        value="{{ old('degree_intakes.0.intake') }}" placeholder="Intake">
                                    @error('degree_intakes.0.intake')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Duration</label>
                                    <input type="text" name="degree_intakes[0][duration]"
                                        class="form-control @error('degree_intakes.0.duration') is-invalid @enderror"
                                        value="{{ old('degree_intakes.0.duration') }}" placeholder="mis. 4 Years">
                                    @error('degree_intakes.0.duration')
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
                    </div>

                    <button type="button" id="btnAddDegreeIntakeRow" class="btn btn-outline-primary mb-4">
                        + Tambah Degree/Intake
                    </button>

                    <hr>
                    <label class="form-label">Payment</label>
                    <div class="form-text mb-2" style="color:#6c757d;">Boleh dikosongkan, atau isi lebih dari satu item biaya (mis. Registration Fee - Indonesia, Tuition Fee - China). Mata uang mengikuti lokasi bayar yang dipilih.</div>

                    <div id="paymentRows">

                        {{-- Baris awal (index 0) --}}
                        <div class="payment-row border rounded p-3 mb-3">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Lokasi Bayar</label>
                                    <select name="payments[0][location]"
                                        class="form-select @error('payments.0.location') is-invalid @enderror">
                                        <option value="">Choose...</option>
                                        <option value="indonesia" {{ old('payments.0.location') === 'indonesia' ? 'selected' : '' }}>Indonesia (Rp)</option>
                                        <option value="china" {{ old('payments.0.location') === 'china' ? 'selected' : '' }}>China (元)</option>
                                    </select>
                                    @error('payments.0.location')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Nama Biaya</label>
                                    <input type="text" name="payments[0][name]"
                                        class="form-control @error('payments.0.name') is-invalid @enderror"
                                        value="{{ old('payments.0.name') }}" placeholder="mis. Registration Fee">
                                    @error('payments.0.name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Jumlah</label>
                                    <input type="number" min="0" name="payments[0][amount]"
                                        class="form-control @error('payments.0.amount') is-invalid @enderror"
                                        value="{{ old('payments.0.amount') }}" placeholder="0">
                                    @error('payments.0.amount')
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
                    </div>

                    <button type="button" id="btnAddPaymentRow" class="btn btn-outline-primary mb-4">
                        + Tambah Payment
                    </button>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="min_budget" class="mb-2">Min Budget</label>
                            <input type="number" min="0" class="form-control @error('min_budget') is-invalid @enderror"
                                id="min_budget" name="min_budget" value="{{ old('min_budget') }}" placeholder="0">
                            @error('min_budget')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="max_budget" class="mb-2">Max Budget</label>
                            <input type="number" min="0" class="form-control @error('max_budget') is-invalid @enderror"
                                id="max_budget" name="max_budget" value="{{ old('max_budget') }}" placeholder="0">
                            @error('max_budget')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="language" class="mb-2">Language</label>
                            <input type="text" class="form-control @error('language') is-invalid @enderror" id="language"
                                name="language" value="{{ old('language') }}" placeholder="Enter language...">
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
                                        <option value="1" {{ old('scholarship_available') === '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ old('scholarship_available') === '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                    @error('scholarship_available')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="status">Status</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                        <option value="">Choose...</option>
                                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                    <button type="submit" class="btn btn-success w-100">Create Profile</button>
                                </div>
                                <div class="col-sm-12">
                                    {{-- Kalau university-nya sudah terkunci (datang dari tombol +Profile
                                         di University), Cancel balik ke halaman profile university itu;
                                         kalau belum, balik ke index profile seperti biasa. --}}
                                    <a href="{{ $lockedUniversity ? route('quiz.university.show', $lockedUniversity->id) : route('quiz.university-profile.index') }}"
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

{{-- Template baris baru, dipakai JS saat klik "Tambah Degree/Intake" --}}
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

{{-- Template baris baru Payment, dipakai JS saat klik "Tambah Payment" --}}
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
            <div class="col-md-5">
                <label class="form-label">Nama Biaya</label>
                <input type="text" name="payments[__INDEX__][name]" class="form-control" placeholder="mis. Registration Fee">
            </div>
            <div class="col-md-4">
                <label class="form-label">Jumlah</label>
                <input type="number" min="0" name="payments[__INDEX__][amount]" class="form-control" placeholder="0">
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
        var rowIndex = 1; // index 0 sudah dipakai baris pertama
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
                    // (degree/intake nullable) — jadi cukup dikosongkan saja.
                    var inputs = e.target.closest('.degree-intake-row').querySelectorAll('input');
                    inputs.forEach(function (input) { input.value = ''; });
                }
            }
        });
    })();

    (function () {
        // Sama persis polanya dengan IIFE Degree/Intake di atas, cuma
        // dipisah supaya rowIndex-nya independen (index Payment tidak boleh
        // ikut kepotong sama index Degree/Intake).
        var rowIndex = 1; // index 0 sudah dipakai baris pertama
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
