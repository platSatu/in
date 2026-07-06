@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('transaction.index') }}">Transaction</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('transaction.store') }}" method="POST">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">

                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="type" class="mb-2">Type</label>
                            <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                                <option value="">-- Pilih Type --</option>
                                <option value="debit" {{ old('type') === 'debit' ? 'selected' : '' }}>Debit</option>
                                <option value="credit" {{ old('type') === 'credit' ? 'selected' : '' }}>Credit</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="status" class="mb-2">Status</label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="">-- Pilih Status --</option>
                                <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="success" {{ old('status') === 'success' ? 'selected' : '' }}>Success</option>
                                <option value="failed" {{ old('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-4">
                            <label for="amount" class="mb-2">Amount</label>
                            <input type="number" min="0" step="0.01"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   id="amount" name="amount" value="{{ old('amount') }}">
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-4">
                            <label for="balance_before" class="mb-2">Balance Before</label>
                            <input type="number" min="0" step="0.01"
                                   class="form-control @error('balance_before') is-invalid @enderror"
                                   id="balance_before" name="balance_before" value="{{ old('balance_before') }}">
                            @error('balance_before')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-4">
                            <label for="balance_after" class="mb-2">Balance After</label>
                            <input type="number" min="0" step="0.01"
                                   class="form-control @error('balance_after') is-invalid @enderror"
                                   id="balance_after" name="balance_after" value="{{ old('balance_after') }}">
                            @error('balance_after')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="reference_type" class="mb-2">Reference Type</label>
                            <input type="text"
                                   class="form-control @error('reference_type') is-invalid @enderror"
                                   id="reference_type" name="reference_type" value="{{ old('reference_type') }}">
                            @error('reference_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="reference_id" class="mb-2">Reference ID</label>
                            <input type="text"
                                   class="form-control @error('reference_id') is-invalid @enderror"
                                   id="reference_id" name="reference_id" value="{{ old('reference_id') }}">
                            @error('reference_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="transaction_date" class="mb-2">Transaction Date</label>
                            <input type="datetime-local"
                                   class="form-control @error('transaction_date') is-invalid @enderror"
                                   id="transaction_date" name="transaction_date"
                                   value="{{ old('transaction_date') }}">
                            @error('transaction_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="description" class="mb-2">Description</label>
                            <textarea id="description" name="description" rows="1"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Keterangan transaction...">{{ old('description') }}</textarea>
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
                            <button type="submit" class="btn btn-success w-100">Create Transaction</button>
                        </div>
                        <div class="col-sm-12">
                            <a href="{{ route('transaction.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

@endsection
