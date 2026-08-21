@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">

        <div class="row justify-content-between align-items-center">

            <div class="col-md-6">

                <nav class="breadcrumb-style-one" aria-label="breadcrumb">

                    <ol class="breadcrumb">

                        <li class="breadcrumb-item active" aria-current="page">
                            Whatsapp Template
                        </li>

                    </ol>

                </nav>

            </div>


            <div class="col-md-6 text-md-end mt-3 mt-md-0">

                <a href="{{ route('quiz.whatsapp-template.create') }}"
                    class="btn btn-primary">

                    + Add Template

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
                        action="{{ route('quiz.whatsapp-template.index') }}"
                        class="row g-2">


                        <div class="col-md-10">

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search whatsapp template..."
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

                                <th>
                                    No
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Content
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Created At
                                </th>

                                <th width="180" class="text-center">
                                    Action
                                </th>

                            </tr>


                        </thead>




                        <tbody>


                        @forelse($data as $index => $template)


                            <tr>


                                <td>
                                    {{ $data->firstItem() + $index }}
                                </td>



                                <td class="fw-bold">

                                    {{ $template->name }}

                                </td>




                                <td>

                                    {{ Str::limit($template->content, 80) }}

                                </td>




                                <td>


                                    @if($template->status == 'active')


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

                                    {{ optional($template->created_at)->format('Y/m/d') }}

                                </td>




                                <td class="text-center">

                                    {{-- Sebelumnya Edit/Delete disembunyikan di belakang dropdown
                                         titik-tiga, beda sendiri dari list page lain (mis. Major)
                                         yang tombol aksinya sejajar langsung kelihatan. Disamakan
                                         di sini biar konsisten. --}}
                                    <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">

                                        <a
                                            href="{{ route('quiz.whatsapp-template.edit',$template->id) }}"
                                            class="btn btn-sm btn-outline-primary text-nowrap">

                                            Edit

                                        </a>

                                        <form
                                            action="{{ route('quiz.whatsapp-template.destroy',$template->id) }}"
                                            method="POST"
                                            class="m-0"
                                            onsubmit="return confirm('Delete this whatsapp template?')">

                                            @csrf

                                            @method('DELETE')

                                            <button
                                                class="btn btn-sm btn-outline-danger text-nowrap">

                                                Delete

                                            </button>

                                        </form>

                                    </div>

                                </td>



                            </tr>



                        @empty



                            <tr>

                                <td colspan="6"
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