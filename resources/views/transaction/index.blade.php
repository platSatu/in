@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Transaction</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('transaction.create') }}" class="btn btn-primary">+ Add Transaction</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('transaction.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" placeholder="Search transaction..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Balance Before</th>
                                <th>Balance After</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th>Transaction Date</th>
                                <th>Description</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $transaction)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td>{{ $transaction->user->name ?? $transaction->user_id }}</td>
                                    <td>{{ ucfirst($transaction->type) }}</td>
                                    <td>Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $transaction->balance_before, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $transaction->balance_after, 0, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match ($transaction->status) {
                                                'success' => 'badge badge-success',
                                                'failed' => 'badge badge-danger',
                                                'cancelled' => 'badge badge-warning',
                                                default => 'badge badge-secondary',
                                            };
                                        @endphp
                                        <span class="{{ $badgeClass }}">{{ ucfirst($transaction->status) }}</span>
                                    </td>
                                    <td>
                                        {{ $transaction->reference_type ?? '-' }}
                                        @if($transaction->reference_id)
                                            ({{ $transaction->reference_id }})
                                        @endif
                                    </td>
                                    <td>{{ optional($transaction->transaction_date)->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($transaction->description, 40) }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a class="dropdown-toggle" href="#" role="button"
                                               id="dropdownMenuLink{{ $transaction->id }}"
                                               data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                     class="feather feather-more-horizontal">
                                                    <circle cx="12" cy="12" r="1"></circle>
                                                    <circle cx="19" cy="12" r="1"></circle>
                                                    <circle cx="5" cy="12" r="1"></circle>
                                                </svg>
                                            </a>

                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink{{ $transaction->id }}">
                                                <a class="dropdown-item" href="{{ route('transaction.edit', $transaction->id) }}">Edit</a>

                                                <form action="{{ route('transaction.destroy', $transaction->id) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Hapus data transaction ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">Belum ada data transaction.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $data->links() }}
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
