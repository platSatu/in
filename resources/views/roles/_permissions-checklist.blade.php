{{--
    Partial checklist akses menu/modul untuk form Role (dipakai di create.blade.php
    & edit.blade.php). Variabel yang diharapkan sudah di-set oleh view pemanggil:
    - $permissionGroups: Collection<string, Collection<Permission>> (grouped by group_label)
    - $selectedPermissionIds: array id permission yang sudah dicentang "Lihat" (opsional, default [])
    - $selectedEditIds: array id permission yang sudah dicentang "Kelola" (opsional, default [])
--}}
@php
    $checkedViewIds = old('permissions', $selectedPermissionIds ?? []);
    $checkedEditIds = old('can_edit', $selectedEditIds ?? []);
@endphp

<div class="row mb-4">
    <div class="col-sm-12">
        <label class="mb-2">Akses Menu / Modul</label>
        <p class="text-muted mb-2" style="font-size: 0.85rem;">
            Centang <strong>Lihat</strong> supaya role ini bisa membuka menu tsb.
            Centang juga <strong>Kelola</strong> kalau role ini boleh
            tambah/ubah/hapus data di menu tsb (bukan cuma lihat).
        </p>

        <div style="max-height: 420px; overflow-y: auto; border: 1px solid #e0e6ed; border-radius: 6px; padding: 12px 16px;">
            @forelse ($permissionGroups as $groupLabel => $groupPermissions)
                <div class="mb-3">
                    <strong>{{ $groupLabel }}</strong>
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach ($groupPermissions as $permission)
                                <tr>
                                    <td style="width: 55%;">{{ $permission->label }}</td>
                                    <td style="width: 22%;">
                                        <label for="perm_view_{{ $permission->id }}"
                                            style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 0; cursor: pointer; font-weight: normal;">
                                            <input type="checkbox"
                                                name="permissions[]" value="{{ $permission->id }}"
                                                id="perm_view_{{ $permission->id }}"
                                                style="width: 16px; height: 16px; margin: 0; flex-shrink: 0;"
                                                {{ in_array($permission->id, $checkedViewIds) ? 'checked' : '' }}>
                                            <span>Lihat</span>
                                        </label>
                                    </td>
                                    <td style="width: 23%;">
                                        <label for="perm_edit_{{ $permission->id }}"
                                            style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 0; cursor: pointer; font-weight: normal;">
                                            <input type="checkbox"
                                                name="can_edit[]" value="{{ $permission->id }}"
                                                id="perm_edit_{{ $permission->id }}"
                                                style="width: 16px; height: 16px; margin: 0; flex-shrink: 0;"
                                                {{ in_array($permission->id, $checkedEditIds) ? 'checked' : '' }}>
                                            <span>Kelola</span>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p class="text-muted mb-0">
                    Belum ada permission terdaftar. Jalankan <code>php artisan permissions:sync</code> di server.
                </p>
            @endforelse
        </div>
    </div>
</div>
