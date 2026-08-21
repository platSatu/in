@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 text-end">
        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('student.student.edit', $data->id) }}" class="btn btn-outline-primary">Edit</a>
            <a href="{{ route('student.student.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="gdoc-shell">

        {{-- === OUTLINE (mirip panel outline di Google Docs) === --}}
        <div class="gdoc-outline d-none d-lg-block">
            <div class="gdoc-outline-title">Daftar Isi</div>
            <a href="#profil" class="gdoc-outline-link">Profil Student</a>

            @forelse ($submissions as $submission)
                <a href="#submission-{{ $submission->id }}" class="gdoc-outline-link">
                    <span class="gdoc-outline-link-name">{{ optional($submission->form)->name ?? 'Form (dihapus)' }}</span>
                    <span class="gdoc-outline-link-date">{{ optional($submission->created_at)->format('d M Y') }}</span>
                </a>
            @empty
                <span class="gdoc-outline-empty">Belum ada riwayat</span>
            @endforelse
        </div>

        {{-- === DOKUMEN === --}}
        <div class="gdoc-page" id="profil">

            {{-- Header profil --}}
            <div class="gdoc-profile">
                <div class="gdoc-avatar">
                    @if($data->images)
                        <img src="{{ asset($data->images) }}" alt="{{ $data->first_name }}">
                    @else
                        <span>{{ strtoupper(substr($data->first_name, 0, 1) . substr($data->last_name, 0, 1)) }}</span>
                    @endif
                </div>

                <div class="gdoc-profile-info">
                    <h1 class="gdoc-title">{{ $data->first_name }} {{ $data->last_name }}</h1>

                    <div class="gdoc-chip-row">
                        <span class="gdoc-chip">
                            <i class="bi bi-envelope"></i> {{ $data->email }}
                        </span>
                        <span class="gdoc-chip">
                            <i class="bi bi-whatsapp"></i> {{ $data->handphone }}
                        </span>
                        <span class="gdoc-chip {{ $data->status === 'active' ? 'gdoc-chip-success' : 'gdoc-chip-muted' }}">
                            {{ ucfirst($data->status) }}
                        </span>
                        @if($data->user_id)
                            <span class="gdoc-chip gdoc-chip-success">
                                <i class="bi bi-check-circle"></i> Akun Login Aktif
                            </span>
                        @else
                            <span class="gdoc-chip gdoc-chip-warning">Belum Ada Akun Login</span>
                        @endif
                    </div>

                    <div class="gdoc-meta-grid">
                        <div>
                            <span class="gdoc-meta-label">Branch Terakhir</span>
                            <span class="gdoc-meta-value">{{ optional($data->companyBranch)->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="gdoc-meta-label">Form Terakhir</span>
                            <span class="gdoc-meta-value">{{ optional($data->form)->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="gdoc-meta-label">Kode Sales</span>
                            <span class="gdoc-meta-value">{{ $data->sales_id ?: '-' }}</span>
                        </div>
                        <div>
                            <span class="gdoc-meta-label">Terdaftar Sejak</span>
                            <span class="gdoc-meta-value">{{ optional($data->created_at)->format('d M Y') ?? '-' }}</span>
                        </div>
                    </div>

                    @unless($data->user_id)
                        <form action="{{ route('student.student.add-user', $data->id) }}" method="POST"
                            onsubmit="return confirm('Buat akun login untuk {{ $data->first_name }}?');" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">+ Add User (buat akun login)</button>
                        </form>
                    @endunless
                </div>
            </div>

            <hr class="gdoc-divider">

            {{-- Riwayat pengisian quiz --}}
            <h2 class="gdoc-h1">Riwayat Pengisian Quiz</h2>
            <p class="gdoc-subtle">
                Total <strong>{{ $submissions->count() }}</strong> kali submit, diurutkan dari yang terbaru.
            </p>

            @forelse ($submissions as $submission)
                @php
                    $payment = $paymentsBySubmission->get($submission->id);
                    $form = $submission->form;
                    $answerGroups = $answersBySubmission
                        ->get($submission->id, collect())
                        ->groupBy('question_id')
                        ->sortBy(fn ($group) => optional(optional($group->first())->question)->order ?? 0);
                @endphp

                <section id="submission-{{ $submission->id }}" class="gdoc-submission">

                    <div class="gdoc-submission-head">
                        <div>
                            <h3 class="gdoc-h2">{{ $form->name ?? 'Form (sudah dihapus)' }}</h3>
                            <div class="gdoc-submission-meta">
                                <span><i class="bi bi-geo-alt"></i> {{ optional(optional($form)->companyBranch)->name ?? '-' }}</span>
                                <span><i class="bi bi-calendar3"></i> {{ optional($submission->created_at)->format('d M Y, H:i') }}</span>
                            </div>
                        </div>
                        <span class="gdoc-chip {{ $submission->status === 'active' ? 'gdoc-chip-success' : 'gdoc-chip-muted' }}">
                            {{ ucfirst($submission->status) }}
                        </span>
                    </div>

                    {{-- Status pembayaran --}}
                    @if(optional($form)->requires_payment)
                        <div class="gdoc-payment-box gdoc-payment-{{ $payment->status ?? 'none' }}">
                            <div class="gdoc-payment-icon">
                                @if(($payment->status ?? null) === 'paid')
                                    <i class="bi bi-check-circle-fill"></i>
                                @elseif(($payment->status ?? null) === 'failed' || ($payment->status ?? null) === 'expired')
                                    <i class="bi bi-x-circle-fill"></i>
                                @else
                                    <i class="bi bi-hourglass-split"></i>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="gdoc-payment-title">
                                    @if(!$payment)
                                        Belum ada transaksi pembayaran
                                    @elseif($payment->status === 'paid')
                                        Pembayaran Lunas
                                    @elseif($payment->status === 'pending')
                                        Menunggu Pembayaran
                                    @elseif($payment->status === 'failed')
                                        Pembayaran Gagal
                                    @else
                                        Pembayaran Kedaluwarsa
                                    @endif
                                </div>
                                @if($payment)
                                    <div class="gdoc-payment-detail">
                                        Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                        &middot; {{ ucfirst($payment->gateway) }}
                                        @if($payment->payment_method)
                                            &middot; {{ $payment->payment_method }}
                                        @endif
                                        @if($payment->paid_at)
                                            &middot; dibayar {{ $payment->paid_at->format('d M Y, H:i') }}
                                        @endif
                                    </div>
                                    <div class="gdoc-payment-order">Order ID: {{ $payment->order_id }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Jawaban — dibungkus collapse (default kebuka cuma buat submission
                         terbaru/pertama, sisanya tertutup) karena satu form bisa punya
                         puluhan pertanyaan, jadi kalau semua submission langsung
                         ke-expand sekaligus halamannya jadi sangat panjang ke bawah. --}}
                    <button type="button" class="btn btn-sm btn-outline-secondary gdoc-collapse-btn"
                        data-bs-toggle="collapse"
                        data-bs-target="#answers-{{ $submission->id }}"
                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                        aria-controls="answers-{{ $submission->id }}">
                        <i class="bi bi-chevron-down"></i>
                        {{ $answerGroups->count() }} Pertanyaan &amp; Jawaban
                    </button>

                    <div class="gdoc-answers collapse {{ $loop->first ? 'show' : '' }}" id="answers-{{ $submission->id }}">
                        @forelse ($answerGroups as $group)
                            @php
                                $question = optional($group->first())->question;
                            @endphp
                            @if($question)
                                <div class="gdoc-answer-row">
                                    <div class="gdoc-question">
                                        <div class="gdoc-question-text">
                                            {{ $loop->iteration }}. {{ $question->question_text ?: '(Pertanyaan berupa media)' }}
                                        </div>

                                        @if($question->description)
                                            <div class="gdoc-question-description">{{ $question->description }}</div>
                                        @endif

                                        @if($question->image)
                                            <img src="{{ asset($question->image) }}" alt="" class="gdoc-question-media-image">
                                        @endif

                                        @if($question->audio)
                                            <audio controls preload="none" src="{{ asset($question->audio) }}" class="gdoc-question-media-audio"></audio>
                                        @endif
                                    </div>

                                    <div class="gdoc-answer">
                                        <span class="gdoc-answer-label">Jawaban</span>

                                        @if(in_array($question->type, ['single_choice', 'dropdown']))
                                            @php $answer = $group->first(); @endphp
                                            <div class="gdoc-answer-value">
                                                {{ optional($answer->option)->option_text ?: (optional($answer->option)->image ? '[Gambar]' : '-') }}
                                            </div>
                                            @if(optional($answer->option)->image)
                                                <img src="{{ asset($answer->option->image) }}" alt="" class="gdoc-answer-image">
                                            @endif
                                        @elseif($question->type === 'multiple_choice')
                                            <div class="gdoc-answer-value">
                                                {{ $group->map(fn ($a) => optional($a->option)->option_text ?: '[Gambar]')->implode(', ') ?: '-' }}
                                            </div>
                                            <div class="gdoc-answer-image-row">
                                                @foreach ($group as $a)
                                                    @if(optional($a->option)->image)
                                                        <img src="{{ asset($a->option->image) }}" alt="" class="gdoc-answer-image">
                                                    @endif
                                                @endforeach
                                            </div>
                                        @elseif($question->type === 'major')
                                            @php $answer = $group->first(); @endphp
                                            <div class="gdoc-answer-value">
                                                {{ $majorNames[$answer->answer_text] ?? '-' }}
                                            </div>
                                        @else
                                            @php $answer = $group->first(); @endphp
                                            <div class="gdoc-answer-value">{{ $answer->answer_text ?: '-' }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @empty
                            <p class="gdoc-subtle">Tidak ada jawaban yang tercatat untuk submission ini.</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <p class="gdoc-subtle">Student ini belum pernah mengisi form apapun.</p>
            @endforelse

        </div>
    </div>

</div>

<style>
    .gdoc-shell {
        display: flex;
        align-items: flex-start;
        gap: 24px;
    }

    .gdoc-outline {
        position: sticky;
        top: 16px;
        width: 240px;
        flex-shrink: 0;
        background: #fff;
        border: 1px solid #e3e6ea;
        border-radius: 8px;
        padding: 16px;
        max-height: calc(100vh - 32px);
        overflow-y: auto;
    }

    .gdoc-outline-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #8a8f98;
        margin-bottom: 10px;
    }

    .gdoc-outline-link {
        display: flex;
        flex-direction: column;
        padding: 6px 8px;
        border-radius: 6px;
        color: #3c4043;
        text-decoration: none;
        font-size: 13px;
        margin-bottom: 2px;
    }

    .gdoc-outline-link:hover {
        background: #f1f3f4;
        color: #1a73e8;
    }

    .gdoc-outline-link-date {
        font-size: 11px;
        color: #9aa0a6;
    }

    .gdoc-outline-empty {
        font-size: 13px;
        color: #9aa0a6;
    }

    .gdoc-page {
        flex-grow: 1;
        min-width: 0;
        background: #fff;
        border: 1px solid #e3e6ea;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(60, 64, 67, .12);
        padding: 40px 48px;
    }

    .gdoc-profile {
        display: flex;
        gap: 24px;
        align-items: flex-start;
    }

    .gdoc-avatar {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        overflow: hidden;
        background: #e8f0fe;
        color: #1a73e8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .gdoc-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .gdoc-title {
        font-size: 26px;
        font-weight: 600;
        color: #202124;
        margin: 0 0 10px;
    }

    .gdoc-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .gdoc-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f3f4;
        color: #3c4043;
        font-size: 12.5px;
        padding: 4px 10px;
        border-radius: 999px;
    }

    .gdoc-chip-success { background: #e6f4ea; color: #137333; }
    .gdoc-chip-warning { background: #fef7e0; color: #b06000; }
    .gdoc-chip-muted { background: #f1f3f4; color: #5f6368; }

    .gdoc-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px 24px;
    }

    .gdoc-meta-label {
        display: block;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #9aa0a6;
    }

    .gdoc-meta-value {
        display: block;
        font-size: 14px;
        color: #202124;
        font-weight: 500;
    }

    .gdoc-divider {
        border: none;
        border-top: 1px solid #e3e6ea;
        margin: 32px 0;
    }

    .gdoc-h1 {
        font-size: 20px;
        font-weight: 600;
        color: #202124;
        margin-bottom: 4px;
    }

    .gdoc-h2 {
        font-size: 17px;
        font-weight: 600;
        color: #202124;
        margin: 0 0 4px;
    }

    .gdoc-subtle {
        color: #5f6368;
        font-size: 13.5px;
    }

    .gdoc-submission {
        margin-top: 20px;
        padding: 20px 24px 24px;
        border: 1px solid #e3e6ea;
        border-radius: 10px;
        background: #fbfbfc;
        box-shadow: 0 1px 3px rgba(60, 64, 67, .08);
        scroll-margin-top: 16px;
    }

    .gdoc-collapse-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 14px;
    }

    .gdoc-collapse-btn .bi-chevron-down {
        transition: transform .2s ease;
    }

    .gdoc-collapse-btn[aria-expanded="true"] .bi-chevron-down {
        transform: rotate(180deg);
    }

    .gdoc-submission-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }

    .gdoc-submission-meta {
        display: flex;
        gap: 14px;
        font-size: 12.5px;
        color: #5f6368;
        margin-top: 4px;
    }

    .gdoc-payment-box {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        background: #f8f9fa;
        border-left: 4px solid #9aa0a6;
        border-radius: 6px;
        padding: 12px 16px;
        margin-bottom: 18px;
    }

    .gdoc-payment-box.gdoc-payment-paid { border-left-color: #34a853; background: #e6f4ea; }
    .gdoc-payment-box.gdoc-payment-pending { border-left-color: #f9ab00; background: #fef7e0; }
    .gdoc-payment-box.gdoc-payment-failed,
    .gdoc-payment-box.gdoc-payment-expired { border-left-color: #ea4335; background: #fce8e6; }

    .gdoc-payment-icon { font-size: 20px; color: inherit; margin-top: 1px; }
    .gdoc-payment-paid .gdoc-payment-icon { color: #188038; }
    .gdoc-payment-pending .gdoc-payment-icon { color: #b06000; }
    .gdoc-payment-failed .gdoc-payment-icon,
    .gdoc-payment-expired .gdoc-payment-icon { color: #c5221f; }

    .gdoc-payment-title { font-weight: 600; font-size: 14px; color: #202124; }
    .gdoc-payment-detail { font-size: 12.5px; color: #3c4043; margin-top: 2px; }
    .gdoc-payment-order { font-size: 11.5px; color: #9aa0a6; margin-top: 2px; }

    .gdoc-answers {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .gdoc-answer-row {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 20px;
        background: #fbfbfc;
        border: 1px solid #edeef0;
        border-radius: 8px;
        padding: 14px 16px;
    }

    .gdoc-question-text {
        font-size: 13.5px;
        font-weight: 600;
        color: #202124;
    }

    .gdoc-question-description {
        font-size: 12.5px;
        color: #5f6368;
        font-style: italic;
        margin-top: 4px;
    }

    .gdoc-question-media-image {
        max-width: 100%;
        max-height: 140px;
        border-radius: 6px;
        margin-top: 8px;
        display: block;
    }

    .gdoc-question-media-audio {
        margin-top: 8px;
        width: 100%;
        max-width: 260px;
    }

    .gdoc-answer-label {
        display: block;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #9aa0a6;
        margin-bottom: 4px;
    }

    .gdoc-answer-value {
        font-size: 13.5px;
        color: #202124;
        font-weight: 500;
    }

    .gdoc-answer-image,
    .gdoc-answer-image-row img {
        max-width: 96px;
        max-height: 96px;
        object-fit: cover;
        border-radius: 6px;
        margin-top: 6px;
        margin-right: 6px;
    }

    @media (max-width: 767px) {
        .gdoc-answer-row {
            grid-template-columns: 1fr;
        }

        .gdoc-page {
            padding: 24px 20px;
        }

        .gdoc-profile {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .gdoc-chip-row,
        .gdoc-meta-grid {
            justify-content: center;
        }
    }
</style>

@endsection
