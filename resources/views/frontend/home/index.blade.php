@extends('frontend.home.layout')

@section('content')
    @include('frontend.home.inastudy')
    @include('frontend.home.inayule')

    {{-- Section berikut masih kerangka, diisi sesuai brief per section. --}}
    <section class="section section--soon" id="inatrip">
        <div class="container"><span class="eyebrow">InaTrip</span><h2 class="section-title">Sedang disiapkan</h2></div>
    </section>
    @include('frontend.home.cta')
    @include('frontend.home.lokasi')
@endsection
