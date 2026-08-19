@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h4 class="mb-0">Profile</h4>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">&larr; Kembali ke Dashboard</a>
    </div>

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

    <div class="row layout-top-spacing justify-content-center">
        <div class="col-xl-7 col-lg-9 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- === FOTO === --}}
                    <div class="text-center mb-4">
                        <img id="profilePhotoPreview"
                            src="{{ $user->image ? asset($user->image) : asset('frontend') . '/src/assets/img/profile-30.png' }}"
                            alt="{{ $user->name }}"
                            style="width:110px;height:110px;object-fit:cover;border-radius:50%;border:3px solid #f1f1f5;">

                        <div class="mt-3">
                            <label for="image" class="btn btn-sm btn-outline-primary">Ganti Foto</label>
                            <input type="file" name="image" id="image" accept="image/*"
                                class="d-none @error('image') is-invalid @enderror">
                            <div class="form-text">JPG/PNG/WEBP, maks 2MB.</div>
                            @error('image')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- === DATA AKUN (read-only) === --}}
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                        </div>
                    </div>
                    <div class="form-text mb-4">
                        Name & Email tidak bisa diubah sendiri dari sini. Hubungi administrator kalau perlu diperbarui.
                    </div>

                    <hr class="my-4">

                    {{-- === GANTI PASSWORD === --}}
                    <h6 class="mb-3">Ganti Password</h6>
                    <div class="form-text mb-3">Kosongkan bagian ini kalau tidak ingin mengganti password.</div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" name="current_password" id="current_password"
                                class="form-control @error('current_password') is-invalid @enderror"
                                autocomplete="current-password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" name="new_password" id="new_password"
                                class="form-control @error('new_password') is-invalid @enderror"
                                autocomplete="new-password">
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="new_password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                                class="form-control" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

<script>
    (function () {
        var input = document.getElementById('image');
        var preview = document.getElementById('profilePhotoPreview');

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    })();
</script>

@endsection
