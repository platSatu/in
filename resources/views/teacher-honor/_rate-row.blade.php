{{-- 1 baris aturan Tarif Pengajar. $i = nomor baris (atau __INDEX__ untuk template). --}}
<tr>
    <td>
        <select name="rows[{{ $i }}][teacher_user_id]" class="form-select form-select-sm">
            <option value="">Semua pengajar</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected(($row['teacher_user_id'] ?? null) === $teacher->id)>{{ $teacher->name }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select name="rows[{{ $i }}][branch_id]" class="form-select form-select-sm" @unless ($canUseAllBranches) required @endunless>
            @if ($canUseAllBranches)
                <option value="">Semua cabang</option>
            @else
                <option value="">-- Pilih cabang --</option>
            @endif
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(($row['branch_id'] ?? null) === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select name="rows[{{ $i }}][course_class_id]" class="form-select form-select-sm">
            <option value="">Semua kelas</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected(($row['course_class_id'] ?? null) === $class->id)>{{ $class->name }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select name="rows[{{ $i }}][honor_mode]" class="form-select form-select-sm" required>
            @foreach (\App\Models\CourseClass::HONOR_MODES as $value => $label)
                <option value="{{ $value }}" @selected(($row['honor_mode'] ?? null) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td><input type="number" name="rows[{{ $i }}][fee]" class="form-control form-control-sm" min="0" step="1000" value="{{ $row['fee'] ?? '' }}" placeholder="mis. 10000" required></td>
    <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Hapus</button></td>
</tr>
