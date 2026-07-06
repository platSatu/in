@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Vouchers</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('vouchers.create') }}" class="btn btn-primary">+ Add Voucher</a>
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
                    <form method="GET" action="{{ route('vouchers.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" placeholder="Search voucher..."
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
                                <th>Kode Voucher</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Valid From</th>
                                <th>Valid Until</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $voucher)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td>{{ $voucher->code_vouchers }}</td>
                                    <td>{{ $voucher->category->name ?? '-' }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match ($voucher->status) {
                                                'active' => 'badge badge-success',
                                                'expired' => 'badge badge-danger',
                                                default => 'badge badge-warning',
                                            };
                                        @endphp
                                        <span class="{{ $badgeClass }}">{{ ucfirst($voucher->status) }}</span>
                                    </td>
                                    <td>{{ optional($voucher->valid_from)->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ optional($voucher->valid_until)->format('Y-m-d') ?? '-' }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a class="dropdown-toggle" href="#" role="button"
                                               id="dropdownMenuLink{{ $voucher->id }}"
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

                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink{{ $voucher->id }}">
                                                <a class="dropdown-item" href="{{ route('vouchers.edit', $voucher->id) }}">Edit</a>

                                                <form action="{{ route('vouchers.destroy', $voucher->id) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Hapus data voucher ini?');">
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
                                    <td colspan="7" class="text-center">Belum ada data voucher.</td>
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
