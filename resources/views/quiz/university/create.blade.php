@extends('layouts.frontend')
@section('content')
    <div class="middle-content container-xxl p-0">

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Ada input yang belum valid:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('quiz.university.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row mb-4 layout-spacing layout-top-spacing">

                <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="widget-content widget-content-area ecommerce-create-section">

                        @if(!empty($lockedMajor))
                            <input type="hidden" name="major_id" value="{{ $lockedMajor->id }}">
                            <div class="alert alert-info">
                                University ini akan dikaitkan ke Major
                                <strong>{{ $lockedMajor->name }}</strong>.
                                <a href="{{ route('quiz.university.create') }}">Ganti major</a>
                            </div>
                        @endif

                        <div class="row mb-4">
                            <div class="col-sm-12">
                                <label for="name" class="mb-2">Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name') }}"
                                    placeholder="Enter university name...">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-sm-12">
                                <label for="country" class="mb-2">Country</label>

                                @php
                                    $lockedCountryValue = old('country', $lockedCountryName ?? null);
                                @endphp

                                @if($lockedCountryValue && !$errors->has('country'))
                                    <input type="text" class="form-control" value="{{ $lockedCountryValue }}" disabled readonly>
                                    <input type="hidden" name="country" value="{{ $lockedCountryValue }}">
                                    <div class="form-text">Country diturunkan dari City yang dipilih.</div>
                                @else
                                    <input type="text" class="form-control @error('country') is-invalid @enderror"
                                        id="country" name="country" value="{{ old('country') }}"
                                        placeholder="Enter country...">
                                @endif

                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                       <div class="row mb-4">
                            <div class="col-sm-12">
                                <label for="city" class="mb-2">City</label>

                                @php
                                    $lockedCity = !empty($lockedCityId) ? $cities->firstWhere('id', $lockedCityId) : null;
                                @endphp

                                @if($lockedCity && !$errors->has('city'))
                                    <input type="text" class="form-control" value="{{ $lockedCity->name }}" disabled readonly>
                                    <input type="hidden" name="city" value="{{ $lockedCity->id }}">
                                    <div class="form-text">City diturunkan dari Major yang dipilih.</div>
                                @else
                                    <select class="form-control @error('city') is-invalid @enderror"
                                        id="city" name="city">
                                        <option value="">Select city...</option>

                                        @foreach($cities as $city)
                                            <option value="{{ $city->id }}"
                                                {{ old('city') == $city->id ? 'selected' : '' }}>
                                                {{ $city->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif

                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-sm-12">
                                <label for="description" class="mb-2">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                    rows="5" placeholder="Enter description...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-sm-4">
                                <label for="logo" class="mb-2">Logo</label>
                                <input type="file" accept="image/*"
                                    class="form-control @error('logo') is-invalid @enderror" id="logo"
                                    name="logo">
                                <div class="form-text">Boleh dikosongkan.</div>
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-sm-4">
                                <label for="banner" class="mb-2">Banner</label>
                                <input type="file" accept="image/*"
                                    class="form-control @error('banner') is-invalid @enderror" id="banner"
                                    name="banner">
                                <div class="form-text">Boleh dikosongkan.</div>
                                @error('banner')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-sm-4">
                                <label for="attachment" class="mb-2">Attachment</label>
                                <input type="file" accept=".jpg,.jpeg,.pdf"
                                    class="form-control @error('attachment') is-invalid @enderror" id="attachment"
                                    name="attachment">
                                <div class="form-text">JPG atau PDF, boleh dikosongkan.</div>
                                @error('attachment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-xxl-12 mb-4">
                            <label for="status">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="">Choose...</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="row">
                        <div class="col-xxl-12 col-xl-4 col-lg-4 col-md-5 mt-4 mt-xxl-0">
                            <div class="widget-content widget-content-area ecommerce-create-section">
                                <div class="row">
                                    <div class="col-sm-12 mb-3">
                                        <button type="submit" class="btn btn-success w-100">Create University</button>
                                    </div>
                                    <div class="col-sm-12">
                                        <a href="{{ route('quiz.university.index') }}"
                                            class="btn btn-outline-secondary w-100">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
@endsection
