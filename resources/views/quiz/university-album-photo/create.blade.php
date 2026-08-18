@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Add University Album Photo</h4>
    </div>
    <div class="widget-content widget-content-area">
        <form action="{{ route('quiz.university-album-photo.store') }}"
            method="POST"
            enctype="multipart/form-data">
            @csrf

            @php
                $lockedAlbumId = old('album_id', $selectedAlbumId ?? null);
                $lockedAlbum = $lockedAlbumId ? $albums->firstWhere('id', $lockedAlbumId) : null;
            @endphp

            <div class="mb-4">
                <label for="album_id" class="form-label"> Album</label>

                @if($lockedAlbum && !$errors->has('album_id'))
                    <input type="text" class="form-control" value="{{ $lockedAlbum->name }}" disabled readonly>
                    <input type="hidden" name="album_id" value="{{ $lockedAlbum->id }}">
                    <div class="form-text">
                        Foto akan ditambahkan ke album ini.
                        <a href="{{ route('quiz.university-album-photo.create') }}">Ganti album</a>
                    </div>
                @else
                    <select
                        class="form-select @error('album_id') is-invalid @enderror"
                        id="album_id"
                        name="album_id">
                        <option value="">
                            Choose...
                        </option>
                        @foreach($albums as $album)
                            <option value="{{ $album->id }}"
                                {{ $lockedAlbumId == $album->id ? 'selected' : '' }}>

                                {{ $album->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('album_id')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                @endif
            </div>
            <hr>
            <label class="form-label">
                Daftar Foto
            </label>

            <div id="photoRows">

                {{-- Baris awal (index 0) --}}
                <div class="photo-row border rounded p-3 mb-3">

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Foto</label>
                            <input
                                type="file"
                                name="photos[0][photo]"
                                accept="image/*"
                                class="form-control @error('photos.0.photo') is-invalid @enderror">
                            @error('photos.0.photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Title</label>
                            <input
                                type="text"
                                name="photos[0][title]"
                                class="form-control @error('photos.0.title') is-invalid @enderror"
                                value="{{ old('photos.0.title') }}">
                            @error('photos.0.title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Description</label>
                            <input
                                type="text"
                                name="photos[0][description]"
                                class="form-control @error('photos.0.description') is-invalid @enderror"
                                value="{{ old('photos.0.description') }}">
                            @error('photos.0.description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-1">
                            <label class="form-label">Urutan</label>
                            <input
                                type="number"
                                name="photos[0][sort_order]"
                                class="form-control"
                                value="{{ old('photos.0.sort_order', 0) }}">
                        </div>

                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger btn-remove-row w-100">
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @error('photos')
                <div class="text-danger mb-3">{{ $message }}</div>
            @enderror

            <button type="button" id="btnAddRow" class="btn btn-outline-primary mb-4">
                + Tambah Foto
            </button>
            <br>
            <button class="btn btn-primary"> Save </button>
            <a href="{{ $lockedAlbum ? route('quiz.university-album.index') : route('quiz.university-album-photo.index') }}"
                class="btn btn-secondary">Back </a>
        </form>
    </div>
</div>


{{-- Template baris baru, dipakai JS saat klik "Tambah Foto" --}}
<template id="photoRowTemplate">
    <div class="photo-row border rounded p-3 mb-3">

        <div class="row g-3">

            <div class="col-md-4">
                <label class="form-label">Foto</label>
                <input
                    type="file"
                    name="photos[__INDEX__][photo]"
                    accept="image/*"
                    class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Title</label>
                <input
                    type="text"
                    name="photos[__INDEX__][title]"
                    class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Description</label>
                <input
                    type="text"
                    name="photos[__INDEX__][description]"
                    class="form-control">
            </div>

            <div class="col-md-1">
                <label class="form-label">Urutan</label>
                <input
                    type="number"
                    name="photos[__INDEX__][sort_order]"
                    class="form-control"
                    value="__INDEX__">
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-remove-row w-100">
                    Hapus
                </button>
            </div>

        </div>

    </div>
</template>

<script>
    (function () {
        var rowIndex = 1; // index 0 sudah dipakai baris pertama
        var container = document.getElementById('photoRows');
        var template = document.getElementById('photoRowTemplate');

        document.getElementById('btnAddRow').addEventListener('click', function () {
            var html = template.innerHTML.replaceAll('__INDEX__', rowIndex);
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            container.appendChild(wrapper.firstElementChild);
            rowIndex++;
        });

        container.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-remove-row')) {
                var rows = container.querySelectorAll('.photo-row');
                if (rows.length > 1) {
                    e.target.closest('.photo-row').remove();
                } else {
                    alert('Minimal harus ada 1 foto.');
                }
            }
        });
    })();
</script>

@endsection