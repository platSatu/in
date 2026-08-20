@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('roleuser.index') }}">Role User</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('roleuser.update', $data->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="user_id" class="mb-2">User</label>
                            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">-- Pilih User --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ old('user_id', $data->user_id) === $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="role_id" class="mb-2">Role</label>
                            <select id="role_id" name="role_id" class="form-select @error('role_id') is-invalid @enderror">
                                <option value="">-- Pilih Role --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" data-scope="{{ $role->scope_level }}"
                                        {{ old('role_id', $data->role_id) === $role->id ? 'selected' : '' }}>
                                        {{ $role->name }} ({{ ucfirst($role->scope_level) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="status" class="mb-2">Status</label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', $data->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $data->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6" id="branchScopeField">
                            <label for="company_branch_id" class="mb-2">Company Branch</label>
                            <select id="company_branch_id" name="company_branch_id" class="form-select @error('company_branch_id') is-invalid @enderror">
                                <option value="">-- Pilih Branch --</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ old('company_branch_id', $data->company_branch_id) === $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Wajib diisi kalau role di atas scope-nya Branch.</small>
                        </div>

                        <div class="col-sm-6" id="divisionScopeField">
                            <label for="company_division_id" class="mb-2">Division / Unit</label>
                            <select id="company_division_id" name="company_division_id" class="form-select @error('company_division_id') is-invalid @enderror">
                                <option value="">-- Pilih Division --</option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}"
                                        {{ old('company_division_id', $data->company_division_id) === $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_division_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Wajib diisi kalau role di atas scope-nya Division.</small>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12 mt-4 mt-xxl-0">
                <div class="widget-content widget-content-area ecommerce-create-section">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <button type="submit" class="btn btn-success w-100">Update Role User</button>
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
    // (lihat RoleUserController::assertScopeSelected).
    document.addEventListener('DOMContentLoaded', function () {
        var roleSelect = document.getElementById('role_id');
        var branchField = document.getElementById('branchScopeField');
        var divisionField = document.getElementById('divisionScopeField');

        function syncScopeFields() {
            var selected = roleSelect.options[roleSelect.selectedIndex];
            var scope = selected ? selected.dataset.scope : null;

            if (branchField) {
                branchField.style.display = scope === 'branch' ? '' : 'none';
            }
            if (divisionField) {
                divisionField.style.display = scope === 'division' ? '' : 'none';
            }
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', syncScopeFields);
            syncScopeFields();
        }
    });
</script>

@endsection
