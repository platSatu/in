@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('pembayaran.form-link.index') }}">Form Link Pembayaran</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('pembayaran.form-link.store') }}" method="POST">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="parent_id" class="mb-2">Parent User</label>
                            <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                                <option value="">Choose...</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ old('parent_id') === $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="pembayaran_form_id" class="mb-2">Pembayaran Form</label>
                            <select class="form-select @error('pembayaran_form_id') is-invalid @enderror"
                                id="pembayaran_form_id" name="pembayaran_form_id">
                                <option value="">Choose...</option>
                                @foreach ($forms as $form)
                                    <option value="{{ $form->id }}"
                                        {{ old('pembayaran_form_id') === $form->id ? 'selected' : '' }}>
                                        {{ $form->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('pembayaran_form_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="payment_status" class="mb-2">Payment Status</label>
                            <select class="form-select @error('payment_status') is-invalid @enderror" id="payment_status"
                                name="payment_status">
                                <option value="">Choose...</option>
                                <option value="pending" {{ old('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ old('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="failed" {{ old('payment_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="cancelled" {{ old('payment_status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('payment_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="payment_method" class="mb-2">Payment Method</label>
                            <input type="text" class="form-control @error('payment_method') is-invalid @enderror"
                                id="payment_method" name="payment_method" value="{{ old('payment_method') }}"
                                placeholder="e.g. bank_transfer, ewallet...">
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="payment_date" class="mb-2">Payment Date</label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror"
                                id="payment_date" name="payment_date" value="{{ old('payment_date') }}">
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="order_id" class="mb-2">Order ID</label>
                            <input type="text" class="form-control @error('order_id') is-invalid @enderror"
                                id="order_id" name="order_id" value="{{ old('order_id') }}" placeholder="Enter order id...">
                            @error('order_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="row">
                    <div class="col-xxl-12 col-xl-8 col-lg-8 col-md-7 mt-xxl-0 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-xxl-12 mb-4">
                                    <label for="status">Status</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                        <option value="">Choose...</option>
                                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-12 col-xl-4 col-lg-4 col-md-5 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-sm-12 mb-3">
                                    <button type="submit" class="btn btn-success w-100">Create Form Link</button>
                                </div>
                                <div class="col-sm-12">
                                    <a href="{{ route('pembayaran.form-link.index') }}"
                                        class="btn btn-outline-secondary w-100">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

@endsection
