@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('quiz.university-application.index') }}" class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search application no / student name / email / phone..." value="{{ $search }}">
                        </div>
                        <div class="col-md-3">
                            <select name="university_id" class="form-select">
                                <option value="">All Universities</option>
                                @foreach ($universities as $university)
                                    <option value="{{ $university->id }}" {{ (string) $universityId === (string) $university->id ? 'selected' : '' }}>
                                        {{ $university->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="intake_year" class="form-select">
                                <option value="">All Intake Years</option>
                                @foreach ($intakeYears as $year)
                                    <option value="{{ $year }}" {{ (string) $intakeYear === (string) $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                @foreach ($statuses as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" {{ $status === $statusKey ? 'selected' : '' }}>
                                        {{ $statusLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Filter</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Application No</th>
                                <th>Student</th>
                                <th>University</th>
                                <th>Major</th>
                                <th>Intake</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="fw-bold">{{ $item->application_no }}</td>
                                    <td>
                                        {{ trim(($item->student->first_name ?? '') . ' ' . ($item->student->last_name ?? '')) ?: '-' }}
                                        <div class="text-muted" style="font-size:12px;">{{ $item->whatsapp }}</div>
                                    </td>
                                    <td>{{ optional($item->university)->name ?? '-' }}</td>
                                    <td>{{ optional($item->universityProfile)->field ?? '-' }}</td>
                                    <td>{{ $item->intake ?: '-' }} {{ $item->intake_year }}</td>
                                    <td>
                                        <span class="badge badge-info text-capitalize">{{ str_replace('_', ' ', $item->status) }}</span>
                                    </td>
                                    <td>{{ optional($item->submitted_at)->format('Y/m/d') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('quiz.university-application.show', $item->id) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">Belum ada aplikasi kuliah masuk.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $data->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
