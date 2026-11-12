{{-- FAQ beranda: kartu terpisah, satu jawaban terbuka dalam satu waktu (<details name> bawaan browser, tanpa JS). --}}
@php
    $faqs = [
        ['Apakah saya harus bisa bahasa Mandarin dulu sebelum kuliah di China?',
            'Tidak harus. Banyak kampus membuka program berbahasa Inggris. Untuk program berbahasa Mandarin biasanya diminta sertifikat HSK, dan kalau kamu belum bisa sama sekali, kamu bisa mulai belajar pelan-pelan bersama kelas InaYule kami.'],
        ['Kira-kira berapa biaya kuliah di China?',
            'Biayanya berbeda-beda, tergantung kampus, kota, dan jurusan yang kamu pilih. Ceritakan saja rencana dan kemampuanmu, nanti kami bantu hitungkan perkiraan biaya kuliah dan biaya hidupnya supaya tidak ada kejutan.'],
        ['Apakah ada peluang beasiswa?',
            'Ada. Beasiswa bisa datang dari pemerintah China, pemerintah provinsi, maupun dari kampusnya sendiri. Kami bantu mencarikan yang paling cocok dengan profilmu dan menyiapkan berkasnya, walau keputusan akhirnya tetap ada di pihak pemberi beasiswa.'],
        ['Dokumen apa saja yang perlu saya siapkan?',
            'Umumnya paspor, ijazah dan transkrip nilai, surat rekomendasi, sertifikat bahasa, dan hasil pemeriksaan kesehatan. Daftar pastinya tergantung kampus tujuan, jadi tenang saja, kami cek dan lengkapi satu per satu bersamamu.'],
        ['Kapan sebaiknya saya mulai mendaftar?',
            'Sebagian besar kampus memulai perkuliahan di bulan September, dan pendaftarannya sudah dibuka beberapa bulan sebelumnya. Idealnya mulai persiapan 6 sampai 12 bulan sebelumnya, supaya ada waktu untuk memilih kampus, mengejar beasiswa, dan mengurus visa.'],
        ['Saya tinggal di luar Jakarta, apakah tetap bisa dibantu?',
            'Tentu bisa. Kami mendampingi pelajar dari seluruh Indonesia. Konsultasi bisa lewat WhatsApp atau online, dan pendampingan kami tetap berjalan sampai kamu tiba dan mulai kuliah di China.'],
    ];
@endphp
<section class="faq" id="faq">
    <div class="container">
        <div class="faq__head">
            <h2 class="faq__title">FAQs</h2>
            <span class="faq__line" aria-hidden="true"></span>
            <p class="faq__desc">Jawaban singkat untuk hal yang paling sering ditanyakan. Belum ketemu? Chat kami lewat WhatsApp di pojok kanan bawah, kami siap bantu.</p>
        </div>

        <div class="faq__list">
            @foreach ($faqs as [$question, $answer])
                <details class="faq__item" name="faq" @if ($loop->first) open @endif>
                    <summary>
                        <span class="faq__no">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <span class="faq__q">{{ $question }}</span>
                        <span class="faq__icon" aria-hidden="true"></span>
                    </summary>
                    <p class="faq__a">{{ $answer }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
