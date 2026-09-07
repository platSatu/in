@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 text-end">
        <a href="{{ route('settings.zoom.create') }}" class="btn btn-primary">+ Add Zoom Setting</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Account ID</th>
                                <th>Pemilik</th>
                                <th>Status</th>
                                <th>Aktif</th>
                                <th>Updated on</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="fw-bold">{{ $item->name ?? 'Zoom Account' }}</td>
                                    <td>
                                        {{-- Account ID ter-enkripsi di database & disamarkan di sini,
                                             cukup ditampilkan sebagian supaya admin bisa membedakan baris
                                             tanpa membuka kredensial lengkapnya di layar. --}}
                                        <code>{{ \Illuminate\Support\Str::limit($item->account_id, 10, '••••') }}</code>
                                    </td>
                                    <td>{{ optional($item->user)->name ?? '-' }}</td>
                                    <td>
                                        @if ($item->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->is_active)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($item->updated_at)->format('Y/m/d H:i') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            <a href="{{ route('settings.zoom.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            @unless ($item->is_active)
                                                <form action="{{ route('settings.zoom.activate', $item->id) }}"
                                                    method="POST" class="m-0">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-success text-nowrap">Activate</button>
                                                </form>
                                            @endunless

                                            <form action="{{ route('settings.zoom.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus konfigurasi Zoom ini?');" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger text-nowrap">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">Belum ada konfigurasi Zoom. Klik "Add Zoom Setting" untuk menambah.</td>
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
