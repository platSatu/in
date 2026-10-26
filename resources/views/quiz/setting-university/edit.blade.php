@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">




    <div class="widget-content widget-content-area">


        <form
            action="{{ route('quiz.setting-university.update',$data->id) }}"
            method="POST">


            @csrf
            @method('PUT')



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
                            {{ old('city_id', $data->city_id) == $city->id ? 'selected' : '' }}>

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
                            {{ old('major_id', $data->major_id) == $major->id ? 'selected' : '' }}>

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





            {{-- Tambah university lain ke major yang sama. Setiap baris jadi pasangan Major-University baru. --}}
            @php($extraIds = old('extra_university_ids', []))
            <div class="mb-3 border rounded p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label mb-0">Tambah University lain untuk major ini</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-university-row">+ Add Row</button>
                </div>
                <div class="small text-muted mb-2">City, Major, dan Status mengikuti isian di atas. University yang sudah terhubung ke major ini otomatis dilewati.</div>

                <div id="university-rows">
                    @foreach ($extraIds as $i => $extraId)
                        <div class="d-flex gap-2 mb-2" data-university-row>
                            <select name="extra_university_ids[]" class="form-control @error('extra_university_ids.' . $i) is-invalid @enderror">
                                <option value="">-- Select University --</option>
                                @foreach ($universities as $university)
                                    <option value="{{ $university->id }}" {{ $extraId == $university->id ? 'selected' : '' }}>{{ $university->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-danger" data-remove-row>Hapus</button>
                        </div>
                        @error('extra_university_ids.' . $i)
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                    @endforeach
                </div>

                <template id="university-row-template">
                    <div class="d-flex gap-2 mb-2" data-university-row>
                        <select name="extra_university_ids[]" class="form-control">
                            <option value="">-- Select University --</option>
                            @foreach ($universities as $university)
                                <option value="{{ $university->id }}">{{ $university->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-danger" data-remove-row>Hapus</button>
                    </div>
                </template>
            </div>





            <div class="mb-3">

                <label class="form-label">
                    Status
                </label>


                <select name="status"
                    class="form-control">


                    <option value="active"
                        {{ old('status', $data->status) == 'active' ? 'selected' : '' }}>

                        Active

                    </option>


                    <option value="inactive"
                        {{ old('status', $data->status) == 'inactive' ? 'selected' : '' }}>

                        Inactive

                    </option>


                </select>


            </div>





            <button class="btn btn-primary">

                Update

            </button>



            <a href="{{ route('quiz.setting-university.index') }}"
                class="btn btn-secondary">

                Back

            </a>



        </form>


    </div>


</div>


<script>
    (function () {
        var rows = document.getElementById('university-rows');
        var template = document.getElementById('university-row-template');

        document.getElementById('add-university-row').addEventListener('click', function () {
            rows.appendChild(template.content.cloneNode(true));
        });

        rows.addEventListener('click', function (event) {
            var button = event.target.closest('[data-remove-row]');
            if (button) {
                button.closest('[data-university-row]').remove();
            }
        });
    })();
</script>

@endsection
