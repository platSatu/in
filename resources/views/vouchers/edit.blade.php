@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('vouchers.index') }}">Vouchers</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('vouchers.update', $data->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">

                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="application_category_id" class="mb-2">Category</label>
                            <select id="application_category_id" name="application_category_id" class="form-select @error('application_category_id') is-invalid @enderror">
                                <option value="">-- Pilih Category --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('application_category_id', $data->application_category_id) === $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('application_category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="status" class="mb-2">Status</label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', $data->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $data->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="expired" {{ old('status', $data->status) === 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="code_vouchers" class="mb-2">Code Voucher</label>
                            <input type="text"
                                   class="form-control @error('code_vouchers') is-invalid @enderror"
                                   id="code_vouchers" name="code_vouchers" value="{{ old('code_vouchers', $data->code_vouchers) }}">
                            @error('code_vouchers')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-3">
                            <label for="valid_from" class="mb-2">Valid From</label>
                            <input type="date"
                                   class="form-control @error('valid_from') is-invalid @enderror"
                                   id="valid_from" name="valid_from"
                                   value="{{ old('valid_from', optional($data->valid_from)->format('Y-m-d')) }}">
                            @error('valid_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-3">
                            <label for="valid_until" class="mb-2">Valid Until</label>
                            <input type="date"
                                   class="form-control @error('valid_until') is-invalid @enderror"
                                   id="valid_until" name="valid_until"
                                   value="{{ old('valid_until', optional($data->valid_until)->format('Y-m-d')) }}">
                            @error('valid_until')
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
                            <button type="submit" class="btn btn-success w-100">Update Voucher</button>
                        </div>
                        <div class="col-sm-12">
                            <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

@endsection
