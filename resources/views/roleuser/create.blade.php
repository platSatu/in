@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('roleuser.index') }}">Role User</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </nav>
    </div>

    @php
        $checkedUserIds = old('user_ids', $selectedUserId ? [$selectedUserId] : []);
        $checkedRoleIds = old('role_ids', $selectedRoleId ? [$selectedRoleId] : []);
    @endphp

    <form action="{{ route('roleuser.store') }}" method="POST">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="mb-2 fw-bold">Pilih User</label>
                            <div class="border rounded p-3" style="max-height: 340px; overflow-y: auto;">
                                @forelse ($users as $user)
                                    <label for="user_{{ $user->id }}"
                                        class="mb-2"
                                        style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal;">
                                        <input type="checkbox" name="user_ids[]"
                                            id="user_{{ $user->id }}" value="{{ $user->id }}"
                                            style="width: 16px; height: 16px; margin: 0; flex-shrink: 0;"
                                            {{ in_array($user->id, $checkedUserIds) ? 'checked' : '' }}>
                                        <span>{{ $user->name }} <span class="text-muted">({{ $user->email }})</span></span>
                                    </label>
                                @empty
                                    <p class="text-muted mb-0">Belum ada data user.</p>
                                @endforelse
                            </div>
                            @error('user_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="mb-2 fw-bold">Pilih Role</label>
                            <div class="border rounded p-3" style="max-height: 340px; overflow-y: auto;">
                                @forelse ($roles as $role)
                                    <label for="role_{{ $role->id }}"
                                        class="mb-2"
                                        style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal;">
                                        <input class="role-scope-checkbox" type="checkbox" name="role_ids[]"
                                            id="role_{{ $role->id }}" value="{{ $role->id }}"
                                            data-scope="{{ $role->scope_level }}"
                                            style="width: 16px; height: 16px; margin: 0; flex-shrink: 0;"
                                            {{ in_array($role->id, $checkedRoleIds) ? 'checked' : '' }}>
                                        <span>{{ $role->name }} <span class="text-muted">({{ ucfirst($role->scope_level) }})</span></span>
                                    </label>
                                @empty
                                    <p class="text-muted mb-0">Belum ada data role.</p>
                                @endforelse
                            </div>
                            @error('role_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="status" class="mb-2">Status</label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status') === 'inactive' ? '' : 'selected' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4" id="scopeFieldsRow">
                        <div class="col-sm-6" id="branchScopeField">
                            <label for="company_branch_id" class="mb-2">Company Branch</label>
                            <select id="company_branch_id" name="company_branch_id" class="form-select @error('company_branch_id') is-invalid @enderror">
                                <option value="">-- Pilih Branch --</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('company_branch_id') === $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Wajib diisi kalau ada role dengan scope Branch yang dicentang.</small>
                        </div>

                        <div class="col-sm-6" id="divisionScopeField">
                            <label for="company_division_id" class="mb-2">Division / Unit</label>
                            <select id="company_division_id" name="company_division_id" class="form-select @error('company_division_id') is-invalid @enderror">
                                <option value="">-- Pilih Division --</option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('company_division_id') === $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_division_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Wajib diisi kalau ada role dengan scope Division yang dicentang.</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12 mt-4 mt-xxl-0">
                <div class="widget-content widget-content-area ecommerce-create-section">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <button type="submit" class="btn btn-success w-100">Assign Role ke User</button>
                        </div>
                        <div class="col-sm-12">
                            <a href="{{ route('roleuser.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

<script>
    // Progressive enhancement saja — validasi sebenarnya tetap di server
    // (lihat RoleUserController::assertScopeSelected). Ini cuma
    // menyembunyikan/menampilkan field Branch & Division supaya admin tidak
    // bingung mengisi field yang tidak relevan dengan role yang dicentang.
    document.addEventListener('DOMContentLoaded', function () {
        var roleCheckboxes = document.querySelectorAll('.role-scope-checkbox');
        var branchField = document.getElementById('branchScopeField');
        var divisionField = document.getElementById('divisionScopeField');

        function syncScopeFields() {
            var needsBranch = false;
            var needsDivision = false;

            roleCheckboxes.forEach(function (checkbox) {
                if (!checkbox.checked) {
                    return;
                }
                if (checkbox.dataset.scope === 'branch') {
                    needsBranch = true;
                }
                if (checkbox.dataset.scope === 'division') {
                    needsDivision = true;
                }
            });

            if (branchField) {
                branchField.style.display = needsBranch ? '' : 'none';
            }
            if (divisionField) {
                divisionField.style.display = needsDivision ? '' : 'none';
            }
        }

        roleCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', syncScopeFields);
        });

        syncScopeFields();
    });
</script>

@endsection
