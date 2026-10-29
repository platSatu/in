<?php

namespace App\Services\Dashboard;

use App\Helpers\DataScope;
use App\Models\ApplicationPayment;
use App\Models\ClassSession;
use App\Models\CoursePackagePurchase;
use App\Models\FormPayment;
use App\Models\Student;
use App\Models\UniversityApplication;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Angka & data chart untuk Dashboard (tab "Chart") semua role selain student
 * (29 Oktober 2026).
 *
 * Aturan akses SAMA dengan menu aslinya, supaya dashboard tidak membuka data
 * yang di menunya sendiri tidak boleh dilihat:
 *   - Student & leads   : izin 'student.student', cakupan DataScope (company/branch/divisi/self).
 *   - Aplikasi kampus   : izin 'quiz.university-application', hanya student yang terlihat (visibleStudentIds).
 *   - Pendapatan        : per sumber, masing-masing ikut izin & cakupan menunya:
 *                         biaya aplikasi (quiz.university-application), paket kursus (course.report,
 *                         sama seperti menu Laporan = seluruh perusahaan), form berbayar (quiz.form).
 *   - Kelas & sesi      : izin 'class-session', dibatasi cabang (visibleBranchIds).
 * Bagian yang tidak boleh dilihat bernilai null dan tidak ditampilkan.
 */
class DashboardStats
{
    public const PERIOD_OPTIONS = [3, 6, 12];

    public const DEFAULT_PERIOD = 6;

    public const APPLICATION_STATUS_LABELS = [
        UniversityApplication::STATUS_SUBMITTED => 'Submitted',
        UniversityApplication::STATUS_DOCUMENTS_REVIEW => 'Review Dokumen',
        UniversityApplication::STATUS_REGISTERED => 'Registered',
        UniversityApplication::STATUS_VISA_PROCESS => 'Proses Visa',
        UniversityApplication::STATUS_CHECK_IN => 'Check-in',
        UniversityApplication::STATUS_COMPLETED => 'Selesai',
        UniversityApplication::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public const SESSION_STATUS_LABELS = [
        ClassSession::STATUS_WAITING_TEACHER => 'Menunggu Pengajar',
        ClassSession::STATUS_WAITING_ADMIN => 'Menunggu Admin',
        ClassSession::STATUS_APPROVED => 'Disetujui',
        ClassSession::STATUS_REJECTED_BY_TEACHER => 'Ditolak Pengajar',
        ClassSession::STATUS_REJECTED_BY_ADMIN => 'Ditolak Admin',
        ClassSession::STATUS_REFUNDED => 'Direfund',
        ClassSession::STATUS_CANCELLED_BY_STUDENT => 'Dibatalkan Siswa',
    ];

    private readonly CarbonImmutable $start;

    private readonly CarbonImmutable $monthStart;

    /** Cache visibleStudentIds (dipakai dua bagian). */
    private ?array $studentIds = null;

    private bool $studentIdsLoaded = false;

    public function __construct(private readonly User $user, private readonly int $months = self::DEFAULT_PERIOD)
    {
        $this->monthStart = CarbonImmutable::now()->startOfMonth();
        $this->start = $this->monthStart->subMonths(max(1, $this->months) - 1);
    }

    public static function normalizePeriod(mixed $months): int
    {
        $months = (int) $months;

        return in_array($months, self::PERIOD_OPTIONS, true) ? $months : self::DEFAULT_PERIOD;
    }

    /** Semua bagian yang boleh dilihat user ini (bagian null dibuang). */
    public function sections(): array
    {
        return array_filter([
            'students' => $this->students(),
            'applications' => $this->applications(),
            'revenue' => $this->revenue(),
            'classes' => $this->classes(),
        ]);
    }

    /** Label bulan sumbu X, mis. "Mei 26". */
    public function monthLabels(): array
    {
        return array_values(array_map(
            fn (string $ym) => CarbonImmutable::createFromFormat('Y-m-d', $ym.'-01')->locale('id')->translatedFormat('M y'),
            $this->monthKeys()
        ));
    }

    /** @return array<int, string> ['2026-05', '2026-06', ...] */
    public function monthKeys(): array
    {
        $keys = [];
        for ($m = $this->start; $m->lte($this->monthStart); $m = $m->addMonth()) {
            $keys[] = $m->format('Y-m');
        }

        return $keys;
    }

    public function students(): ?array
    {
        if (! $this->user->canAccessPermission('student.student')) {
            return null;
        }

        $base = fn () => DataScope::applyBranchDivisionScope(Student::query(), $this->user, 'handled_by_user_id');

        $progress = $base()->toBase()
            ->selectRaw('progress_student as k, COUNT(*) as total')
            ->groupBy('progress_student')
            ->pluck('total', 'k');

        $progressData = [];
        foreach (Student::PROGRESS_LABELS as $key => $label) {
            $progressData[$label] = (int) ($progress[$key] ?? 0);
        }
        // Kosong (null) & nilai lama di luar daftar dihitung "Belum diisi".
        $progressData['Belum diisi'] = (int) $progress
            ->reject(fn ($v, $k) => array_key_exists((string) $k, Student::PROGRESS_LABELS))
            ->sum();

        $branches = $base()->toBase()
            ->leftJoin('company_branch', 'company_branch.id', '=', 'students.branch_id')
            ->selectRaw("COALESCE(company_branch.name, 'Tanpa branch') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'label');

        return [
            'kpi' => [
                'total' => $base()->count(),
                'this_month' => $base()->where('students.created_at', '>=', $this->monthStart)->count(),
                'paid' => $base()->where('progress_student', Student::PROGRESS_PAID)->count(),
            ],
            'per_month' => $this->perMonth($base(), 'students.created_at'),
            'progress' => $this->toChart(collect($progressData)->filter()),
            'branches' => $this->toChart($branches),
        ];
    }

    public function applications(): ?array
    {
        if (! $this->user->canAccessPermission('quiz.university-application')) {
            return null;
        }

        $base = fn () => $this->scopeToVisibleStudents(UniversityApplication::query(), 'university_applications.student_id');

        $statuses = $base()->toBase()
            ->selectRaw('status as k, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'k');

        $statusData = [];
        foreach (self::APPLICATION_STATUS_LABELS as $key => $label) {
            $statusData[$label] = (int) ($statuses[$key] ?? 0);
        }

        $universities = $base()->toBase()
            ->where('university_applications.status', '!=', UniversityApplication::STATUS_CANCELLED)
            ->leftJoin('universities', 'universities.id', '=', 'university_applications.university_id')
            ->selectRaw("COALESCE(universities.name, 'Lainnya') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'label');

        $closed = [UniversityApplication::STATUS_COMPLETED, UniversityApplication::STATUS_CANCELLED];

        return [
            'kpi' => [
                'active' => $base()->whereNotIn('status', $closed)->count(),
                'this_month' => $base()->where('university_applications.created_at', '>=', $this->monthStart)->count(),
                'completed' => $base()->where('status', UniversityApplication::STATUS_COMPLETED)->count(),
            ],
            'per_month' => $this->perMonth($base(), 'university_applications.created_at'),
            'status' => $this->toChart(collect($statusData)->filter()),
            'universities' => $this->toChart($universities),
        ];
    }

    public function revenue(): ?array
    {
        $series = [];

        if ($this->user->canAccessPermission('quiz.university-application')) {
            $applicationPaid = fn () => ApplicationPayment::query()
                ->where('application_payments.status', ApplicationPayment::STATUS_PAID)
                ->whereHas('application', fn (Builder $q) => $this->scopeToVisibleStudents($q, 'student_id'));

            $series['Biaya Pendaftaran Kampus'] = $this->perMonth(
                $applicationPaid()->where('purpose', ApplicationPayment::PURPOSE_REGISTRATION_FEE),
                'application_payments.paid_at',
                'application_payments.amount'
            );
            $series['Biaya Keberangkatan'] = $this->perMonth(
                $applicationPaid()->where('purpose', ApplicationPayment::PURPOSE_DEPARTURE_FEE),
                'application_payments.paid_at',
                'application_payments.amount'
            );
        }

        if ($this->user->canAccessPermission('course.report')) {
            // Sama dengan menu Laporan: pembelian paket selesai, tanpa klaim trial gratis.
            $series['Paket Kursus'] = $this->perMonth(
                CoursePackagePurchase::query()
                    ->where('status', CoursePackagePurchase::STATUS_COMPLETED)
                    ->where('source', '!=', CoursePackagePurchase::SOURCE_TRIAL_CLAIM),
                'course_package_purchases.created_at',
                'course_package_purchases.price_paid'
            );
        }

        if ($this->user->canAccessPermission('quiz.form')) {
            $formIds = DataScope::visibleFormIds($this->user);
            $series['Form Berbayar'] = $this->perMonth(
                FormPayment::query()
                    ->where('status', 'paid')
                    ->when($formIds !== null, fn (Builder $q) => $q->whereIn('form_id', $formIds)),
                'form_payments.paid_at',
                'form_payments.amount'
            );
        }

        if ($series === []) {
            return null;
        }

        // Sumber tanpa pemasukan sama sekali di periode ini tidak ditampilkan,
        // supaya warna chart batang & donut "Per sumber" tetap sama urutannya.
        $nonEmpty = array_filter($series, fn (array $values) => array_sum($values) > 0);
        if ($nonEmpty !== []) {
            $series = $nonEmpty;
        }

        $lastIndex = count($this->monthKeys()) - 1;
        $thisMonth = array_sum(array_map(fn (array $values) => $values[$lastIndex] ?? 0, $series));
        $period = array_sum(array_map('array_sum', $series));

        return [
            'kpi' => [
                'this_month' => $thisMonth,
                'period' => $period,
            ],
            'series' => collect($series)->map(fn ($data, $name) => ['name' => $name, 'data' => $data])->values()->all(),
            'by_source' => $this->toChart(collect($series)->map(fn ($values) => array_sum($values))->filter()),
        ];
    }

    public function classes(): ?array
    {
        if (! $this->user->canAccessPermission('class-session')) {
            return null;
        }

        $branchIds = $this->user->visibleBranchIds();
        $base = fn () => ClassSession::query()
            ->when($branchIds !== null, fn (Builder $q) => $q->whereIn('class_sessions.branch_id', $branchIds));

        $statuses = $base()->toBase()
            ->selectRaw('status as k, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'k');

        $statusData = [];
        foreach (self::SESSION_STATUS_LABELS as $key => $label) {
            $statusData[$label] = (int) ($statuses[$key] ?? 0);
        }

        $approved = fn () => $base()->where('status', ClassSession::STATUS_APPROVED);

        return [
            'kpi' => [
                'pending' => $base()->whereIn('status', [ClassSession::STATUS_WAITING_TEACHER, ClassSession::STATUS_WAITING_ADMIN])->count(),
                'approved_this_month' => $approved()->where('admin_approved_at', '>=', $this->monthStart)->count(),
                'credits_this_month' => (float) $approved()->where('admin_approved_at', '>=', $this->monthStart)->sum('credit_amount_final'),
            ],
            'per_month' => [
                ['name' => 'Pengajuan', 'data' => $this->perMonth($base(), 'class_sessions.created_at')],
                ['name' => 'Disetujui', 'data' => $this->perMonth($approved(), 'class_sessions.admin_approved_at')],
            ],
            'status' => $this->toChart(collect($statusData)->filter()),
        ];
    }

    /**
     * Jumlah (COUNT) atau total (SUM $sumColumn) per bulan dalam periode, bulan kosong = 0.
     *
     * @return array<int, float|int>
     */
    private function perMonth(Builder $query, string $dateColumn, ?string $sumColumn = null): array
    {
        $aggregate = $sumColumn ? "COALESCE(SUM({$sumColumn}), 0)" : 'COUNT(*)';

        $rows = $query->toBase()
            ->whereNotNull($dateColumn)
            ->where($dateColumn, '>=', $this->start)
            ->selectRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') as ym, {$aggregate} as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return array_map(
            fn (string $ym) => $sumColumn ? (float) ($rows[$ym] ?? 0) : (int) ($rows[$ym] ?? 0),
            $this->monthKeys()
        );
    }

    private function scopeToVisibleStudents(Builder $query, string $column): Builder
    {
        if (! $this->studentIdsLoaded) {
            $this->studentIds = DataScope::visibleStudentIds($this->user);
            $this->studentIdsLoaded = true;
        }

        return $this->studentIds === null ? $query : $query->whereIn($column, $this->studentIds);
    }

    /** @return array{labels: array<int, string>, values: array<int, float|int>} */
    private function toChart(iterable $pairs): array
    {
        $pairs = collect($pairs);

        return [
            'labels' => $pairs->keys()->map(fn ($k) => (string) $k)->values()->all(),
            'values' => $pairs->values()->map(fn ($v) => is_numeric($v) ? $v + 0 : 0)->all(),
        ];
    }
}
