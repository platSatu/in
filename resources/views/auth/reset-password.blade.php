<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>INASTUDY | RESET PASSWORD | CHINA EDUCATION CONSULTANT</title>
    <!--favicon-->
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">

    <!--plugins-->
    <link href="{{ asset('authLogin') }}/assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('authLogin') }}/assets/plugins/metismenu/metisMenu.min.css">
    <link rel="stylesheet" type="text/css" href="{{ asset('authLogin') }}/assets/plugins/metismenu/mm-vertical.css">
    <!--bootstrap css-->
    <link href="{{ asset('authLogin') }}/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">
    <!--main css-->
    <link href="{{ asset('authLogin') }}/assets/css/bootstrap-extended.css" rel="stylesheet">
    <link href="{{ asset('authLogin') }}/sass/main.css" rel="stylesheet">
    <link href="{{ asset('authLogin') }}/sass/dark-theme.css" rel="stylesheet">
    <link href="{{ asset('authLogin') }}/sass/responsive.css" rel="stylesheet">

</head>

<body>

    <!--authentication-->
    {{-- FIX 23 Nov 2026: dulu memakai layout Breeze (@vite) -> 500 di server karena public/build tidak ada.
         Sekarang mandiri, sama seperti halaman Forgot Password. --}}

    <div class="section-authentication-cover">
        <div class="">
            <div class="row g-0">

                <div
                    class="col-12 col-xl-7 col-xxl-8 auth-cover-left align-items-center justify-content-center d-none d-xl-flex border-end">

                    <div class="card rounded-0 mb-0 border-0 shadow-none bg-transparent">
                        <div class="card-body">
                            <img src="{{ asset('authLogin') }}/assets/images/auth/reset-password1-ina.png"
                                class="img-fluid auth-img-cover-login" width="650" alt="">
                        </div>
                    </div>

                </div>

                <div class="col-12 col-xl-5 col-xxl-4 auth-cover-right align-items-center justify-content-center">
                    <div class="card rounded-0 m-3 mb-0 border-0 shadow-none">
                        <div class="card-body p-sm-5">
                            <img src="{{ asset('frontend/img/Logo.png') }}" class="mb-4" width="145"
                                alt="">
                            <h4 class="fw-bold">Reset Password</h4>
                            <p class="mb-0">Masukkan password baru untuk akun Anda.</p>

                            <div class="form-body mt-4">
                                <form method="POST" action="{{ route('password.store') }}" class="row g-3">
                                    @csrf

                                    <!-- Password Reset Token -->
                                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                                    <!-- Email Address -->
                                    <div class="col-12">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email"
                                            value="{{ old('email', $request->email) }}" placeholder="example@user.com"
                                            required autocomplete="username">
                                    </div>

                                    @error('email')
                                        <div class="col-12">
                                            <div class="alert alert-danger mb-0">{{ $message }}</div>
                                        </div>
                                    @enderror

                                    <!-- Password -->
                                    <div class="col-12">
                                        <label class="form-label">Password Baru</label>
                                        <input type="password" class="form-control" name="password"
                                            placeholder="Password baru" required autofocus autocomplete="new-password">
                                    </div>

                                    @error('password')
                                        <div class="col-12">
                                            <div class="alert alert-danger mb-0">{{ $message }}</div>
                                        </div>
                                    @enderror

                                    <!-- Confirm Password -->
                                    <div class="col-12">
                                        <label class="form-label">Ulangi Password Baru</label>
                                        <input type="password" class="form-control" name="password_confirmation"
                                            placeholder="Ulangi password baru" required autocomplete="new-password">
                                    </div>

                                    @error('password_confirmation')
                                        <div class="col-12">
                                            <div class="alert alert-danger mb-0">{{ $message }}</div>
                                        </div>
                                    @enderror

                                    <!-- Buttons -->
                                    <div class="col-12">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">Reset Password</button>
                                            <a href="{{ route('login') }}" class="btn btn-light">Back to Login</a>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
            <!--end row-->
        </div>
    </div>

    <!--authentication-->

</body>

</html>
