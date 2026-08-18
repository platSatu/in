@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex justify-content-between align-items-center">
        <h4>University Album</h4>

        <a href="{{ route('quiz.university-album.create') }}" class="btn btn-primary">
            + Add University Album
        </a>
    </div>


    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


    <div class="widget-content widget-content-area">

        <form method="GET" action="{{ route('quiz.university-album.index') }}" class="mb-3">
            <div class="input-group" style="max-width: 320px;">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Cari nama/deskripsi..."
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
                        <th>University</th>
                        <th>Nama Album</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                        <tr>
                            <td>{{ $item->university->name ?? '-' }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($item->description, 60) }}</td>
                            <td>
                                <span class="badge {{ $item->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('quiz.university-album-photo.create', ['album_id' => $item->id]) }}" class="btn btn-sm btn-success">
                                    + Add Photo
                                </a>
                                
                                <a href="{{ route('quiz.university-album.edit', $item->id) }}" class="btn btn-sm btn-primary">
                                    Edit
                                </a>

                                <form action="{{ route('quiz.university-album.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus album ini?');">
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
            {{ $data->links('pagination::bootstrap-5') }}
        </div>

    </div>

</div>

@endsection