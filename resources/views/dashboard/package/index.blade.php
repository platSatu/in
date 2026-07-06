@extends('layouts.frontend')

@section('content')
    <div class="col-12 layout-spacing">
        <div class="row g-3">
            @forelse ($packages as $package)
                <div class="col-12 col-md-6 col-xl-4 d-flex">
                    <div class="statbox widget box box-shadow w-100 h-100">
                        <div class="widget-content widget-content-area d-flex flex-column">
                            <div class="mb-3">
                                <h4 class="mb-1">{{ $package->name }}</h4>
                                <p class="text-muted mb-0">
                                    {{ $package->description ?: 'Paket terbaik untuk kebutuhan Anda.' }}
                                </p>
                            </div>

                            <div class="mb-3">
                                <h3 class="mb-0">
                                    Rp {{ number_format((float) $package->price, 0, ',', '.') }}
                                </h3>
                                <small class="text-muted">/ {{ (int) $package->duration_days }} hari</small>
                            </div>

                            <ul class="list-unstyled mb-4">
                                <li class="mb-2">• Status: {{ ucfirst((string) $package->status) }}</li>
                                <li class="mb-2">• Durasi: {{ (int) $package->duration_days }} hari</li>
                                <li class="mb-2">• Bisa dibeli user umum</li>
                            </ul>

                            <div class="mt-auto">
                                <a href="{{ route('public.packages.checkout', $package->id) }}" class="btn btn-primary w-100">
                                    Buy
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning mb-0">
                        Belum ada package aktif yang tersedia.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
