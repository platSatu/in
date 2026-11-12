<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'INASTUDY | CHINA EDUCATION CONSULTANT')</title>
    <meta name="description" content="@yield('description', 'China Education Consultant. InaStudy mendampingi kuliah ke China: pilih universitas, beasiswa, dokumen, visa, kursus Mandarin InaYule, dan perjalanan InaTrip.')">
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;1,500&family=Poppins:wght@400;500;600&family=Noto+Serif+SC:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('frontend/home/site.css') }}?v={{ filemtime(public_path('frontend/home/site.css')) }}">
    <link rel="stylesheet" href="{{ asset('frontend/home/home.css') }}?v={{ filemtime(public_path('frontend/home/home.css')) }}">
</head>
<body>
    @include('frontend.home.header')

    <main>
        @yield('content')
    </main>

    @include('frontend.home.footer')

    <script src="{{ asset('frontend/home/home.js') }}?v={{ filemtime(public_path('frontend/home/home.js')) }}" defer></script>
</body>
</html>
