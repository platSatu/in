@extends('layouts.frontend')
@section('content')

{{--
    FASE "InaStudy Register Manual" (14 September 2026, permintaan user):
    widget ini dulu cuma tampil kalau $myApplications tidak kosong
    (@if($myApplications->isNotEmpty())). Sekarang widgetnya SELALU tampil
    untuk siswa (dicek lewat $hasStudent, bukan lagi isi/tidaknya
    $myApplications) -- kalau masih kosong, tabel menampilkan baris
    "Data not found" + tombol Register, supaya siswa yang belum pernah apply
    tetap bisa mulai proses langsung dari sini tanpa lewat halaman katalog
    publik (yang sudah tidak ada link-nya lagi di sidebar student, lihat
    perubahan menu InaYule/InaStudy/InaTrip).
--}}
@if($hasStudent)
<div class="row">
    <div class="col-12">
        <div class="widget-content widget-content-area br-8 mb-4">
            <h4 class="mb-3">My University Applications</h4>

            {{-- Panel Register: default hidden, ditampilkan lewat tombol "+ Register"
                 di atas, atau otomatis kalau validasi submit sebelumnya gagal
                 (old('university_id') masih terisi). --}}
            <div id="registerFormPanel" class="border rounded p-3 mb-3" style="{{ $errors->any() && old('university_id') ? '' : 'display:none;' }} background:#f8f9fa;">
                <h6 class="mb-3">Register Aplikasi Kuliah</h6>
                <form method="POST" action="{{ route('dashboard.apply.manual') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Universitas</label>
                            <select name="university_id" id="registerUniversitySelect" class="form-select @error('university_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Universitas --</option>
                                @foreach($registerUniversities as $university)
                                    <option value="{{ $university->id }}" {{ old('university_id') == $university->id ? 'selected' : '' }}>{{ $university->name }}</option>
                                @endforeach
                            </select>
                            @error('university_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6" id="registerProfileWrapper" style="{{ old('university_id') ? '' : 'display:none;' }}">
                            <label class="form-label">Jurusan</label>
                            <select name="university_profile_id" id="registerProfileSelect" class="form-select @error('university_profile_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Jurusan --</option>
                            </select>
                            @error('university_profile_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                        <button type="button" id="btnCancelRegister" class="btn btn-outline-secondary btn-sm">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Application No</th>
                            <th>University</th>
                            <th>Major</th>
                            <th>Status</th>
                            <th style="width:150px;">Documents</th>
                            <th>Submitted</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myApplications as $myApplication)
                            <tr>
                                <td class="fw-bold">{{ $myApplication->application_no }}</td>
                                <td>{{ optional($myApplication->university)->name ?? '-' }}</td>
                                <td>{{ optional($myApplication->universityProfile)->field ?? '-' }}</td>
                                <td><span class="badge bg-info text-capitalize">{{ str_replace('_', ' ', $myApplication->status) }}</span></td>
                                <td>
                                    @php
                                        $docsCount = $myApplication->documents_count ?? 0;
                                        $docsPercent = $totalDocumentTypes > 0 ? min(100, round(($docsCount / $totalDocumentTypes) * 100)) : 0;
                                        $isComplete = $totalDocumentTypes > 0 && $docsCount >= $totalDocumentTypes;
                                    @endphp
                                    <div style="font-size:12px;" class="mb-1">{{ $docsCount }} / {{ $totalDocumentTypes }}</div>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar {{ $isComplete ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $docsPercent }}%;" aria-valuenow="{{ $docsPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    @if ($isComplete)
                                        <span class="badge bg-success mt-1" style="font-size:10px;width:auto;height:auto;border-radius:.25rem;padding:.25em .5em;">Documents Complete</span>
                                    @endif
                                </td>
                                <td>{{ optional($myApplication->submitted_at)->format('Y/m/d') }}</td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('student-portal.applications.show', $myApplication->id) }}" class="btn btn-sm btn-outline-primary">Summary</a>
                                    <a href="{{ route('student-portal.applications.form.edit', $myApplication->id) }}" class="btn btn-sm btn-outline-warning">Form</a>
                                    <a href="{{ route('student-portal.applications.documents.edit', $myApplication->id) }}" class="btn btn-sm btn-outline-success">Documents</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <div class="mb-2">Data not found</div>
                                    <button type="button" id="btnShowRegister" class="btn btn-primary btn-sm">Register</button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="widget-content widget-content-area br-8 mb-4">
            <div class="calendar-header d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Academic Calendar</h4>
                <a href="{{ route('absensi.academic-calendar.index') }}" class="btn btn-primary btn-sm">
                    Manage Calendar
                </a>
            </div>
            
            <div class="calendar-wrapper">
                <!-- Month Navigation -->
                <div class="calendar-nav d-flex justify-content-between align-items-center mb-4">
                    <button class="btn btn-outline-secondary btn-sm" id="prevMonth">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <h5 id="currentMonth" class="mb-0"></h5>
                    <button class="btn btn-outline-secondary btn-sm" id="nextMonth">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
                
                <!-- Calendar Grid -->
                <div class="calendar-grid">
                    <div class="calendar-weekdays">
                        <div class="text-center">Sun</div>
                        <div class="text-center">Mon</div>
                        <div class="text-center">Tue</div>
                        <div class="text-center">Wed</div>
                        <div class="text-center">Thu</div>
                        <div class="text-center">Fri</div>
                        <div class="text-center">Sat</div>
                    </div>
                    <div class="calendar-days" id="calendarDays">
                        <!-- Days will be generated by JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Legend -->
            <div class="calendar-legend mt-4">
                <div class="d-flex flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-2">&nbsp;</span>
                        <small>Holiday</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-danger me-2">&nbsp;</span>
                        <small>Exam</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-2">&nbsp;</span>
                        <small>Semester</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-warning me-2">&nbsp;</span>
                        <small>Event</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary me-2">&nbsp;</span>
                        <small>Other</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-grid {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.calendar-weekdays > div {
    padding: 10px;
    text-align: center;
    font-weight: 600;
    font-size: 12px;
    color: #6c757d;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendar-day {
    min-height: 80px;
    padding: 8px;
    border-right: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
    background: #fff;
}

.calendar-day:nth-child(7n) {
    border-right: none;
}

.calendar-day.other-month {
    background: #f8f9fa;
    color: #adb5bd;
}

.calendar-day.today {
    background: #e7f5ff;
}

.day-number {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 5px;
}

.event-item {
    font-size: 10px;
    padding: 2px 5px;
    margin-bottom: 2px;
    border-radius: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.event-item.holiday {
    background: #d1fae5;
    color: #065f46;
}

.event-item.exam {
    background: #fee2e2;
    color: #991b1b;
}

.event-item.semester {
    background: #dbeafe;
    color: #1e40af;
}

.event-item.event {
    background: #fef3c7;
    color: #92400e;
}

.event-item.other {
    background: #e5e7eb;
    color: #374151;
}

/*
 * SEBELUMNYA: selector ".badge" polos di sini (dimaksudkan cuma untuk kotak
 * kecil warna di Legend kalender) menimpa class Bootstrap ".badge" di
 * SELURUH halaman ini -- termasuk badge status "Submitted"/dst di tabel "My
 * University Applications" di atas, yang jadi keciiil banget (12x12px)
 * sampai teksnya tidak kelihatan sama sekali (cuma tampil kotak polos).
 * Diperbaiki dengan scope selector ke ".calendar-legend .badge" saja, supaya
 * cuma kotak warna Legend yang kena, badge Bootstrap lain di halaman ini
 * tetap tampil normal (lihat pertanyaan user "itu status kotak gitu aja
 * mksdnya apa ya", 10 September 2026).
 */
.calendar-legend .badge {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 2px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentDate = new Date();
    const calendars = @json($calendars);
    
    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        document.getElementById('currentMonth').textContent = 
            new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDay = firstDay.getDay();
        const totalDays = lastDay.getDate();
        
        const daysContainer = document.getElementById('calendarDays');
        daysContainer.innerHTML = '';
        
        // Previous month days
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        for (let i = startDay - 1; i >= 0; i--) {
            const dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-day other-month';
            dayDiv.innerHTML = `<span class="day-number">${prevMonthLastDay - i}</span>`;
            daysContainer.appendChild(dayDiv);
        }
        
        // Current month days
        const today = new Date();
        for (let i = 1; i <= totalDays; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-day';
            
            if (today.getDate() === i && today.getMonth() === month && today.getFullYear() === year) {
                dayDiv.classList.add('today');
            }
            
            dayDiv.innerHTML = `<span class="day-number">${i}</span>`;
            
            // Add events for this day
            const currentDayStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            
            calendars.forEach(calendar => {
                const startDate = calendar.start_date;
                const endDate = calendar.end_date;
                
                if (currentDayStr >= startDate && currentDayStr <= endDate) {
                    const eventDiv = document.createElement('div');
                    eventDiv.className = `event-item ${calendar.event_type}`;
                    eventDiv.textContent = calendar.title;
                    eventDiv.title = calendar.title + '\n' + 
                        new Date(startDate).toLocaleDateString() + ' - ' + new Date(endDate).toLocaleDateString();
                    dayDiv.appendChild(eventDiv);
                }
            });
            
            daysContainer.appendChild(dayDiv);
        }
        
        // Next month days
        const remainingDays = 42 - (startDay + totalDays);
        for (let i = 1; i <= remainingDays; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-day other-month';
            dayDiv.innerHTML = `<span class="day-number">${i}</span>`;
            daysContainer.appendChild(dayDiv);
        }
    }
    
    document.getElementById('prevMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });
    
    document.getElementById('nextMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });
    
    renderCalendar();
});
</script>

{{--
    FASE "InaStudy Register Manual" (14 September 2026) -- JS pendukung panel
    Register di widget "My University Applications" di atas: buka/tutup
    panel, dan isi cascading dropdown Jurusan berdasarkan Universitas yang
    dipilih (data-nya dari $registerUniversities, sudah termasuk relasi
    profiles() masing-masing lewat eager load di DashboardController::index()).
    Dibungkus pengecekan elemen (bukan @if($hasStudent) di Blade) supaya aman
    kalau suatu saat markup di atas berubah tanpa perlu ingat sinkronkan
    kondisinya di sini juga.
--}}
<script>
(function () {
    const registerUniversities = @json($registerUniversities ?? []);
    const btnShow = document.getElementById('btnShowRegister');
    const panel = document.getElementById('registerFormPanel');
    const btnCancel = document.getElementById('btnCancelRegister');
    const uniSelect = document.getElementById('registerUniversitySelect');
    const profileWrapper = document.getElementById('registerProfileWrapper');
    const profileSelect = document.getElementById('registerProfileSelect');

    if (!panel || !uniSelect || !profileSelect || !profileWrapper) {
        return;
    }

    function populateProfiles(universityId, selectedProfileId) {
        profileSelect.innerHTML = '<option value="">-- Pilih Jurusan --</option>';

        const university = registerUniversities.find(function (u) { return u.id === universityId; });
        const profiles = university ? (university.profiles || []) : [];

        profiles.forEach(function (profile) {
            const option = document.createElement('option');
            option.value = profile.id;
            option.textContent = profile.degree_title ? (profile.field + ' - ' + profile.degree_title) : profile.field;
            if (selectedProfileId && String(selectedProfileId) === String(profile.id)) {
                option.selected = true;
            }
            profileSelect.appendChild(option);
        });

        profileWrapper.style.display = profiles.length ? '' : 'none';
    }

    if (btnShow) {
        btnShow.addEventListener('click', function () {
            panel.style.display = '';
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', function () {
            panel.style.display = 'none';
            uniSelect.value = '';
            profileSelect.innerHTML = '<option value="">-- Pilih Jurusan --</option>';
            profileWrapper.style.display = 'none';
        });
    }

    uniSelect.addEventListener('change', function () {
        populateProfiles(this.value, null);
    });

    // Kalau submit sebelumnya gagal validasi (university_id masih terisi
    // lewat old()), panel-nya sudah otomatis ditampilkan lewat inline style
    // di Blade di atas -- tinggal isi ulang dropdown Jurusan-nya di sini.
    const oldUniversityId = @json(old('university_id'));
    const oldProfileId = @json(old('university_profile_id'));
    if (oldUniversityId) {
        populateProfiles(oldUniversityId, oldProfileId);
    }
})();
</script>

@endsection
