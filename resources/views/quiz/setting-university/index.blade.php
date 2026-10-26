@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 text-end">
        <a href="{{ route('quiz.setting-university.create') }}"
                    class="btn btn-primary">
                    + Add Setting University
                </a>
    </div>


    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show">

            {{ session('success') }}

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    <div class="row layout-top-spacing">

        <div class="col-xl-12 layout-spacing">

            <div class="widget-content widget-content-area br-8">


                <div class="mb-4">

                    <form method="GET"
                        action="{{ route('quiz.setting-university.index') }}"
                        class="row g-2">

                        <div class="col-md-10">

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search setting university..."
                                value="{{ request('search') }}">

                        </div>


                        <div class="col-md-2 d-grid">

                            <button class="btn btn-outline-primary">
                                Search
                            </button>

                        </div>

                    </form>

                </div>



                <div class="table-responsive">

                    <table class="table dt-table-hover">

                        <thead>

                            <tr>
                                <th>No</th>
                                <th>City</th>
                                <th>Major</th>
                                <th>University</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th width="160" class="text-center">
                                    Action
                                </th>
                            </tr>

                        </thead>


                        <tbody>


                        @forelse ($data as $index => $setting)

                            <tr>

                                <td>
                                    {{ $data->firstItem() + $index }}
                                </td>


                                <td>
                                    {{ $setting->city->name ?? '-' }}
                                </td>


                                <td>
                                    {{ $setting->major->name ?? '-' }}
                                </td>


                                <td>
                                     {{ $setting->university->name ?? '-' }}
                                </td>


                                <td>

                                    @if($setting->status == 'active')

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    @else

                                        <span class="badge bg-danger">
                                            Inactive
                                        </span>

                                    @endif

                                </td>


                                <td>
                                    {{ optional($setting->created_at)->format('Y/m/d') }}
                                </td>


                                <td class="text-center">


                                    <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                        <a href="{{ route('quiz.setting-university.edit', $setting->id) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                            Edit
                                        </a>

                                        <form action="{{ route('quiz.setting-university.destroy', $setting->id) }}" method="POST" class="m-0" onsubmit="return confirm('Delete this setting university?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger text-nowrap">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>


                            </tr>


                        @empty


                            <tr>

                                <td colspan="7"
                                    class="text-center">

                                    No data.

                                </td>

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