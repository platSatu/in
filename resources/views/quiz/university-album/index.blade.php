@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        {{-- Breadcrumb + "Back to Profile" hanya muncul kalau index ini dibuka
             scoped dari halaman profile University (query ?university_id=...). --}}
        @if($university)
            <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('quiz.university.index') }}">University</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('quiz.university.show', $university->id) }}">{{ $university->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Album</li>
                </ol>
            </nav>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h4 class="mb-0">University Album</h4>

            <div class="d-flex flex-wrap gap-2">
                @if($university)
                    <a href="{{ route('quiz.university.show', $university->id) }}" class="btn btn-secondary">
                        Back to Profile
                    </a>
                @endif

                <a href="{{ route('quiz.university-album.create', $universityId ? ['university_id' => $universityId] : []) }}" class="btn btn-primary">
                    + Add University Album
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

        <form method="GET" action="{{ route('quiz.university-album.index') }}" class="mb-3">
            @if($universityId)
                <input type="hidden" name="university_id" value="{{ $universityId }}">
            @endif
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
                        {{-- Kolom University disembunyikan saat sudah scoped (redundan, semua
                             baris pasti dari university yang sama). --}}
                        @unless($university)
                            <th>University</th>
                        @endunless
                        <th>Nama Album</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 220px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                        <tr>
                            @unless($university)
                                <td>{{ $item->university->name ?? '-' }}</td>
                            @endunless
                            <td>{{ $item->name }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($item->description, 60) }}</td>
                            <td>
                                <span class="badge {{ $item->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                    <a href="{{ route('quiz.university-album-photo.index', ['album_id' => $item->id]) }}"
                                        class="btn btn-sm btn-outline-secondary text-nowrap">Foto</a>

                                    <a href="{{ route('quiz.university-album-photo.create', ['album_id' => $item->id]) }}"
                                        class="btn btn-sm btn-outline-success text-nowrap">+ Photo</a>

                                    <a href="{{ route('quiz.university-album.edit', $item->id) }}"
                                        class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                    <form action="{{ route('quiz.university-album.destroy', $item->id) }}" method="POST" class="m-0" onsubmit="return confirm('Hapus album ini?');">
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
                            <td colspan="{{ $university ? 4 : 5 }}" class="text-center">Belum ada data.</td>
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
