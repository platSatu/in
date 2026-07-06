@extends('layouts.frontend')

@section('content')
    <div class="col-lg-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-content widget-content-area">
                <h4 class="mb-4">History Mutasi User</h4>

                <ul class="nav nav-tabs mb-3" id="historyTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="deposit-tab" data-bs-toggle="tab" data-bs-target="#deposit-pane" type="button" role="tab" aria-controls="deposit-pane" aria-selected="true">
                            Mutasi Deposit
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="voucher-tab" data-bs-toggle="tab" data-bs-target="#voucher-pane" type="button" role="tab" aria-controls="voucher-pane" aria-selected="false">
                            Mutasi Voucher
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="historyTabContent">
                    <div class="tab-pane fade show active" id="deposit-pane" role="tabpanel" aria-labelledby="deposit-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Debit</th>
                                        <th>Kredit</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                        <th>Method</th>
                                        <th>Deskripsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($deposits as $item)
                                        <tr>
                                            <td>{{ optional($item->payment_date)->format('d-m-Y H:i') ?? '-' }}</td>
                                            <td>Rp {{ number_format((float) $item->debit, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format((float) $item->kredit, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format((float) $item->balance, 0, ',', '.') }}</td>
                                            <td>{{ ucfirst((string) $item->payment_status) }}</td>
                                            <td>{{ ucfirst((string) $item->payment_method) }}</td>
                                            <td>{{ $item->description ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">Belum ada mutasi deposit.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="voucher-pane" role="tabpanel" aria-labelledby="voucher-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kode Voucher</th>
                                        <th>Status</th>
                                        <th>Valid From</th>
                                        <th>Valid Until</th>
                                        <th>Dibuat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($vouchers as $voucher)
                                        <tr>
                                            <td>{{ $voucher->code_vouchers }}</td>
                                            <td>{{ ucfirst((string) $voucher->status) }}</td>
                                            <td>{{ optional($voucher->valid_from)->format('d-m-Y') ?? '-' }}</td>
                                            <td>{{ optional($voucher->valid_until)->format('d-m-Y') ?? '-' }}</td>
                                            <td>{{ optional($voucher->created_at)->format('d-m-Y H:i') ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Belum ada mutasi voucher.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
