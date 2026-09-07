@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Edit Meeting Zoom</h4>
    </div>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="widget-content widget-content-area">

        <form action="{{ route('zoom.meeting.update', $data->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="topic" class="form-label">Topik Meeting</label>
                <input type="text" class="form-control @error('topic') is-invalid @enderror" id="topic" name="topic"
                    value="{{ old('topic', $data->topic) }}">
                @error('topic')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="agenda" class="form-label">Agenda <span class="text-muted">(opsional)</span></label>
                <textarea class="form-control @error('agenda') is-invalid @enderror" id="agenda" name="agenda"
                    rows="3">{{ old('agenda', $data->agenda) }}</textarea>
                @error('agenda')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row mb-3">
                <div class="col-sm-4">
                    <label for="start_date" class="form-label">Tanggal</label>
                    <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                        id="start_date" name="start_date" value="{{ old('start_date', optional($data->start_time)->format('Y-m-d')) }}">
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label for="start_time_of_day" class="form-label">Jam Mulai</label>
                    <input type="time" class="form-control @error('start_time_of_day') is-invalid @enderror"
                        id="start_time_of_day" name="start_time_of_day" value="{{ old('start_time_of_day', optional($data->start_time)->format('H:i')) }}">
                    @error('start_time_of_day')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label for="duration" class="form-label">Durasi (menit)</label>
                    <input type="number" min="1" max="1440" class="form-control @error('duration') is-invalid @enderror"
                        id="duration" name="duration" value="{{ old('duration', $data->duration) }}">
                    @error('duration')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-sm-6">
                    <label for="timezone" class="form-label">Timezone</label>
                    <select class="form-select @error('timezone') is-invalid @enderror" id="timezone" name="timezone">
                        @foreach ($timezones as $tz)
                            <option value="{{ $tz }}" {{ old('timezone', $data->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                    @error('timezone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-6">
                    <label for="password" class="form-label">Passcode <span class="text-muted">(kosongkan = tidak berubah)</span></label>
                    <input type="text" maxlength="10" class="form-control @error('password') is-invalid @enderror"
                        id="password" name="password" value="{{ old('password') }}" placeholder="{{ $data->password }}">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            @php $currentSettings = $data->settings ?? []; @endphp
            <div class="mb-3">
                <label class="form-label d-block">Opsi Meeting</label>
                <div class="row">
                    <div class="col-sm-4 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="join_before_host"
                                name="join_before_host" value="1" {{ old('join_before_host', $currentSettings['join_before_host'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="join_before_host">Boleh join sebelum host</label>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="waiting_room"
                                name="waiting_room" value="1" {{ old('waiting_room', $currentSettings['waiting_room'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="waiting_room">Waiting room</label>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="host_video"
                                name="host_video" value="1" {{ old('host_video', $currentSettings['host_video'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="host_video">Video host aktif</label>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="participant_video"
                                name="participant_video" value="1" {{ old('participant_video', $currentSettings['participant_video'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="participant_video">Video peserta aktif</label>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="mute_upon_entry"
                                name="mute_upon_entry" value="1" {{ old('mute_upon_entry', $currentSettings['mute_upon_entry'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="mute_upon_entry">Mute otomatis saat masuk</label>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary">Save</button>
            <a href="{{ route('zoom.meeting.index') }}" class="btn btn-secondary">Back</a>

        </form>

    </div>

</div>

@endsection
