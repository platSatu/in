{{--
    Tab "Chart" Dashboard (29 Oktober 2026) -- hanya untuk role selain student.
    Data dari App\Services\Dashboard\DashboardStats; setiap bagian hanya ada
    kalau user punya izin menu terkait, dan angkanya mengikuti cakupan data
    (branch/divisi/self) yang sama dengan menu aslinya.
--}}
<div class="dash-charts">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-0">Ringkasan</h4>
            <small class="text-muted">{{ $monthLabels[0] ?? '' }} &ndash; {{ end($monthLabels) ?: '' }} &middot; sesuai hak akses Anda</small>
        </div>
        <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2">
            <label for="dash-months" class="small text-muted mb-0">Periode</label>
            <select id="dash-months" name="months" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                @foreach ($periodOptions as $option)
                    <option value="{{ $option }}" @selected($option === $period)>{{ $option }} bulan terakhir</option>
                @endforeach
            </select>
        </form>
    </div>

    @isset($sections['students'])
        @php($s = $sections['students'])
        <div class="widget-content widget-content-area br-8 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-people"></i> Student &amp; Leads</h5>
                <a href="{{ route('student.student.index') }}" class="small">Lihat Data Student &rarr;</a>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-4"><div class="dash-kpi"><span>Total student</span><strong>{{ number_format($s['kpi']['total'], 0, ',', '.') }}</strong></div></div>
                <div class="col-6 col-md-4"><div class="dash-kpi"><span>Baru bulan ini</span><strong>{{ number_format($s['kpi']['this_month'], 0, ',', '.') }}</strong></div></div>
                <div class="col-12 col-md-4"><div class="dash-kpi"><span>Sudah bayar</span><strong>{{ number_format($s['kpi']['paid'], 0, ',', '.') }}</strong></div></div>
            </div>
            <div class="row g-3">
                <div class="col-lg-8"><h6 class="dash-chart-title">Student baru per bulan</h6><div id="chart-students-month" class="dash-chart"></div></div>
                <div class="col-lg-4"><h6 class="dash-chart-title">Progress follow up</h6><div id="chart-students-progress" class="dash-chart"></div></div>
                <div class="col-12"><h6 class="dash-chart-title">Student per branch</h6><div id="chart-students-branch" class="dash-chart"></div></div>
            </div>
        </div>
    @endisset

    @isset($sections['applications'])
        @php($a = $sections['applications'])
        <div class="widget-content widget-content-area br-8 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-mortarboard"></i> Aplikasi Universitas</h5>
                <a href="{{ route('quiz.university-application.index') }}" class="small">Lihat Aplikasi &rarr;</a>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-4"><div class="dash-kpi"><span>Sedang berjalan</span><strong>{{ number_format($a['kpi']['active'], 0, ',', '.') }}</strong></div></div>
                <div class="col-6 col-md-4"><div class="dash-kpi"><span>Baru bulan ini</span><strong>{{ number_format($a['kpi']['this_month'], 0, ',', '.') }}</strong></div></div>
                <div class="col-12 col-md-4"><div class="dash-kpi"><span>Selesai</span><strong>{{ number_format($a['kpi']['completed'], 0, ',', '.') }}</strong></div></div>
            </div>
            <div class="row g-3">
                <div class="col-lg-8"><h6 class="dash-chart-title">Aplikasi baru per bulan</h6><div id="chart-apps-month" class="dash-chart"></div></div>
                <div class="col-lg-4"><h6 class="dash-chart-title">Status aplikasi</h6><div id="chart-apps-status" class="dash-chart"></div></div>
                <div class="col-12"><h6 class="dash-chart-title">Top 5 universitas (tanpa yang dibatalkan)</h6><div id="chart-apps-univ" class="dash-chart"></div></div>
            </div>
        </div>
    @endisset

    @isset($sections['classes'])
        @php($c = $sections['classes'])
        <div class="widget-content widget-content-area br-8 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-easel"></i> Kelas &amp; Sesi</h5>
                <a href="{{ route('class-session.index') }}" class="small">Lihat Approval Credit &rarr;</a>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-4"><div class="dash-kpi {{ $c['kpi']['pending'] > 0 ? 'dash-kpi-warn' : '' }}"><span>Menunggu approval</span><strong>{{ number_format($c['kpi']['pending'], 0, ',', '.') }}</strong></div></div>
                <div class="col-6 col-md-4"><div class="dash-kpi"><span>Disetujui bulan ini</span><strong>{{ number_format($c['kpi']['approved_this_month'], 0, ',', '.') }}</strong></div></div>
                <div class="col-12 col-md-4"><div class="dash-kpi"><span>Credit terpakai bulan ini</span><strong>{{ rtrim(rtrim(number_format($c['kpi']['credits_this_month'], 2, ',', '.'), '0'), ',') }}</strong></div></div>
            </div>
            <div class="row g-3">
                <div class="col-lg-8"><h6 class="dash-chart-title">Sesi per bulan</h6><div id="chart-classes-month" class="dash-chart"></div></div>
                <div class="col-lg-4"><h6 class="dash-chart-title">Status sesi</h6><div id="chart-classes-status" class="dash-chart"></div></div>
            </div>
        </div>
    @endisset
</div>

<style>
.dash-kpi { border: 1px solid #e0e6ed; border-radius: 8px; padding: 12px 14px; background: #fafbfc; height: 100%; }
.dash-kpi span { display: block; font-size: 12px; color: #6c757d; }
.dash-kpi strong { display: block; font-size: 22px; line-height: 1.3; color: #0e1726; word-break: break-word; }
.dash-kpi-warn { border-color: #f5c26b; background: #fff8eb; }
.dash-chart-title { font-size: 13px; font-weight: 600; color: #515365; margin-bottom: 4px; }
.dash-chart { min-height: 260px; }
.dash-empty { min-height: 200px; display: flex; align-items: center; justify-content: center; color: #9aa0a6; font-size: 13px; border: 1px dashed #e0e6ed; border-radius: 8px; }
@media (max-width: 576px) { .dash-kpi strong { font-size: 18px; } }
</style>

<script src="{{ asset('frontend/src/plugins/src/apex/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') { return; }

    const months = @json($monthLabels);
    const sections = @json($sections);
    const palette = ['#c8102e', '#4361ee', '#00ab55', '#e2a03f', '#805dca', '#2196f3', '#e7515a', '#888ea8'];
    const num = new Intl.NumberFormat('id-ID');
    const base = { fontFamily: 'Nunito, sans-serif', toolbar: { show: false }, zoom: { enabled: false } };

    function hasData(values) { return (values || []).some(v => Number(v) > 0); }
    function empty(id) {
        const el = document.getElementById(id);
        if (el) { el.innerHTML = '<div class="dash-empty">Belum ada data pada periode ini</div>'; }
    }
    function render(id, options, values) {
        const el = document.getElementById(id);
        if (!el) return;
        if (!hasData(values)) { empty(id); return; }
        new ApexCharts(el, options).render();
    }
    function columns(id, series, opts = {}) {
        render(id, {
            chart: { ...base, type: 'bar', height: 280, stacked: !!opts.stacked },
            series: series,
            xaxis: { categories: months },
            colors: opts.colors || palette,
            plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
            dataLabels: { enabled: false },
            legend: { position: 'top', show: series.length > 1 },
            yaxis: { labels: { formatter: v => num.format(Math.round(v)) } },
            tooltip: { y: { formatter: v => num.format(v) } },
            grid: { borderColor: '#eef0f3' },
        }, series.flatMap(s => s.data));
    }
    function donut(id, data, opts = {}) {
        render(id, {
            chart: { ...base, type: 'donut', height: 280 },
            series: data.values,
            labels: data.labels,
            colors: opts.colors || palette,
            legend: { position: 'bottom' },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: v => num.format(v) } },
            plotOptions: { pie: { donut: { size: '62%' } } },
        }, data.values);
    }
    function hbar(id, data, color) {
        render(id, {
            chart: { ...base, type: 'bar', height: Math.max(180, data.labels.length * 38 + 40) },
            series: [{ name: 'Jumlah', data: data.values }],
            xaxis: { categories: data.labels, labels: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { maxWidth: 200 } },
            tooltip: { y: { formatter: v => num.format(v) } },
            colors: [color],
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
            dataLabels: { enabled: true, formatter: v => num.format(v), style: { fontSize: '11px' } },
            grid: { borderColor: '#eef0f3' },
        }, data.values);
    }

    if (sections.students) {
        columns('chart-students-month', [{ name: 'Student baru', data: sections.students.per_month }], { colors: ['#c8102e'] });
        donut('chart-students-progress', sections.students.progress);
        hbar('chart-students-branch', sections.students.branches, '#4361ee');
    }
    if (sections.applications) {
        columns('chart-apps-month', [{ name: 'Aplikasi baru', data: sections.applications.per_month }], { colors: ['#4361ee'] });
        donut('chart-apps-status', sections.applications.status);
        hbar('chart-apps-univ', sections.applications.universities, '#00ab55');
    }
    if (sections.classes) {
        columns('chart-classes-month', sections.classes.per_month, { colors: ['#e2a03f', '#00ab55'] });
        donut('chart-classes-status', sections.classes.status);
    }
});
</script>
