@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex justify-content-between align-items-center">
        <h4>QR Code</h4>

        <a href="{{ route('qrcodes.create') }}" class="btn btn-primary">
            + Add QR Code
        </a>
    </div>


    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


    <div class="widget-content widget-content-area">

        <form method="GET" action="{{ route('qrcodes.index') }}" class="mb-3">
            <div class="input-group" style="max-width: 320px;">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Cari nama/link..."
                    value="{{ request('search') }}">

                <button class="btn btn-outline-secondary" type="submit">
                    Cari
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width: 90px;">QR</th>
                        <th>Name</th>
                        <th>Link</th>
                        <th>Status</th>
                        <th style="width: 220px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                        <tr>
                            <td>
                                @if($item->qrcode)
                                    <img src="{{ asset($item->directory_qrcode . '/' . $item->qrcode) }}" alt="QR {{ $item->name }}" style="width: 60px; height: 60px;">
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $item->name }}</td>
                            <td>
                                <a href="{{ $item->link }}" target="_blank" rel="noopener">
                                    {{ \Illuminate\Support\Str::limit($item->link, 40) }}
                                </a>
                            </td>
                            <td>
                                <span class="badge {{ $item->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($item->status ?? '-') }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('qrcodes.show', $item->id) }}" class="btn btn-sm btn-outline-primary">
                                    Lihat
                                </a>

                                <a href="{{ route('qrcodes.edit', $item->id) }}" class="btn btn-sm btn-primary">
                                    Edit
                                </a>

                                <form action="{{ route('qrcodes.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus QR code ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $data->links() }}
        </div>

    </div>

</div>

@endsection