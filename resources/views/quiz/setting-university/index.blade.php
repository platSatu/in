@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">
                            Setting University
                        </li>
                    </ol>
                </nav>
            </div>

            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="{{ route('quiz.setting-university.create') }}"
                    class="btn btn-primary">
                    + Add Setting University
                </a>
            </div>
        </div>
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
                                <th width="100" class="text-center">
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


                                    <div class="dropdown">


                                        <a
                                            class="dropdown-toggle"
                                            href="#"
                                            data-bs-toggle="dropdown">


                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                width="24"
                                                height="24"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2">

                                                <circle cx="12" cy="12" r="1"></circle>
                                                <circle cx="19" cy="12" r="1"></circle>
                                                <circle cx="5" cy="12" r="1"></circle>

                                            </svg>


                                        </a>



                                        <div class="dropdown-menu">


                                            <a
                                                class="dropdown-item"
                                                href="{{ route('quiz.setting-university.edit',$setting->id) }}">
                                                Edit
                                            </a>



                                            <form
                                                action="{{ route('quiz.setting-university.destroy',$setting->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Delete this setting university?')">


                                                @csrf
                                                @method('DELETE')


                                                <button
                                                    class="dropdown-item text-danger">
                                                    Delete
                                                </button>


                                            </form>


                                        </div>


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