@extends('layouts.frontend')
@section('content')

{{--
    Halaman "InaYule" (modul kursus Mandarin: Buy Packages / History /
    Schedule).

    STEP 1 (15 September 2026, permintaan user): baru TAMPILANNYA saja --
    3 tab di bawah ini SEMUA sengaja masih placeholder "Data not found",
    belum ada query/data apapun yang dikirim dari InaYuleController. Data
    model di baliknya (kepemilikan package per student, jadwal + pengajar,
    dst) masih tahap diskusi konsep, belum diputuskan strukturnya -- jangan
    isi tab-tab ini dengan data sungguhan sebelum ada keputusan lanjutan.

    Pola widget (widget-content-area br-8) & style tabel disamakan dengan
    resources/views/student-portal/inastudy/index.blade.php supaya
    konsisten satu payung produk InaStudy/InaYule.
--}}

<div class="row">
    <div class="col-12">
        <div class="widget-content widget-content-area br-8 mb-4">
            <h4 class="mb-3">InaYule</h4>

            <div style="overflow-x:auto;">
                <ul class="nav nav-tabs flex-nowrap text-nowrap" id="inayuleTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="inayule-buy-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-buy" type="button" role="tab"
                            aria-controls="inayule-buy" aria-selected="true">Buy Packages</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inayule-history-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-history" type="button" role="tab"
                            aria-controls="inayule-history" aria-selected="false">History</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inayule-schedule-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-schedule" type="button" role="tab"
                            aria-controls="inayule-schedule" aria-selected="false">Schedule</button>
                    </li>
                </ul>
            </div>

            <div class="tab-content pt-4" id="inayuleTabContent">
                {{-- Tab 1: Buy Packages --}}
                <div class="tab-pane fade show active" id="inayule-buy" role="tabpanel"
                    aria-labelledby="inayule-buy-tab">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="text-center text-muted py-5">
                                <div>Data not found</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tab 2: History --}}
                <div class="tab-pane fade" id="inayule-history" role="tabpanel"
                    aria-labelledby="inayule-history-tab">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Tanggal Beli</th>
                                    <th>Credits</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Data not found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tab 3: Schedule --}}
                <div class="tab-pane fade" id="inayule-schedule" role="tabpanel"
                    aria-labelledby="inayule-schedule-tab">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Pengajar</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Data not found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
