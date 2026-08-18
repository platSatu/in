@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Edit QR Code</h4>
    </div>


    <div class="widget-content widget-content-area">

        @if($data->qrcode)
            <div class="mb-4">
                <label class="form-label d-block">QR Code Saat Ini</label>
                <img src="{{ asset($data->directory_qrcode . '/' . $data->qrcode) }}" alt="QR {{ $data->name }}" style="width: 160px;">
                <div class="form-text">QR akan otomatis dibuat ulang kalau Link diubah dan disimpan.</div>
            </div>
        @endif


        <form action="{{ route('qrcodes.update', $data->id) }}" method="POST">

            @csrf
            @method('PUT')


            <div class="mb-3">

                <label class="form-label">
                    Name
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
                    Link
                </label>


                <input
                    type="text"
                    name="link"
                    class="form-control @error('link') is-invalid @enderror"
                    value="{{ old('link', $data->link) }}">


                @error('link')

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


            <a href="{{ route('qrcodes.index') }}"
                class="btn btn-secondary">

                Back

            </a>



        </form>


    </div>


</div>


@endsection