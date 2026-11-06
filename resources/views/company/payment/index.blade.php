@extends('layouts.frontend')
@section('content')

@php
    $rp = fn ($v) => 'Rp ' . number_format((int) $v, 0, ',', '.');
    $date = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->timezone(config('app.timezone')) : null;
    $statusBadge = [
        'paid' => ['success', 'Lunas'], 'pending' => ['warning', 'Menunggu'], 'failed' => ['danger', 'Gagal'], 'expired' => ['secondary', 'Kedaluwarsa'],
    ];
    $payoutBadge = [
        'pending' => ['warning', 'Menunggu'], 'processing' => ['info', 'Diproses'], 'success' => ['success', 'Berhasil'],
        'failed' => ['danger', 'Gagal'], 'needs_review' => ['warning', 'Sedang dicek'],
    ];
@endphp

<div class="middle-content container-xxl p-0 pt-3">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'history' ? 'active' : '' }}" href="{{ route('company.payment.index') }}">Riwayat Transaksi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'payout' ? 'active' : '' }}" href="{{ route('company.payment.index', ['tab' => 'payout']) }}">Rekening &amp; Penarikan</a>
        </li>
    </ul>

    @if ($tab === 'history')
        {{-- ================= TAB 1: riwayat transaksi lewat BB ================= --}}
        <div class="widget-content widget-content-area br-8 p-4">
            <form method="GET" action="{{ route('company.payment.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-12 col-lg-3">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="text" name="search" class="form-control" maxlength="100" value="{{ request('search') }}" placeholder="Order ID / nama / email">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small mb-1">Modul</label>
                    <select name="module" class="form-select">
                        <option value="">Semua</option>
                        <option value="quiz" @selected(request('module') === 'quiz')>Quiz</option>
                        <option value="apply" @selected(request('module') === 'apply')>Apply</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($statusBadge as $key => [, $label])
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small mb-1">Dari</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small mb-1">Sampai</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-lg-1">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted mb-2">
                <span>
                    Lunas <strong class="text-dark">{{ number_format((int) $paid->total, 0, ',', '.') }}</strong> transaksi ·
                    total <strong class="text-dark">{{ $rp($paid->amount) }}</strong>
                </span>
                @if (request()->hasAny(['search', 'module', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('company.payment.index') }}">Hapus filter</a>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Order ID</th>
                            <th>Pembayar</th>
                            <th>Modul</th>
                            <th class="text-end">Nominal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="text-nowrap">{{ $date($row->created_at)?->format('d/m/Y') }}<div class="small text-muted">{{ $date($row->created_at)?->format('H:i') }}</div></td>
                                <td class="text-nowrap fw-bold">{{ $row->order_id }}<div class="small text-muted fw-normal">{{ $row->payment_method ?: '' }}</div></td>
                                <td>{{ $row->payer ?: '-' }}<div class="small text-muted">{{ $row->email }}</div></td>
                                <td>{{ $row->module === 'quiz' ? 'Quiz' : 'Apply' }}<div class="small text-muted">{{ $row->item ?: '-' }}</div></td>
                                <td class="text-end text-nowrap">{{ $rp($row->amount) }}</td>
                                <td>
                                    @php([$tone, $label] = $statusBadge[$row->status] ?? ['secondary', ucfirst((string) $row->status)])
                                    <span class="badge badge-{{ $tone }}">{{ $label }}</span>
                                    @if ($row->paid_at)
                                        <div class="small text-muted text-nowrap mt-1">{{ $date($row->paid_at)->format('d/m/Y H:i') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi lewat BB.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-end">{{ $rows->links('pagination::bootstrap-5') }}</div>
        </div>
    @elseif (isset($setupError))
        <div class="alert alert-warning">{{ $setupError }}</div>
    @else
        {{-- ================= TAB 2 (atas): saldo, rekening, penarikan ================= --}}
        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="widget-content widget-content-area br-8 p-4 h-100">
                    <h6 class="mb-3">Saldo di BB</h6>
                    @if ($balanceError)
                        <div class="text-danger small">{{ $balanceError }}</div>
                    @else
                        <div class="small text-muted">Bisa ditarik</div>
                        <div class="fs-4 fw-bold text-success mb-2">{{ $rp($balance['available_balance'] ?? 0) }}</div>
                        <div class="d-flex justify-content-between small"><span class="text-muted">Total saldo</span><span>{{ $rp($balance['balance'] ?? 0) }}</span></div>
                        <div class="d-flex justify-content-between small"><span class="text-muted">Menunggu pencairan</span><span>{{ $rp($balance['pending_balance'] ?? 0) }}</span></div>
                    @endif
                    @if ($rules)
                        <hr>
                        <div class="small text-muted">
                            Biaya per penarikan {{ $rp($rules['fee']) }} · minimal {{ $rp($rules['min_amount']) }}
                            @if ($rules['daily_limit']) · batas harian {{ $rp($rules['daily_limit']) }} @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="widget-content widget-content-area br-8 p-4 h-100">
                    <h6 class="mb-3">Daftarkan Rekening</h6>
                    @if ($accountsError)
                        <div class="small text-muted mb-2">Form rekening belum bisa dibuka. Pastikan:</div>
                        <ol class="small text-muted ps-3 mb-2">
                            <li>Penarikan lewat API sudah diaktifkan di akun BB (Invoice › Disbursement › Pengaturan &amp; API).</li>
                            <li>Server BB sudah versi terbaru.</li>
                        </ol>
                        <div class="small text-danger">{{ $accountsError }}</div>
                    @else
                        @error('account') <div class="alert alert-danger small">{{ $message }}</div> @enderror
                        <form method="POST" action="{{ route('company.payment.accounts.store') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small mb-1">Bank</label>
                                <select name="bank_code" class="form-select" required>
                                    <option value="">Pilih bank</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank['bank_code'] }}" @selected(old('bank_code') === $bank['bank_code'])>{{ $bank['bank_name'] }}</option>
                                    @endforeach
                                </select>
                                @error('bank_code') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Nomor rekening</label>
                                <input type="text" name="account_number" class="form-control" inputmode="numeric" maxlength="30" required value="{{ old('account_number') }}">
                                @error('account_number') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label small mb-1">Label <span class="text-muted">(opsional)</span></label>
                                <input type="text" name="label" class="form-control" maxlength="100" value="{{ old('label') }}" placeholder="mis. Rekening operasional">
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100">Cek &amp; Daftarkan</button>
                            <div class="small text-muted mt-2">Nama pemilik dicek langsung ke bank. Rekening yang sama didaftarkan ulang hanya diperbarui.</div>
                        </form>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="widget-content widget-content-area br-8 p-4 h-100">
                    <h6 class="mb-3">Tarik Dana</h6>
                    @if ($accountsError)
                        <div class="small text-muted">Bisa dipakai setelah rekening terdaftar.</div>
                    @elseif ($accounts->where('status', 'active')->isEmpty())
                        <div class="small text-muted">Daftarkan rekening dulu.</div>
                    @else
                        @error('payout') <div class="alert alert-danger small">{{ $message }}</div> @enderror
                        <form method="POST" action="{{ route('company.payment.payouts.store') }}" id="payout-form"
                            data-fee="{{ (int) ($rules['fee'] ?? 0) }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ old('token', (string) \Illuminate\Support\Str::uuid()) }}">
                            <div class="mb-2">
                                <label class="form-label small mb-1">Ke rekening</label>
                                <select name="payout_account_id" class="form-select" required>
                                    @foreach ($accounts->where('status', 'active') as $account)
                                        <option value="{{ $account['id'] }}" @selected(old('payout_account_id') === $account['id'])>
                                            {{ $account['bank_name'] }} ****{{ $account['account_last4'] }} · {{ $account['account_name'] }}{{ $account['label'] ? ' ('.$account['label'].')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Jumlah (Rp)</label>
                                <input type="number" name="amount" class="form-control" required min="{{ (int) ($rules['min_amount'] ?? 1) }}"
                                    max="{{ (int) ($balance['available_balance'] ?? ($rules['max_amount'] ?? 1000000000)) }}" step="1" value="{{ old('amount') }}">
                                <div class="small text-muted mt-1" data-net>Diterima: -</div>
                                @error('amount') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label small mb-1">Password akun Anda</label>
                                <input type="password" name="password" class="form-control" required autocomplete="current-password">
                                @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Tarik Dana</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- ================= TAB 2 (bawah): mutasi ================= --}}
        <div class="widget-content widget-content-area br-8 p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                <h6 class="mb-0">Mutasi</h6>
                <form method="GET" action="{{ route('company.payment.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
                    <input type="hidden" name="tab" value="payout">
                    <select name="direction" class="form-select form-select-sm" style="min-width:170px">
                        <option value="">Masuk &amp; keluar</option>
                        <option value="credit" @selected(request('direction') === 'credit')>Masuk</option>
                        <option value="debit" @selected(request('direction') === 'debit')>Keluar</option>
                    </select>
                    <input type="date" name="date_from" class="form-control form-control-sm" style="width:auto" value="{{ request('date_from') }}">
                    <input type="date" name="date_to" class="form-control form-control-sm" style="width:auto" value="{{ request('date_to') }}">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    @if (request()->hasAny(['direction', 'date_from', 'date_to']))
                        <a href="{{ route('company.payment.index', ['tab' => 'payout']) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </form>
            </div>

            @if ($mutationsError)
                <div class="small text-danger mb-2">Mutasi belum bisa dimuat: {{ $mutationsError }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Keterangan</th>
                            <th class="text-end">Masuk</th>
                            <th class="text-end">Keluar</th>
                            <th class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mutations as $row)
                            @php($credit = ($row['direction'] ?? '') === 'credit')
                            <tr>
                                <td class="text-nowrap">{{ $date($row['created_at'] ?? null)?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="fw-bold">{{ $row['type_label'] ?? '-' }}</span>
                                    @if (! empty($row['payout']))
                                        @php([$tone, $label] = $payoutBadge[$row['payout']['status']] ?? ['secondary', $row['payout']['status']])
                                        <span class="badge badge-{{ $tone }} ms-1">{{ $label }}</span>
                                    @endif
                                    @if (! empty($row['pending_until']))
                                        <span class="badge badge-warning ms-1">Cair {{ $date($row['pending_until'])->format('d/m H:i') }}</span>
                                    @endif
                                    <div class="small text-muted">
                                        {{ $row['description'] ?? '' }}
                                        @if (! empty($row['invoice']['external_id'])) · Order {{ $row['invoice']['external_id'] }} @endif
                                    </div>
                                </td>
                                <td class="text-end text-nowrap text-success">{{ $credit ? $rp($row['amount']) : '' }}</td>
                                <td class="text-end text-nowrap text-danger">{{ $credit ? '' : $rp($row['amount']) }}</td>
                                <td class="text-end text-nowrap">{{ $rp($row['balance_after'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada mutasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-end">{{ $mutations->links('pagination::bootstrap-5') }}</div>
        </div>

        <script>
            (function () {
                var form = document.getElementById('payout-form');
                if (!form) return;
                var fee = parseInt(form.dataset.fee, 10) || 0;
                var amount = form.querySelector('[name=amount]');
                var net = form.querySelector('[data-net]');
                var rupiah = function (v) { return 'Rp ' + Math.max(0, v).toLocaleString('id-ID'); };
                var update = function () {
                    var value = parseInt(amount.value, 10) || 0;
                    net.textContent = value ? 'Diterima ' + rupiah(value - fee) + ' (biaya ' + rupiah(fee) + ')' : 'Diterima: -';
                };
                amount.addEventListener('input', update);
                update();
                form.addEventListener('submit', function (e) {
                    var account = form.querySelector('[name=payout_account_id]');
                    var text = account.options[account.selectedIndex].text.trim();
                    if (!confirm('Tarik ' + rupiah(parseInt(amount.value, 10) || 0) + ' ke ' + text + '?\nDiterima ' + rupiah((parseInt(amount.value, 10) || 0) - fee) + '.')) {
                        e.preventDefault();
                        return;
                    }
                    form.querySelector('button[type=submit]').disabled = true;
                });
            })();
        </script>
    @endif

</div>

@endsection
