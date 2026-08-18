@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>
            Add Setting University
        </h4>
    </div>


    <div class="widget-content widget-content-area">

        <form action="{{ route('quiz.setting-university.store') }}"
            method="POST">

            @csrf


            <div class="mb-3">

                <label class="form-label">
                    City
                </label>

                <select name="city_id"
                    class="form-control @error('city_id') is-invalid @enderror">

                    <option value="">
                        -- Select City --
                    </option>

                    @foreach($cities as $city)

                        <option value="{{ $city->id }}"
                            {{ old('city_id') == $city->id ? 'selected' : '' }}>

                            {{ $city->name }}

                        </option>

                    @endforeach

                </select>


                @error('city_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror

            </div>



            <div class="mb-3">

                <label class="form-label">
                    Major
                </label>


                <select name="major_id"
                    class="form-control @error('major_id') is-invalid @enderror">


                    <option value="">
                        -- Select Major --
                    </option>


                    @foreach($majors as $major)

                        <option value="{{ $major->id }}"
                            {{ old('major_id') == $major->id ? 'selected' : '' }}>

                            {{ $major->name }}

                        </option>

                    @endforeach


                </select>


                @error('major_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror


            </div>



            <div class="mb-3">

                <label class="form-label">
                    University
                </label>


                <select name="university_id"
                    class="form-control @error('university_id') is-invalid @enderror">


                    <option value="">
                        -- Select University --
                    </option>


                    @foreach($universities as $university)

                        <option value="{{ $university->id }}"
                            {{ old('university_id') == $university->id ? 'selected' : '' }}>

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
                    Status
                </label>


                <select name="status"
                    class="form-control">


                    <option value="active"
                        {{ old('status') == 'active' ? 'selected' : '' }}>

                        Active

                    </option>


                    <option value="inactive"
                        {{ old('status') == 'inactive' ? 'selected' : '' }}>

                        Inactive

                    </option>


                </select>


            </div>




            <button class="btn btn-primary">
                Save
            </button>


            <a href="{{ route('quiz.setting-university.index') }}"
                class="btn btn-secondary">
                Back
            </a>


        </form>


    </div>


</div>

@endsection