@extends('frontend.home.layout')

@section('title', 'Kelas Mandarin INA YULE | INASTUDY | CHINA EDUCATION CONSULTANT')
@section('description', 'Kursus Mandarin INA YULE di Mall of Indonesia, Pluit, dan Online. Pilih lokasi, lalu temukan kelas sesuai level dan waktumu.')

@section('content')
    <section class="page-section page-section--catalog">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">INA YULE &middot; Kursus Mandarin</span>
                    <h1 class="section-title">Mau belajar <em>di mana?</em></h1>
                    <p class="section-lead">Setiap lokasi punya kelas, harga, dan Placement Test sendiri. Pilih yang paling dekat atau paling nyaman untukmu.</p>
                </div>
                <a href="{{ route('home') }}#inayule" class="link-arrow link-arrow--back">Kembali ke beranda</a>
            </div>

            @include('frontend.home._branches', ['branches' => $branches])
        </div>
    </section>
@endsection
