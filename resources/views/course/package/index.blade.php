@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-end align-items-center">
            <div class="col-md-6 text-md-end">
                <a href="{{ route('course.package.create') }}" class="btn btn-primary">+ Add Course Package</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Pesan validasi form Pilih Semua (mis. belum pilih cabang tujuan), sebelumnya tidak pernah tampil. --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                @if ($withoutBranch > 0)
                    {{-- Paket tanpa cabang tidak tampil di website (25 November 2026). --}}
                    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span><strong>{{ $withoutBranch }} paket belum punya cabang</strong> dan belum tampil di website. Tampilkan, centang Pilih Semua, lalu Pindahkan ke cabang.</span>
                        <a href="{{ route('course.package.index', ['branch' => 'none']) }}" class="btn btn-sm btn-warning">Tampilkan paket ini</a>
                    </div>
                @endif

                <div class="mb-4">
                    <form method="GET" action="{{ route('course.package.index') }}" class="row g-2">
                        <div class="col-md-3">
                            <select name="branch" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Cabang</option>
                                <option value="none" @selected($branchFilter === 'none')>Belum ada cabang</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($branchFilter === $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search course package..."
                                value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2 d-grid">
                            <button class="btn btn-outline-primary">
                                Search
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Pilih Semua -> Copy / Pindahkan ke cabang. Checkbox baris memakai form="bulkForm"
                     karena tombol Copy/Delete per baris sudah punya form sendiri. --}}
                <form method="POST" action="{{ route('course.package.bulk') }}" id="bulkForm"
                    class="border rounded-3 p-3 mb-3 bg-light" hidden>
                    @csrf
                    <input type="hidden" name="all_filtered" value="0" id="bulkAllFiltered">
                    <input type="hidden" name="search" value="{{ $search }}">
                    <input type="hidden" name="branch" value="{{ $branchFilter }}">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <strong id="bulkCount">0 paket dipilih</strong>
                        @if ($data->total() > $data->count())
                            <button type="button" class="btn btn-link btn-sm p-0" id="bulkSelectFiltered">
                                Pilih juga semua {{ $data->total() }} paket di filter ini
                            </button>
                        @endif
                        <span class="ms-md-auto"></span>
                        <select name="action" class="form-select form-select-sm w-auto" required id="bulkAction">
                            <option value="copy">Copy ke cabang</option>
                            <option value="move">Pindahkan ke cabang</option>
                            {{-- Hapus banyak paket (26 November 2026): paket yang sudah dibeli/dibayar/dipakai kelas otomatis dilewati. --}}
                            <option value="delete">Hapus</option>
                        </select>
                        <select name="target_branch_id" class="form-select form-select-sm w-auto" required id="bulkBranch">
                            <option value="">-- Pilih cabang --</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm" id="bulkSubmit">Proses</button>
                    </div>
                    <small class="text-muted d-block mt-2">Copy = paket digandakan ke cabang tujuan (nama tetap sama). Pindahkan = cabang paket diganti. Hapus = paket dihapus permanen; paket yang sudah pernah dibeli, dibayar, atau dipakai jadwal kelas otomatis dilewati (nonaktifkan saja).</small>
                </form>

                <div class="table-responsive">
                    <table class="table dt-table-hover">
                        <thead>
                            <tr>
                                <th style="width:36px"><input type="checkbox" class="form-check-input" id="bulkSelectAll" title="Pilih semua di halaman ini"></th>
                                <th>No</th>
                                <th>Name</th>
                                <th>Cabang</th>
                                <th>Type</th>
                                <th>Class</th>
                                <th>Level</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Promo</th>
                                <th>Credits</th>
                                <th>Status</th>
                                <th width="260" class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        @forelse ($data as $index => $item)

                            <tr>
                                <td><input type="checkbox" class="form-check-input bulk-row" name="ids[]" value="{{ $item->id }}" form="bulkForm"></td>
                                <td>{{ $data->firstItem() + $index }}</td>

                                <td class="fw-bold">
                                    {{ $item->name }}
                                </td>

                                <td>
                                    @if ($item->branch)
                                        {{ $item->branch->name }}
                                    @else
                                        <span class="badge bg-warning text-dark">Belum ada cabang</span>
                                    @endif
                                </td>

                                <td>{{ optional($item->type)->name ?? '-' }}</td>
                                <td>{{ optional($item->courseClass)->name ?? '-' }}</td>
                                <td>{{ optional($item->level)->name ?? '-' }}</td>

                                <td>{{ $item->duration_value }} {{ ucfirst($item->duration_unit) }}{{ $item->duration_value > 1 ? 's' : '' }}</td>

                                <td>Rp {{ number_format((float) $item->price, 0, ',', '.') }}</td>

                                <td>
                                    @if ($item->promo_price !== null)
                                        <span class="badge bg-danger">Rp {{ number_format((float) $item->promo_price, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td>{{ rtrim(rtrim(number_format((float) $item->credits, 2, ',', '.'), '0'), ',') }}</td>

                                <td>
                                    @if ($item->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                        <a href="{{ route('course.package.edit', $item->id) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                            Edit
                                        </a>

                                        {{--
                                            FIX (16 September 2026, permintaan user -- "buatkan fungsi
                                            copy di packages index, pastikan semuanya tercopy sama
                                            persis, name-nya tambahkan '- copy'"): duplikat 1 baris
                                            lewat CoursePackageController::copy() -- lihat docblock
                                            method tsb, semua kolom produk disalin apa adanya, cuma
                                            name yang ditambah suffix ' - Copy'.
                                        --}}
                                        <form action="{{ route('course.package.copy', $item->id) }}" method="POST" class="m-0" onsubmit="return confirm('Copy course package ini?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary text-nowrap">
                                                Copy
                                            </button>
                                        </form>

                                        <form action="{{ route('course.package.destroy', $item->id) }}" method="POST" class="m-0" onsubmit="return confirm('Delete this course package?')">
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
                                <td colspan="13" class="text-center">
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

                <script>
                (function () {
                    var form = document.getElementById('bulkForm');
                    var all = document.getElementById('bulkSelectAll');
                    var rows = Array.prototype.slice.call(document.querySelectorAll('.bulk-row'));
                    var count = document.getElementById('bulkCount');
                    var allFiltered = document.getElementById('bulkAllFiltered');
                    var selectFiltered = document.getElementById('bulkSelectFiltered');

                    function refresh() {
                        var checked = rows.filter(function (row) { return row.checked; }).length;
                        if (allFiltered.value !== '1') {
                            count.textContent = checked + ' paket dipilih';
                        }
                        form.hidden = checked === 0 && allFiltered.value !== '1';
                        all.checked = checked > 0 && checked === rows.length;
                    }

                    all.addEventListener('change', function () {
                        rows.forEach(function (row) { row.checked = all.checked; });
                        if (!all.checked) { allFiltered.value = '0'; }
                        refresh();
                    });
                    rows.forEach(function (row) {
                        row.addEventListener('change', function () { allFiltered.value = '0'; refresh(); });
                    });
                    if (selectFiltered) {
                        selectFiltered.addEventListener('click', function () {
                            allFiltered.value = '1';
                            rows.forEach(function (row) { row.checked = true; });
                            count.textContent = 'Semua {{ $data->total() }} paket di filter ini dipilih';
                            refresh();
                        });
                    }
                    // Hapus tidak butuh cabang tujuan: sembunyikan & lepas "required" (26 November 2026).
                    var actionSelect = document.getElementById('bulkAction');
                    var branchSelect = document.getElementById('bulkBranch');
                    var submitButton = document.getElementById('bulkSubmit');
                    function syncAction() {
                        var isDelete = actionSelect.value === 'delete';
                        branchSelect.hidden = isDelete;
                        branchSelect.required = !isDelete;
                        submitButton.className = 'btn btn-sm ' + (isDelete ? 'btn-danger' : 'btn-primary');
                        submitButton.textContent = isDelete ? 'Hapus' : 'Proses';
                    }
                    actionSelect.addEventListener('change', syncAction);
                    syncAction();

                    form.addEventListener('submit', function (event) {
                        var message;
                        if (actionSelect.value === 'delete') {
                            message = count.textContent + ' akan DIHAPUS PERMANEN. Paket yang sudah pernah dibeli, dibayar, atau dipakai jadwal kelas otomatis dilewati. Lanjutkan?';
                        } else {
                            var action = actionSelect.selectedOptions[0].textContent.toLowerCase();
                            var branch = branchSelect.selectedOptions[0].textContent;
                            message = count.textContent + ' akan di-' + action.replace(' ke cabang', '') + ' ke cabang ' + branch + '. Lanjutkan?';
                        }
                        if (!confirm(message)) {
                            event.preventDefault();
                        }
                    });
                })();
                </script>

            </div>
        </div>
    </div>

</div>

@endsection
