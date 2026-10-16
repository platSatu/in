{{-- Tab "Application Form": isian Formulir siswa (ApplicationFormController) dalam format A4 siap print. 16 Oktober 2026. --}}
@php
    $form = $application->formDetail;
    $student = $application->student;
    $value = fn ($v) => filled($v) ? $v : '-';
    $sponsorships = ['scholarship' => 'Scholarship', 'self_sponsored' => 'Self Sponsored'];
@endphp

<style id="a4-style">
    .a4-sheet { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 14mm 14mm 12mm; background: #fff; color: #111; font-size: 12px; line-height: 1.45; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
    .a4-head { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 10px; }
    .a4-head img.logo { height: 42px; }
    .a4-head h4 { margin: 0; font-size: 18px; font-weight: 700; letter-spacing: .5px; }
    .a4-meta { margin-left: auto; text-align: right; font-size: 11px; }
    .a4-title { background: #111; color: #fff; font-weight: 700; font-size: 12px; padding: 3px 8px; margin: 12px 0 6px; letter-spacing: .5px; }
    .a4-table { width: 100%; border-collapse: collapse; }
    .a4-table th, .a4-table td { border: 1px solid #999; padding: 4px 6px; vertical-align: top; }
    .a4-table th { width: 34%; background: #f3f3f3; font-weight: 600; }
    .a4-table.grid th { width: auto; text-align: left; }
    .a4-photo { width: 30mm; height: 40mm; object-fit: cover; border: 1px solid #999; }
    .a4-photo.empty { display: flex; align-items: center; justify-content: center; color: #999; font-size: 10px; }
    .a4-sign { display: flex; justify-content: space-between; margin-top: 18mm; text-align: center; }
    .a4-sign div { width: 60mm; }
    .a4-sign .line { border-top: 1px solid #111; margin-top: 18mm; padding-top: 3px; }

    .a4-sheet .text-center { text-align: center; }
    .a4-sheet .mb-0 { margin-bottom: 0; }
    @media print {
        @page { size: A4; margin: 0; }
        body { margin: 0; }
        .a4-sheet { box-shadow: none; margin: 0; }
        .a4-title, .a4-table th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

@if (! $form)
    <div class="text-center text-muted py-5">
        <i class="bi bi-file-earmark-text" style="font-size:32px;"></i>
        <div class="mt-2">Form belum diisi siswa.</div>
    </div>
@else
    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-primary" data-print-application><i class="bi bi-printer"></i> Print / Save PDF</button>
    </div>

    <div style="overflow-x:auto;">
        <div class="a4-sheet" id="application-print">
            <div class="a4-head">
                <img src="{{ asset('frontend/img/Logo.png') }}" alt="INASTUDY" class="logo">
                <h4>APPLICATION FORM</h4>
                <div class="a4-meta">
                    <div><strong>{{ $application->application_no ?: '-' }}</strong></div>
                    <div>{{ $application->university->name ?? '-' }}</div>
                    <div>{{ $application->course_name ?: $application->major_label }}</div>
                </div>
            </div>

            <div class="a4-title">PERSONAL INFORMATION</div>
            <table class="a4-table">
                <tr>
                    <th>Surname</th><td>{{ $value($form->surname) }}</td>
                    <td rowspan="5" style="width:34mm; text-align:center; border:none; padding:0 0 0 6px;">
                        @if ($form->photo_path)
                            <img src="{{ asset($form->photo_path) }}" alt="Photo" class="a4-photo">
                        @else
                            <div class="a4-photo empty">No photo</div>
                        @endif
                    </td>
                </tr>
                <tr><th>Given Name</th><td>{{ $value($form->given_name) }}</td></tr>
                <tr><th>Chinese Name</th><td>{{ $value($form->chinese_name) }}</td></tr>
                <tr><th>Gender</th><td>{{ $value($form->gender) }}</td></tr>
                <tr><th>Nationality</th><td>{{ $value($form->nationality) }}</td></tr>
            </table>
            <table class="a4-table" style="margin-top:-1px;">
                <tr><th>Name on Passport</th><td>{{ $value($student->name_pasport ?? null) }}</td></tr>
                <tr><th>Passport No.</th><td>{{ $value(($student->number_pasport ?? null) ?: $form->passport_no) }}</td></tr>
                <tr><th>Passport Expiry Date</th><td>{{ $form->passport_expiry_date?->format('d M Y') ?? '-' }}</td></tr>
                <tr><th>Place, Date of Birth</th><td>{{ $value($form->place_of_birth) }}, {{ $form->date_of_birth?->format('d M Y') ?? '-' }}</td></tr>
                <tr><th>Telephone No.</th><td>{{ $value($form->telephone_no) }}</td></tr>
                <tr><th>E-mail Address</th><td>{{ $value($form->email) }}</td></tr>
                <tr><th>Religion</th><td>{{ $value($form->religion) }}</td></tr>
                <tr><th>Hobby</th><td>{{ $value($form->hobby) }}</td></tr>
                <tr><th>Home Address and Zipcode</th><td>{!! nl2br(e($value($form->home_address))) !!}</td></tr>
                <tr><th>Parents Name (Father &amp; Mother)</th><td>{{ $value($form->parents_name) }}</td></tr>
                <tr><th>Parents Phone Number</th><td>{{ $value($form->parents_phone) }}</td></tr>
                <tr><th>Parents Occupation</th><td>{{ $value($form->parents_occupation) }}</td></tr>
                <tr><th>Highest Degree Obtained</th><td>{{ $value($form->highest_degree_obtained) }}</td></tr>
                <tr><th>Field of Study in China</th><td>{{ $value($form->field_of_study_in_china) }}</td></tr>
                <tr><th>Financial Support Provided By</th><td>{{ $value($form->financial_support_by) }}</td></tr>
                <tr><th>Scholarship / Self Sponsored</th><td>{{ $sponsorships[$form->sponsorship_type] ?? $value($form->sponsorship_type) }}</td></tr>
            </table>

            <div class="a4-title">EDUCATION BACKGROUND</div>
            <table class="a4-table grid">
                <thead><tr><th style="width:24%">Level</th><th>School Name</th><th style="width:22%">Location</th><th style="width:16%">Year</th></tr></thead>
                <tbody>
                    @forelse ($application->educationBackgrounds as $row)
                        <tr>
                            <td>{{ $row->levelLabel() }}</td>
                            <td>{{ $value($row->school_name) }}</td>
                            <td>{{ $value($row->location) }}</td>
                            <td>{{ $value($row->year_start) }} - {{ $value($row->year_end) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">-</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="a4-title">DECLARATION</div>
            <p class="mb-0">
                The applicant has read, understood, and agreed to the INA STUDY Terms &amp; Conditions
                @if ($form->terms_accepted_at) on {{ $form->terms_accepted_at->format('d M Y H:i') }}@endif.
            </p>

            <div class="a4-sign">
                <div>Applicant<div class="line">{{ trim(($form->given_name ?? '').' '.($form->surname ?? '')) ?: '-' }}</div></div>
                <div>INA STUDY<div class="line">&nbsp;</div></div>
            </div>
        </div>
    </div>

    <script>
        // Print di jendela terpisah yang hanya berisi lembar A4, supaya sidebar & tab lain tidak ikut tercetak.
        document.querySelector('[data-print-application]').addEventListener('click', function () {
            var win = window.open('', '_blank');
            if (!win) { alert('Izinkan pop-up untuk mencetak form.'); return; }
            win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>' + @json($application->application_no ?: 'Application Form') + '</title><style>'
                + document.getElementById('a4-style').textContent + 'body{font-family:Arial,Helvetica,sans-serif;background:#fff}</style></head><body>'
                + document.getElementById('application-print').outerHTML + '</body></html>');
            win.document.close();
            win.onload = function () { win.focus(); win.print(); };
        });
    </script>
@endif
