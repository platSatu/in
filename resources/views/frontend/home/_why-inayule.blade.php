{{--
    Di bawah kartu lokasi InaYule (25 November 2026): alasan belajar di INA YULE
    dan alur belajarnya dari daftar sampai mulai kelas.
--}}
@php
    $reasons = [
        ['journal-bookmark', 'Materi sesuai kurikulum China', 'Tata bahasa, kosakata, berbicara, membaca, dan menulis dipelajari dengan runtut, persis seperti di China.'],
        ['chat-heart', 'Garansi lancar berbahasa Mandarin', 'Metode yang terstruktur dan teruji memastikan kamu benar-benar bisa memakai bahasanya.'],
        ['people', 'Guru berpengalaman dan profesional', 'Pengajar yang interaktif membantu kamu memahami tata bahasa, pengucapan, dan percakapan dengan lebih mudah.'],
        ['laptop', 'Kelas multiplatform', 'Belajar di kelas atau dari perangkat apa pun, tetap praktis dan efisien sesuai kebutuhanmu.'],
        ['easel', 'Kelas interaktif', 'Diskusi, latihan percakapan, dan simulasi situasi nyata membuat belajar terasa hidup dan menyenangkan.'],
        ['calendar-heart', 'Jadwal yang fleksibel', 'Kamu sendiri yang memilih jam belajar, jadi tetap konsisten tanpa mengganggu aktivitas sehari-hari.'],
    ];

    $journey = [
        ['person-plus', 'Registrasi', 'Buat akun dalam hitungan menit'],
        ['collection', 'Pilih paket kelas', 'Sesuai program, kelas, dan level'],
        ['receipt', 'Konfirmasi pembayaran', 'Credit langsung masuk ke akunmu'],
        ['clipboard-check', 'Tes placement', 'Supaya kelasnya pas dengan levelmu'],
        ['calendar-check', 'Pilih jadwal kelas', 'Cocokkan dengan jam buka pengajar'],
        ['mortarboard', 'Mulai belajar', 'Bertemu pengajar dan teman sekelas'],
    ];
@endphp

<div class="why-yule">
    <div class="why-yule__head">
        <span class="eyebrow">Kenapa INA YULE</span>
        <h2 class="section-title">Mengapa harus belajar Mandarin di <em>INA YULE?</em></h2>
    </div>

    <ul class="why-yule__grid">
        @foreach ($reasons as [$icon, $title, $text])
            <li class="why-card">
                <span class="why-card__icon" aria-hidden="true"><i class="bi bi-{{ $icon }}"></i></span>
                <h3>{{ $title }}</h3>
                <p>{{ $text }}</p>
            </li>
        @endforeach
    </ul>
</div>

<div class="yule-journey">
    <div class="why-yule__head">
        <span class="eyebrow">Langkah demi langkah</span>
        <h2 class="section-title">Alur belajar Mandarin di <em>INA YULE</em></h2>
    </div>

    <ol class="yule-journey__steps">
        @foreach ($journey as $i => [$icon, $title, $text])
            <li class="yule-step">
                <span class="yule-step__icon" aria-hidden="true">
                    <i class="bi bi-{{ $icon }}"></i>
                    <b>{{ $i + 1 }}</b>
                </span>
                <strong>{{ $title }}</strong>
                <small>{{ $text }}</small>
            </li>
        @endforeach
    </ol>
</div>
