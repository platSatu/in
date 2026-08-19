@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Edit Whatsapp Template</h4>
    </div>


    <div class="widget-content widget-content-area">


        <form action="{{ route('quiz.whatsapp-template.update', $data->id) }}"
            method="POST">

            @csrf
            @method('PUT')


            <div class="mb-3">

                <label class="form-label">
                    Template Name
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
                    Content
                </label>


                <textarea
                    name="content"
                    rows="6"
                    class="form-control @error('content') is-invalid @enderror"
                    placeholder="Example: Halo, terima kasih sudah mengikuti quiz.">{{ old('content', $data->content) }}</textarea>


                @error('content')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror

                <div class="form-text">
                    Placeholder yang bisa dipakai: <code>{{ '{{name}}' }}</code>, <code>{{ '{{form_name}}' }}</code>,
                    <code>{{ '{{ringkasan_jawaban}}' }}</code>, <code>{{ '{{universitas_major}}' }}</code>, dan
                    <code>{{ '{{callback_link}}' }}</code> (link callback form, mis. link Zoom — hanya terisi kalau
                    form-nya diaktifkan sebagai callback dan sudah lolos verifikasi pembayaran/submit).
                </div>

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


            <a href="{{ route('quiz.whatsapp-template.index') }}"
                class="btn btn-secondary">

                Back

            </a>



        </form>


    </div>


</div>


@endsection