@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Edit University Album Photo</h4>
    </div>


    <div class="widget-content widget-content-area">


        <form action="{{ route('quiz.university-album-photo.update', $data->id) }}"
            method="POST"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')


            <div class="mb-3">

                <label for="album_id" class="form-label">
                    Album
                </label>


                <select
                    class="form-select @error('album_id') is-invalid @enderror"
                    id="album_id"
                    name="album_id">


                    <option value="">
                        Choose...
                    </option>


                    @foreach($albums as $album)

                        <option value="{{ $album->id }}"
                            {{ old('album_id', $data->album_id) == $album->id ? 'selected' : '' }}>

                            {{ $album->name }}

                        </option>

                    @endforeach


                </select>


                @error('album_id')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror


            </div>





            <div class="mb-3">

                <label class="form-label">
                    Foto Saat Ini
                </label>

                <div class="mb-2">
                    @if($data->photo)
                        <img src="{{ asset($data->photo) }}" alt="{{ $data->title }}" style="max-width: 200px; border-radius: 8px;">
                    @else
                        <span class="text-muted">Belum ada foto</span>
                    @endif
                </div>

                <input
                    type="file"
                    name="photo"
                    accept="image/*"
                    class="form-control @error('photo') is-invalid @enderror">

                <div class="form-text">Kosongkan kalau tidak ingin mengganti foto.</div>

                @error('photo')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror


            </div>





            <div class="mb-3">

                <label class="form-label">
                    Title
                </label>


                <input
                    type="text"
                    name="title"
                    class="form-control @error('title') is-invalid @enderror"
                    value="{{ old('title', $data->title) }}">


                @error('title')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror


            </div>





            <div class="mb-3">

                <label class="form-label">
                    Description
                </label>


                <textarea
                    name="description"
                    rows="4"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $data->description) }}</textarea>


                @error('description')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror


            </div>





            <div class="mb-3">

                <label class="form-label">
                    Urutan
                </label>


                <input
                    type="number"
                    name="sort_order"
                    class="form-control @error('sort_order') is-invalid @enderror"
                    value="{{ old('sort_order', $data->sort_order) }}">


                @error('sort_order')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror


            </div>





            <div class="mb-4">

                <label for="status">
                    Status
                </label>


                <select
                    class="form-select @error('status') is-invalid @enderror"
                    id="status"
                    name="status">


                    <option value="">
                        Choose...
                    </option>


                    <option value="active"
                        {{ old('status', $data->status) === 'active' ? 'selected' : '' }}>

                        Active

                    </option>


                    <option value="inactive"
                        {{ old('status', $data->status) === 'inactive' ? 'selected' : '' }}>

                        Inactive

                    </option>


                </select>


                @error('status')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror


            </div>





            <button class="btn btn-primary">
                Save
            </button>


            <a href="{{ route('quiz.university-album-photo.index') }}"
                class="btn btn-secondary">

                Back

            </a>



        </form>


    </div>


</div>


@endsection