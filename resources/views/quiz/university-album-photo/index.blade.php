@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            @if($album)
                <p class="text-muted mb-0">Foto di album <strong>{{ $album->name }}</strong>@if($album->university) &middot; {{ $album->university->name }}@endif</p>
            @else
                <span></span>
            @endif

            <div class="d-flex flex-wrap gap-2">
                @if($album)
                    <a href="{{ route('quiz.university-album.index', ['university_id' => $album->university_id]) }}" class="btn btn-secondary">
                        Back to Album
                    </a>
                @endif

                <a href="{{ route('quiz.university-album-photo.create', $albumId ? ['album_id' => $albumId] : []) }}" class="btn btn-primary">
                    + Add Photo
                </a>
            </div>
        </div>
    </div>


    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


    <div class="widget-content widget-content-area">

        <form method="GET" action="{{ route('quiz.university-album-photo.index') }}" class="mb-3">
            @if($albumId)
                <input type="hidden" name="album_id" value="{{ $albumId }}">
            @endif
            <div class="input-group" style="max-width: 320px;">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Cari title/deskripsi..."
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
                        <th style="width: 100px;">Foto</th>
                        {{-- Kolom Album disembunyikan saat sudah scoped (redundan, semua
                             baris pasti dari album yang sama). --}}
                        @unless($album)
                            <th>Album</th>
                        @endunless
                        <th>Title</th>
                        <th>Description</th>
                        <th style="width: 90px;">Urutan</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                        <tr>
                            <td>
                                @if($item->photo)
                                    <img src="{{ asset($item->photo) }}" alt="{{ $item->title }}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px;">
                                @else
                                    -
                                @endif
                            </td>
                            @unless($album)
                                <td>{{ $item->album->name ?? '-' }}</td>
                            @endunless
                            <td>{{ $item->title }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($item->description, 60) }}</td>
                            <td>{{ $item->sort_order }}</td>
                            <td>
                                <span class="badge {{ $item->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                    <a href="{{ route('quiz.university-album-photo.edit', $item->id) }}"
                                        class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                    <form action="{{ route('quiz.university-album-photo.destroy', $item->id) }}" method="POST" class="m-0" onsubmit="return confirm('Hapus foto ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $album ? 6 : 7 }}" class="text-center">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $data->links('pagination::bootstrap-5') }}
        </div>

    </div>

</div>

@endsection
