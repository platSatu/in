@extends('layouts.frontend')
@section('content')

{{--
    Form jam buka 1 pengajar di 1 cabang: baris per baris (+ Tambah Baris).
    Saat disimpan, semua baris lama diganti dengan isi form ini.
--}}

@php($days = \App\Models\TeacherAvailability::DAYS)

<div class="middle-content container-xxl p-0">
    <div class="row layout-top-spacing">
        <div class="col-lg-8 mx-auto layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <h5 class="mb-1">Jam Buka {{ $teacher->name }}</h5>
                <p class="text-muted mb-4">Cabang {{ $branch->name }} &middot; berlaku setiap minggu, Senin - Sabtu.</p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->unique() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('schedule.availability.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="teacher_user_id" value="{{ $teacher->id }}">
                    <input type="hidden" name="branch_id" value="{{ $branch->id }}">

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 35%">Hari</th>
                                    <th>Jam Mulai</th>
                                    <th>Jam Selesai</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="availabilityRows">
                                @foreach ($rows as $i => $row)
                                    <tr>
                                        <td>
                                            <select name="rows[{{ $i }}][day_of_week]" class="form-select" required>
                                                @foreach ($days as $number => $name)
                                                    <option value="{{ $number }}" @selected((int) $row['day_of_week'] === $number)>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="time" name="rows[{{ $i }}][start_time]" class="form-control" value="{{ $row['start_time'] }}" required></td>
                                        <td><input type="time" name="rows[{{ $i }}][end_time]" class="form-control" value="{{ $row['end_time'] }}" required></td>
                                        <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Hapus</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="text-muted small" id="emptyNote" @if (count($rows)) hidden @endif>
                        Belum ada jam buka. Klik "Tambah Baris" untuk menambahkan, misalnya Senin 13:00 - 17:00.
                    </p>

                    <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="addRow">+ Tambah Baris</button>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan Jam Buka</button>
                        <a href="{{ route('schedule.availability.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                    <small class="text-muted d-block mt-2">Kelas yang sudah diajukan siswa tidak ikut berubah. Jam buka baru berlaku untuk pengajuan berikutnya.</small>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="rows[__INDEX__][day_of_week]" class="form-select" required>
                @foreach ($days as $number => $name)
                    <option value="{{ $number }}">{{ $name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="time" name="rows[__INDEX__][start_time]" class="form-control" value="13:00" required></td>
        <td><input type="time" name="rows[__INDEX__][end_time]" class="form-control" value="17:00" required></td>
        <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Hapus</button></td>
    </tr>
</template>

<script>
(function () {
    var body = document.getElementById('availabilityRows');
    var template = document.getElementById('rowTemplate').innerHTML;
    var emptyNote = document.getElementById('emptyNote');
    var nextIndex = {{ count($rows) }};

    function refreshNote() { emptyNote.hidden = body.children.length > 0; }

    document.getElementById('addRow').addEventListener('click', function () {
        body.insertAdjacentHTML('beforeend', template.replace(/__INDEX__/g, nextIndex++));
        refreshNote();
    });

    body.addEventListener('click', function (event) {
        if (event.target.matches('[data-remove-row]')) {
            event.target.closest('tr').remove();
            refreshNote();
        }
    });
})();
</script>
@endsection
