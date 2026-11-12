@extends('frontend.home.layout')

@section('title', 'Kelas Mandarin InaYule | INASTUDY | CHINA EDUCATION CONSULTANT')
@section('description', 'Daftar kelas kursus Mandarin InaYule: level, durasi, jumlah sesi, dan harga.')

@section('content')
    <section class="page-section page-section--catalog">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">InaYule &middot; Kursus Mandarin</span>
                    <h1 class="section-title">Semua kelas <em>InaYule</em></h1>
                    <p class="section-lead">Pilih kelas yang pas, lalu login untuk membeli dan mengatur jadwal belajarmu.</p>
                </div>
                <a href="{{ route('home') }}#inayule" class="link-arrow link-arrow--back">Kembali ke beranda</a>
            </div>

            @include('frontend.home._packages', ['packages' => $packages])
        </div>
    </section>
@endsection
