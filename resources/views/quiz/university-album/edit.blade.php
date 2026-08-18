@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Edit University Album</h4>
    </div>


    <div class="widget-content widget-content-area">


        <form action="{{ route('quiz.university-album.update', $data->id) }}"
            method="POST">

            @csrf
            @method('PUT')


            <div class="mb-3">

                <label for="university_id" class="form-label">
                    University
                </label>


                <select
                    class="form-select @error('university_id') is-invalid @enderror"
                    id="university_id"
                    name="university_id">


                    <option value="">
                        Choose...
                    </option>


                    @foreach($universities as $university)

                        <option value="{{ $university->id }}"
                            {{ old('university_id', $data->university_id) == $university->id ? 'selected' : '' }}>

                            {{ $university->name }}

                        </option>

                    @endforeach


                </select>


                @error('university_id')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror


            </div>





            <div class="mb-3">

                <label class="form-label">
                    Album Name
                </label>


                <input
                    type="text"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $data->name) }}">


                @error('name')

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


            <a href="{{ route('quiz.university-album.index') }}"
                class="btn btn-secondary">

                Back

            </a>



        </form>


    </div>


</div>


@endsection