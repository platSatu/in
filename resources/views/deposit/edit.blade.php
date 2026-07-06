@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('deposit.index') }}">Deposit</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('deposit.update', $data->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">

                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="debit" class="mb-2">Debit</label>
                            <input type="number" min="0" step="0.01"
                                   class="form-control @error('debit') is-invalid @enderror"
                                   id="debit" name="debit"
                                   value="{{ old('debit', $data->debit) }}">
                            @error('debit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="kredit" class="mb-2">Kredit</label>
                            <input type="number" min="0" step="0.01"
                                   class="form-control @error('kredit') is-invalid @enderror"
                                   id="kredit" name="kredit"
                                   value="{{ old('kredit', $data->kredit) }}">
                            @error('kredit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="payment_method" class="mb-2">Payment Method</label>
                            <input type="text" class="form-control @error('payment_method') is-invalid @enderror"
                                   id="payment_method" name="payment_method"
                                   value="{{ old('payment_method', $data->payment_method) }}">
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="payment_status" class="mb-2">Payment Status</label>
                            <select id="payment_status" name="payment_status"
                                    class="form-select @error('payment_status') is-invalid @enderror">
                                <option value="pending" {{ old('payment_status', $data->payment_status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="success" {{ old('payment_status', $data->payment_status) === 'success' ? 'selected' : '' }}>Success</option>
                                <option value="failed" {{ old('payment_status', $data->payment_status) === 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="cancelled" {{ old('payment_status', $data->payment_status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('payment_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="payment_date" class="mb-2">Payment Date</label>
                            <input type="datetime-local"
                                   class="form-control @error('payment_date') is-invalid @enderror"
                                   id="payment_date" name="payment_date"
                                   value="{{ old('payment_date', optional($data->payment_date)->format('Y-m-d\TH:i')) }}">
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="mb-2">Current Balance</label>
                            <div class="form-control bg-light">
                                Rp {{ number_format((float) $data->balance, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Keterangan deposit...">{{ old('description', $data->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12 mt-4 mt-xxl-0">
                <div class="widget-content widget-content-area ecommerce-create-section">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <button type="submit" class="btn btn-success w-100">Update Deposit</button>
                        </div>
                        <div class="col-sm-12">
                            <a href="{{ route('deposit.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

@endsection
