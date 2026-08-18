@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>{{ $data->name }}</h4>
    </div>

    <div class="widget-content widget-content-area text-center" style="max-width: 480px; margin: 0 auto;">

        @php
            $qrUrl = asset($data->directory_qrcode . '/' . $data->qrcode);
            $qrFilename = \Illuminate\Support\Str::slug($data->name) . '.png';
        @endphp

        @if($data->qrcode)
            <img id="qrImage" src="{{ $qrUrl }}" alt="QR {{ $data->name }}" style="width: 260px; height: 260px; margin-bottom: 20px;">
        @else
            <p class="text-muted">QR code belum tersedia.</p>
        @endif

        <p class="mb-1"><strong>Link:</strong></p>
        <p class="mb-4">
            <a href="{{ $data->link }}" target="_blank" rel="noopener">{{ $data->link }}</a>
        </p>

        @if($data->description)
            <p class="text-muted mb-4">{{ $data->description }}</p>
        @endif

        <div class="d-flex flex-column gap-2 mb-4" style="max-width: 300px; margin: 0 auto;">

            {{-- Download QR sebagai gambar --}}
            <a href="{{ $qrUrl }}" download="{{ $qrFilename }}" class="btn btn-primary">
                ⬇ Download QR Code
            </a>

            {{-- Share ke WhatsApp (link-nya, bukan gambar QR) --}}
            <a href="https://wa.me/?text={{ urlencode($data->name . ' - ' . $data->link) }}" target="_blank" class="btn btn-success">
                Share via WhatsApp
            </a>

            {{-- Copy link ke clipboard --}}
            <button type="button" id="btnCopyLink" class="btn btn-outline-secondary">
                Copy Link
            </button>

            {{-- Native share (kalau browser/device mendukung, misal HP) --}}
            <button type="button" id="btnNativeShare" class="btn btn-outline-primary d-none">
                Share...
            </button>

        </div>

        <a href="{{ route('qrcodes.index') }}" class="btn btn-link">
            ← Kembali ke daftar
        </a>

    </div>

</div>

<script>
    (function () {
        var link = @json($data->link);
        var name = @json($data->name);

        var btnCopy = document.getElementById('btnCopyLink');
        if (btnCopy) {
            btnCopy.addEventListener('click', function () {
                navigator.clipboard.writeText(link).then(function () {
                    btnCopy.textContent = 'Link Tersalin!';
                    setTimeout(function () {
                        btnCopy.textContent = 'Copy Link';
                    }, 2000);
                });
            });
        }

        var btnNativeShare = document.getElementById('btnNativeShare');
        if (btnNativeShare && navigator.share) {
            btnNativeShare.classList.remove('d-none');
            btnNativeShare.addEventListener('click', function () {
                navigator.share({
                    title: name,
                    text: name,
                    url: link,
                });
            });
        }
    })();
</script>

@endsection