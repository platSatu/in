<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My University List | INASTUDY</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #C8102E; --brand-dark: #a30d25; }
        * { box-sizing: border-box; }
        body { background: #f5f7fb; font-family: 'Poppins', 'Segoe UI', Tahoma, sans-serif; color: #2b2f38; }
        .topbar { background: #fff; border-bottom: 1px solid #eef1f8; padding: 16px 0; }
        .topbar a { color: #6b7186; text-decoration: none; font-weight: 600; font-size: 14px; }
        .topbar a:hover { color: var(--brand); }
        .page-wrap { max-width: 980px; margin: 0 auto; padding: 32px 16px 60px; }
        .card-box { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 6px 20px rgba(20,30,60,.05); }
        h1 { font-size: 1.45rem; font-weight: 800; margin-bottom: 4px; }
        .subtitle { color: #6b7186; font-size: 14.5px; }
        .slots { display: flex; justify-content: flex-end; gap: 6px; }
        .slots span { width: 34px; height: 6px; border-radius: 99px; background: #e9ebf2; }
        .slots span.is-on { background: var(--brand); }
        .uni-item { display: flex; gap: 14px; align-items: center; border: 1px solid #eef1f8; border-radius: 14px; padding: 14px 16px; margin-top: 12px; }
        .uni-logo { flex-shrink: 0; width: 52px; height: 52px; border-radius: 12px; background: #fbe6ea; color: var(--brand); display: grid; place-items: center; font-weight: 800; overflow: hidden; }
        .uni-logo img { width: 100%; height: 100%; object-fit: contain; background: #fff; }
        .uni-info { flex: 1; min-width: 0; }
        .uni-name { font-weight: 700; font-size: 15px; }
        .uni-meta { color: #6b7186; font-size: 13px; }
        .uni-price { text-align: right; white-space: nowrap; }
        .uni-price small { display: block; color: #8a90a2; font-size: 12px; }
        .uni-price s { color: #a0a6b8; font-size: 12px; }
        .btn-remove { border: 0; background: none; color: #a0a6b8; padding: 6px; border-radius: 8px; }
        .btn-remove:hover { color: var(--brand); background: #fbe6ea; }
        .summary { position: sticky; top: 20px; }
        .summary .row-line { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; }
        .summary .total { font-size: 1.35rem; font-weight: 800; color: var(--brand-dark); }
        .note { background: #fff7f8; color: #5b2a33; border-radius: 12px; padding: 10px 12px; font-size: 12.5px; line-height: 1.55; }
        .btn-brand { background: var(--brand); color: #fff; border: 0; border-radius: 12px; padding: 13px 18px; font-weight: 700; width: 100%; }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }
        .btn-soft { background: #fff; color: #2b2f38; border: 1px solid #dfe3ee; border-radius: 12px; padding: 11px 16px; font-weight: 600; width: 100%; text-decoration: none; display: inline-block; text-align: center; }
        .btn-soft:hover { border-color: var(--brand); color: var(--brand); }
        .btn-soft.disabled { pointer-events: none; opacity: .5; }
        .empty { text-align: center; padding: 40px 10px; color: #6b7186; }
        .empty i { font-size: 44px; color: #e3b5bd; }
        .added { border-radius: 14px; border: 0; background: #e8f8ee; color: #135c33; }
        @media (max-width: 575.98px) {
            .card-box { padding: 20px 16px; }
            .uni-item { display: grid; grid-template-columns: 44px 1fr auto; gap: 4px 12px; align-items: start; }
            .uni-logo { width: 44px; height: 44px; }
            .uni-price { grid-row: 2; grid-column: 2 / 4; text-align: left; display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 8px; }
            .uni-item form { grid-row: 1; grid-column: 3; }
        }
    </style>
</head>

<body>
    <div class="topbar">
        <div class="container d-flex justify-content-between">
            <a href="{{ route('frontend.university.catalog') }}"><i class="bi bi-arrow-left"></i> Back to universities</a>
            <a href="{{ route('dashboard') }}">My dashboard</a>
        </div>
    </div>

    <div class="page-wrap">
        @if(session('cart_added'))
            {{-- Setelah menambah kampus: tawarkan pilih kampus lagi atau lanjut checkout. --}}
            <div class="alert added d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span><i class="bi bi-check-circle-fill"></i> <strong>{{ session('cart_added') }}</strong> is on your list.
                    @if($items->count() < $max) Would you like to add another university? @endif</span>
                @if($items->count() < $max)
                    <a href="{{ route('frontend.university.catalog') }}" class="btn btn-sm btn-success">Yes, choose another</a>
                @endif
            </div>
        @endif
        @foreach(['cart_status' => 'info', 'cart_error' => 'warning'] as $flash => $type)
            @if(session($flash))
                <div class="alert alert-{{ $type }}" style="border-radius:14px;">{{ session($flash) }}</div>
            @endif
        @endforeach

        <div class="row g-4">
            <div class="{{ $items->isEmpty() ? 'col-lg-8 mx-auto' : 'col-lg-7' }}">
                <div class="card-box">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h1>My University List</h1>
                            <p class="subtitle mb-0">Choose up to {{ $max }} universities and pay for all of them at once.</p>
                        </div>
                        <div class="text-end">
                            <div class="slots" aria-hidden="true">
                                @for($i = 0; $i < $max; $i++)<span @class(['is-on' => $i < $items->count()])></span>@endfor
                            </div>
                            <small class="text-muted">{{ $items->count() }} / {{ $max }}</small>
                        </div>
                    </div>

                    @forelse($items as $item)
                        @php
                            $course = $item['course'];
                            $university = $course->profile->university;
                            $fee = (int) $course->registration_fee_amount;
                        @endphp
                        <div class="uni-item">
                            <div class="uni-logo">
                                @if($university->logo && file_exists(public_path($university->logo)))
                                    <img src="{{ asset($university->logo) }}" alt="">
                                @else
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($university->name, 0, 1)) }}
                                @endif
                            </div>
                            <div class="uni-info">
                                <div class="uni-name">{{ $university->name }}</div>
                                <div class="uni-meta">
                                    {{ $course->course_name ?: ($course->profile->field ?: 'Program') }}{{ $course->degree ? ' ('.$course->degree.')' : '' }}
                                    &middot; Intake {{ $course->intake ? $course->intake.' ' : '' }}{{ $item['intake_year'] }}
                                </div>
                            </div>
                            <div class="uni-price">
                                @if($fee > 0)
                                    @if($isDeposit)
                                        <s>Rp {{ number_format($fee, 0, ',', '.') }}</s>
                                        <strong>Rp {{ number_format(ceil($fee / 2), 0, ',', '.') }}</strong>
                                        <small>DP 50%</small>
                                    @else
                                        <strong>Rp {{ number_format($fee, 0, ',', '.') }}</strong>
                                        <small>Registration Fee</small>
                                    @endif
                                @else
                                    <small>Fee will be confirmed by our team</small>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('student-portal.apply.cart.remove', $item['key']) }}" class="m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-remove" title="Remove" aria-label="Remove {{ $university->name }}"><i class="bi bi-trash3"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="empty">
                            <i class="bi bi-bag"></i>
                            <p class="mt-2 mb-3">Your list is still empty. Let's find a university that fits you.</p>
                            <a href="{{ route('frontend.university.catalog') }}" class="btn-brand d-inline-block text-decoration-none" style="width:auto;">Browse universities</a>
                        </div>
                    @endforelse
                </div>
            </div>

            @if($items->isNotEmpty())
                @php
                    $total = $items->sum(fn ($item) => $isDeposit ? ceil((int) $item['course']->registration_fee_amount / 2) : (int) $item['course']->registration_fee_amount);
                @endphp
                <div class="col-lg-5">
                    <div class="card-box summary">
                        <h6 class="fw-bold mb-3">Summary</h6>
                        <div class="row-line"><span>Universities</span><strong>{{ $items->count() }}</strong></div>
                        <div class="row-line align-items-center"><span>Pay now</span><span class="total">Rp {{ number_format($total, 0, ',', '.') }}</span></div>

                        @if($isDeposit)
                            <div class="note mb-3"><i class="bi bi-info-circle"></i> Since you are applying to more than one university, you only pay a 50% deposit for each one now. Our team will send you the rest later.</div>
                        @endif

                        <form method="POST" action="{{ route('student-portal.apply.cart.checkout') }}" onsubmit="this.querySelector('button').disabled = true;">
                            @csrf
                            <button type="submit" class="btn-brand"><i class="bi bi-lock-fill me-1"></i> Checkout &amp; Pay</button>
                        </form>

                        <div class="d-grid gap-2 mt-2">
                            <a href="{{ route('frontend.university.catalog') }}" @class(['btn-soft', 'disabled' => $items->count() >= $max])>
                                <i class="bi bi-plus-lg"></i> {{ $items->count() >= $max ? 'Your list is full' : 'Choose another university' }}
                            </a>
                            <form method="POST" action="{{ route('student-portal.apply.cart.clear') }}" class="m-0" onsubmit="return confirm('Remove all universities from your list?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link text-muted w-100 text-decoration-none" style="font-size:13.5px;">Cancel &amp; empty my list</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>

</html>
