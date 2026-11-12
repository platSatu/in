<header class="site-header" id="siteHeader">
    <div class="container site-header__inner">
        <a href="{{ route('home') }}#inastudy" class="brand" aria-label="InaStudy, ke atas">
            <img src="{{ asset('frontend/img/Logo.png') }}" alt="" width="889" height="713">
        </a>

        <button class="nav-toggle" id="navToggle" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="siteNav">
            <span></span><span></span><span></span>
        </button>

        <nav class="site-nav" id="siteNav" aria-label="Menu utama">
            <a href="{{ route('home') }}#inastudy">Home</a>
            <a href="{{ route('home') }}#inayule">InaYule</a>
            <a href="{{ route('home') }}#inatrip">InaTrip</a>
            <a href="{{ route('frontend.university.catalog') }}">Partner University</a>
            <a href="{{ route('home') }}#lokasi">Location</a>
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn--primary btn--sm">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn--primary btn--sm">Login</a>
            @endauth
        </nav>
    </div>
</header>
