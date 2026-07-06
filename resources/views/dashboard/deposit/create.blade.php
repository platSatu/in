@extends('layouts.frontend')

@section('content')
    <div class="col-lg-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-content widget-content-area">
                <h4 class="mb-4">Topup Deposit</h4>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('dashboard.deposit.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="debit" class="form-label">Nominal Deposit</label>
                        <input
                            type="number"
                            class="form-control @error('debit') is-invalid @enderror"
                            id="debit"
                            name="debit"
                            min="10000"
                            max="10000000"
                            step="1"
                            value="{{ old('debit') }}"
                            required
                        >
                        <small class="text-muted">Minimal 10.000 dan maksimal 10.000.000</small>
                        @error('debit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi (opsional)</label>
                        <textarea
                            class="form-control @error('description') is-invalid @enderror"
                            id="description"
                            name="description"
                            rows="3"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Submit Deposit</button>
                        <a href="{{ route('public.packages.index') }}" class="btn btn-outline-secondary">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
