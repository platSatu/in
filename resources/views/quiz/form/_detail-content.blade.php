{{--
    Fragmen HTML modal "Detail" di halaman index Quiz Form (quiz/form/index.blade.php).
    Diisi lewat fetch() ke route('quiz.form.detail', $form->id) begitu tombol
    "Detail" per baris diklik — lihat script di bagian bawah index.blade.php.

    PENTING: file ini SENGAJA tidak @extends layout apa pun (bukan halaman penuh,
    cuma potongan HTML buat disuntik ke #formDetailModalBody), dan murni
    read-only — tidak ada <form>/tombol aksi di sini sama sekali, sesuai
    permintaan: "tidak ada fungsi apa apa hanya menampilkan".
--}}
<div class="quiz-detail-content">
    <p class="text-muted mb-3">
        Total <strong>{{ $rootQuestions->count() }}</strong> pertanyaan utama
        @if ($rootQuestions->isNotEmpty())
            (pertanyaan bercabang/anak ikut ditampilkan menjorok di bawah opsi pemicunya).
        @endif
    </p>

    @if ($rootQuestions->isEmpty())
        <p class="text-muted mb-0">Form ini belum punya pertanyaan.</p>
    @else
        @include('quiz.form._detail-questions', ['questions' => $rootQuestions, 'depth' => 0])
    @endif
</div>
