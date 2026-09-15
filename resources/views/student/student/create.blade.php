@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h4 class="mb-0">Add Student</h4>
        <a href="{{ route('student.student.index') }}" class="btn btn-outline-secondary">&larr; Kembali ke daftar student</a>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <form action="{{ route('student.student.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Foto</label>
                        <input type="file" name="images" class="form-control @error('images') is-invalid @enderror" accept="image/*">
                        @error('images')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Handphone</label>
                            <input type="text" name="handphone" class="form-control @error('handphone') is-invalid @enderror" value="{{ old('handphone') }}">
                            @error('handphone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Branch <span class="text-muted">(opsional)</span></label>
                            {{-- Fix (14 September 2026, permintaan user): sales (scope 'self')
                                 dikunci ke branch tempat dia sendiri terdaftar (lewat Company >
                                 Division > Add User), supaya tidak bisa salah/sengaja pilih
                                 branch lain -- lihat StudentController::create()/store() &
                                 ownBranchId(). Field disabled TIDAK ikut ter-submit, jadi nilai
                                 sebenarnya dikirim lewat hidden input, dan tetap dipaksa ulang
                                 di server (store()) apapun yang terkirim. --}}
                            @if ($isSelfScoped)
                                @php $lockedBranch = $companyBranches->firstWhere('id', $lockedBranchId); @endphp
                                <select class="form-select" disabled>
                                    <option selected>{{ $lockedBranch->name ?? '-- Branch Anda belum terdaftar di divisi manapun --' }}</option>
                                </select>
                                <input type="hidden" name="branch_id" value="{{ $lockedBranchId }}">
                                <div class="form-text">Otomatis mengikuti branch Anda.</div>
                            @else
                                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                    <option value="">-- Belum ada Branch --</option>
                                    @foreach ($companyBranches as $companyBranch)
                                        <option value="{{ $companyBranch->id }}" {{ old('branch_id') == $companyBranch->id ? 'selected' : '' }}>
                                            {{ $companyBranch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Form <span class="text-muted">(opsional)</span></label>
                            <select name="form_id" class="form-select @error('form_id') is-invalid @enderror">
                                <option value="">-- Belum ada Form --</option>
                                @foreach ($forms as $form)
                                    <option value="{{ $form->id }}" {{ old('form_id') == $form->id ? 'selected' : '' }}>
                                        {{ $form->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Kode Sales</label>
                            <input type="text" name="sales_id" class="form-control @error('sales_id') is-invalid @enderror" value="{{ old('sales_id') }}" placeholder="Kode sales (opsional)">
                            @error('sales_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Fix (14 September 2026, permintaan user): sama seperti di form
                             Edit -- superadmin bisa langsung assign sales saat menambahkan
                             student baru, tidak perlu buka Edit lagi setelahnya. --}}
                        <div class="col-md-6">
                            <label class="form-label">Assign ke Sales <span class="text-muted">(opsional)</span></label>
                            {{-- Fix (14 September 2026, permintaan user): sales (scope 'self')
                                 otomatis dikunci assign ke DIRINYA SENDIRI di sini -- supaya
                                 student yang baru dia input tidak hilang dari daftarnya sendiri
                                 (scope 'self' cuma memfilter dari handled_by_user_id, lihat
                                 App\Helpers\DataScope::applyBranchDivisionScope()), dan tidak
                                 bisa sengaja/salah assign ke sales lain. --}}
                            @if ($isSelfScoped)
                                @php $lockedSalesUser = $salesUsers->firstWhere('id', $lockedSalesUserId); @endphp
                                <select class="form-select" disabled>
                                    <option selected>{{ $lockedSalesUser->name ?? '(Anda)' }} ({{ $lockedSalesUser->sales_code ?? 'belum ada kode' }})</option>
                                </select>
                                <input type="hidden" name="handled_by_user_id" value="{{ $lockedSalesUserId }}">
                                <div class="form-text">Otomatis di-assign ke Anda sendiri.</div>
                            @else
                                <select name="handled_by_user_id" class="form-select @error('handled_by_user_id') is-invalid @enderror">
                                    <option value="">-- Belum di-assign --</option>
                                    @foreach ($salesUsers as $salesUser)
                                        <option value="{{ $salesUser->id }}" {{ old('handled_by_user_id') == $salesUser->id ? 'selected' : '' }}>
                                            {{ $salesUser->name }} ({{ $salesUser->sales_code ?? 'belum ada kode' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('handled_by_user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('student.student.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

@endsection
