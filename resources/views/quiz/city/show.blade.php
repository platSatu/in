@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('city.index') }}">City</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $data->name }}</li>
            </ol>
        </nav>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <h4 class="mb-1">{{ $data->name }}</h4>
                    <p class="text-muted mb-0">
                        Country: {{ optional($data->country)->name ?? '-' }}
                    </p>
                    @if($data->description)
                        <p class="mt-2 mb-0">{{ $data->description }}</p>
                    @endif
                </div>

                <hr>

                <h5 class="mb-3">University di {{ $data->name }}</h5>

                <div class="table-responsive">
                    <table class="table dt-table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama University</th>
                                <th>Country</th>
                                <th class="text-center" width="140">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data->universities as $index => $university)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-bold">
                                        <a href="{{ route('quiz.university.show', $university->id) }}">
                                            {{ $university->name }}
                                        </a>
                                    </td>
                                    <td>{{ $university->country }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('quiz.university.show', $university->id) }}" class="btn btn-sm btn-outline-info text-nowrap">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada university di kota ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <a href="{{ route('city.index') }}" class="btn btn-secondary">Back</a>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
