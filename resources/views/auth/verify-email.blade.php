<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>INASTUDY | VERIFY EMAIL | CHINA EDUCATION CONSULTANT</title>
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

    <div class="section-authentication-cover">
        <div class="">
            <div class="row g-0">

                <div
                    class="col-12 col-xl-7 col-xxl-8 auth-cover-left align-items-center justify-content-center d-none d-xl-flex border-end">

                    <div class="card rounded-0 mb-0 border-0 shadow-none bg-transparent">
                        <div class="card-body">
                            <img src="{{ asset('authLogin') }}/assets/images/auth/reset-password1.png"
                                class="img-fluid auth-img-cover-login" width="650" alt="">
                        </div>
                    </div>

                </div>

                <div class="col-12 col-xl-5 col-xxl-4 auth-cover-right align-items-center justify-content-center">
                    <div class="card rounded-0 m-3 mb-0 border-0 shadow-none">
                        <div class="card-body p-sm-5">
                            <img src="{{ asset('frontend/img/Logo.png') }}" class="mb-4" width="145" alt="">
                            <h4 class="fw-bold">Verifikasi Email Anda</h4>
                            <p class="mb-0">
                                Terima kasih telah mendaftar! Sebelum memulai, mohon verifikasi email Anda dengan
                                mengklik link yang baru saja kami kirimkan. Tidak menerima emailnya? Kami bisa
                                mengirimkan ulang.
                            </p>

                            @if (session('status'))
                                <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                                    {{ session('status') == 'verification-link-sent'
                                        ? 'Link verifikasi baru sudah dikirim ke alamat email yang Anda daftarkan.'
                                        : session('status') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="mt-4 d-grid gap-2">
                                <form method="POST" action="{{ route('verification.send') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100">Kirim Ulang Email Verifikasi</button>
                                </form>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-light w-100">Log Out</button>
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

    <!--plugins-->
    <script src="{{ asset('authLogin') }}/assets/js/jquery.min.js"></script>

</body>

</html>
