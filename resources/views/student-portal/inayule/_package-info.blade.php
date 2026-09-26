{{-- Nama package + Type / Class / Level, sama seperti menu Laporan Course. --}}
<div class="fw-bold">{{ $package->name ?? '-' }}</div>
@if ($package)
    <div class="text-muted" style="font-size:12px;">{{ optional($package->type)->name ?? '-' }}</div>
    <div class="text-muted" style="font-size:12px;">{{ optional($package->courseClass)->name ?? '-' }}</div>
    <div class="text-muted" style="font-size:12px;">{{ optional($package->level)->name ?? '-' }}</div>
@endif
