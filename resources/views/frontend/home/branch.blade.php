@extends('frontend.home.layout')

{{--
    Halaman 1 cabang InaYule (25 November 2026), mis. /kelas-mandarin/pluit.
    Alur pengunjung: Program (Course Type) -> Belajar dengan (Course Class) -> katalog kelas.
    Placement Test memakai link milik cabang ini (Company > Branch > Link Placement Test).
--}}

@php
    $online = $branch->isOnline();
    $phone = preg_replace('/\D+/', '', (string) $branch->handphone);
    $whatsapp = $phone ? 'https://wa.me/'.(str_starts_with($phone, '0') ? '62'.substr($phone, 1) : $phone) : null;
@endphp

@section('title', 'Kursus Mandarin InaYule '.$branch->name.' | INASTUDY')
@section('description', 'Kelas Mandarin InaYule di '.$branch->name.': pilih program, jenis kelas, dan level yang pas, lalu atur jadwal bersama pengajar.')

@section('content')
    <section class="page-section page-section--catalog page-section--branch">
        <div class="container">
            <a href="{{ route('frontend.inayule.classes') }}" class="link-arrow link-arrow--back branch-back">Semua lokasi</a>

            <div class="branch-hero">
                <div class="branch-hero__text">
                    <span class="eyebrow">InaYule &middot; {{ $online ? 'Kelas Online' : 'Kelas Tatap Muka' }}</span>
                    <h1 class="section-title">
                        @if ($online)
                            Belajar Mandarin <em>dari rumah</em>
                        @else
                            Belajar Mandarin di <em>{{ $branch->name }}</em>
                        @endif
                    </h1>
                    <p class="section-lead">
                        @if ($online)
                            Bertemu pengajar lewat video call, dengan jadwal yang kamu pilih sendiri.
                        @else
                            Belajar langsung bersama pengajar, di kelas yang nyaman dan dekat denganmu.
                        @endif
                    </p>

                    <ul class="branch-contact">
                        @if (! $online && $branch->address)
                            <li><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $branch->address }}</li>
                        @endif
                        @if ($whatsapp)
                            <li><a href="{{ $whatsapp }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp" aria-hidden="true"></i> Tanya admin {{ $branch->name }}</a></li>
                        @endif
                    </ul>
                </div>

                @if ($branch->placement_test_url)
                    {{-- Placement Test cabang ini: membantu pengunjung yang belum tahu levelnya. --}}
                    <aside class="placement-card">
                        <span class="placement-card__seal" lang="zh" aria-hidden="true">测</span>
                        <h2>Belum tahu levelmu?</h2>
                        <p>Ikuti Placement Test singkat. Hasilnya dikirim lewat WhatsApp, lengkap dengan rekomendasi kelas yang pas.</p>
                        <a href="{{ $branch->placement_test_url }}" class="btn btn--primary btn--block">
                            Ikuti Placement Test <i class="bi bi-pencil-square" aria-hidden="true"></i>
                        </a>
                    </aside>
                @endif
            </div>

            {{-- Tiga langkah, supaya pengunjung tahu apa yang terjadi setelah membeli. --}}
            <ol class="start-steps">
                <li><span>1</span><div><strong>Pilih kelas</strong><small>Sesuai program, suasana kelas, dan levelmu.</small></div></li>
                <li><span>2</span><div><strong>Beli paket</strong><small>1 credit = 1 jam belajar.</small></div></li>
                <li><span>3</span><div><strong>Pilih jadwal</strong><small>Cocokkan waktumu dengan jam buka pengajar.</small></div></li>
            </ol>

            @include('frontend.home._packages', ['packages' => $packages, 'pills' => true])

            @if ($otherBranches->isNotEmpty())
                <nav class="other-branches" aria-label="Lokasi lain">
                    <span>Lokasi lain:</span>
                    @foreach ($otherBranches as $other)
                        <a href="{{ route('frontend.inayule.branch', $other->slug()) }}">
                            <i class="bi bi-{{ $other->isOnline() ? 'laptop' : 'geo-alt' }}" aria-hidden="true"></i> {{ $other->name }}
                        </a>
                    @endforeach
                </nav>
            @endif
        </div>
    </section>
@endsection
