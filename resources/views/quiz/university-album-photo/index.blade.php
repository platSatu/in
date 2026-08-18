@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex justify-content-between align-items-center">
        <h4>University Album Photo</h4>

        <a href="{{ route('quiz.university-album-photo.create') }}" class="btn btn-primary">
            + Add Photo
        </a>
    </div>


    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


    <div class="widget-content widget-content-area">

        <form method="GET" action="{{ route('quiz.university-album-photo.index') }}" class="mb-3">
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
                        <th>Album</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th style="width: 90px;">Urutan</th>
                        <th>Status</th>
                        <th style="width: 160px;">Aksi</th>
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
                            <td>{{ $item->album->name ?? '-' }}</td>
                            <td>{{ $item->title }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($item->description, 60) }}</td>
                            <td>{{ $item->sort_order }}</td>
                            <td>
                                <span class="badge {{ $item->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('quiz.university-album-photo.edit', $item->id) }}" class="btn btn-sm btn-primary">
                                    Edit
                                </a>

                                <form action="{{ route('quiz.university-album-photo.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus foto ini?');">
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
                            <td colspan="7" class="text-center">Belum ada data.</td>
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