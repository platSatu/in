<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Services\Payment\Gateways\BbGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

/**
 * Company › Payment (hanya superadmin, lihat routes/web.php).
 *  - Tab "Riwayat Transaksi": pembayaran Quiz & Apply yang lewat gateway BB (dibaca dari tabel ina, hanya baca).
 *  - Tab "Penarikan": saldo, rekening & penarikan ke rekening, mutasi saldo. Semuanya dibaca/dikirim langsung
 *    ke BB lewat BbGateway::api(), ina tidak menyimpan salinan (tidak ada tabel baru, tidak bisa selisih).
 *
 * Pengaman uang keluar: role superadmin + permission kelola, wajib password akun, token sekali pakai (klik dobel /
 * kirim ulang tidak menarik dua kali: BB mengembalikan penarikan yang sama), throttle, dan batas di sisi BB.
 */
class CompanyPaymentController extends Controller
{
    private const STATUSES = ['pending', 'paid', 'failed', 'expired'];

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'payout' ? 'payout' : 'history';

        return view('company.payment.index', ['tab' => $tab] + ($tab === 'payout' ? $this->payout($request) : $this->history($request)));
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_code' => ['required', 'string', 'max:10', 'alpha_num'],
            'account_number' => ['required', 'string', 'regex:/^[0-9\s\-]{5,30}$/'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);
        $number = preg_replace('/\D/', '', $data['account_number']);

        try {
            // external_id tetap per rekening: mendaftarkan ulang rekening yang sama hanya memperbarui datanya.
            $account = (array) ($this->gateway()->api('POST', '/api/v1/payout-accounts', array_filter([
                'external_id' => 'INA-'.$data['bank_code'].'-'.$number,
                'bank_code' => $data['bank_code'],
                'account_number' => $number,
                'label' => $data['label'] ?? null,
            ]))['data'] ?? []);
        } catch (RuntimeException $e) {
            return $this->back($e, 'account');
        }

        Log::info('[PAYMENT][BB] Rekening penarikan didaftarkan', ['user_id' => $request->user()->id, 'account' => $account['id'] ?? null]);

        return redirect()->route('company.payment.index', ['tab' => 'payout'])
            ->with('success', 'Rekening '.($account['bank_name'] ?? '').' ****'.($account['account_last4'] ?? '').' a.n. '.($account['account_name'] ?? '-').' berhasil didaftarkan.');
    }

    public function storePayout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payout_account_id' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:\-]+$/'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'token' => ['required', 'uuid'],
            'password' => ['required', 'current_password'],
        ], ['password.current_password' => 'Password salah.']);

        try {
            $payout = (array) ($this->gateway()->api('POST', '/api/v1/payouts', [
                // Token dibuat saat form tampil: form yang terkirim dua kali tetap satu penarikan.
                'external_id' => 'INA-WD-'.$data['token'],
                'payout_account_id' => $data['payout_account_id'],
                'amount' => (int) $data['amount'],
                'description' => mb_substr('Penarikan ina oleh '.$request->user()->name, 0, 255),
            ])['data'] ?? []);
        } catch (RuntimeException $e) {
            return $this->back($e, 'payout');
        }

        Log::info('[PAYMENT][BB] Penarikan dibuat', ['user_id' => $request->user()->id, 'reference' => $payout['reference'] ?? null, 'amount' => $data['amount']]);

        return redirect()->route('company.payment.index', ['tab' => 'payout'])
            ->with('success', 'Penarikan '.($payout['reference'] ?? '').' sebesar Rp '.number_format((int) $data['amount'], 0, ',', '.').' sedang diproses. Status terbaru tampil di mutasi.');
    }

    /** Tab 1: transaksi Quiz & Apply lewat BB. */
    private function history(Request $request): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', self::STATUSES)],
            'module' => ['nullable', 'in:quiz,apply'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $columns = 'p.order_id, p.amount, p.status, p.payment_method, p.gateway_reference, p.paid_at, p.created_at';
        $quiz = DB::table('form_payments as p')->leftJoin('forms as f', 'f.id', '=', 'p.form_id')->where('p.gateway', 'bb')
            ->selectRaw("'quiz' as module, p.name as payer, p.email, f.name as item, {$columns}");
        $apply = DB::table('application_payments as p')->leftJoin('university_applications as a', 'a.id', '=', 'p.application_id')
            ->leftJoin('students as s', 's.id', '=', 'a.student_id')->where('p.gateway', 'bb')
            ->selectRaw("'apply' as module, TRIM(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, ''))) as payer, s.email, a.application_no as item, {$columns}");
        $search = trim((string) ($filters['search'] ?? ''));

        $query = DB::query()->fromSub($quiz->unionAll($apply), 't')
            ->when($filters['module'] ?? null, fn ($q, $module) => $q->where('module', $module))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('order_id', 'like', "%{$search}%")
                ->orWhere('payer', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($request->date('date_from'), fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('date_to'), fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()));

        $paid = (clone $query)->where('status', 'paid')->selectRaw('COUNT(*) as total, COALESCE(SUM(amount), 0) as amount')->first();

        return [
            'rows' => $query->orderByDesc('created_at')->paginate(15)->withQueryString(),
            'paid' => $paid,
        ];
    }

    /** Tab 2: saldo, rekening, penarikan (atas) & mutasi (bawah), langsung dari BB. */
    private function payout(Request $request): array
    {
        $filters = $request->validate([
            'direction' => ['nullable', 'in:credit,debit'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        try {
            $gateway = $this->gateway();
        } catch (RuntimeException $e) {
            return ['setupError' => $e->getMessage()];
        }

        // Tiap bagian dimuat terpisah: penarikan yang belum diaktifkan di BB tidak membuat saldo & mutasi ikut hilang.
        $load = function (callable $call) {
            try {
                return [$call(), null];
            } catch (RuntimeException $e) {
                return [null, $e->getMessage()];
            }
        };

        [$balance, $balanceError] = $load(fn () => $gateway->api('GET', '/api/v1/balance')['data'] ?? []);
        [$accounts, $accountsError] = $load(fn () => $gateway->api('GET', '/api/v1/payout-accounts'));
        [$banks] = $accountsError ? [[]] : $load(fn () => Cache::remember('bb:banks', now()->addDay(), fn () => $gateway->api('GET', '/api/v1/banks')['data'] ?? []));
        [$mutations, $mutationsError] = $load(fn () => $gateway->api('GET', '/api/v1/mutations', null, $filters + ['per_page' => 15]));

        $meta = $mutations['meta'] ?? [];

        return [
            'balance' => $balance,
            'balanceError' => $balanceError,
            'accounts' => collect($accounts['data'] ?? []),
            'rules' => $accounts['rules'] ?? null,
            'accountsError' => $accountsError,
            'banks' => collect($banks ?? []),
            'mutations' => new LengthAwarePaginator($mutations['data'] ?? [], (int) ($meta['total'] ?? 0), (int) ($meta['per_page'] ?? 15),
                (int) ($meta['current_page'] ?? 1), ['path' => $request->url(), 'query' => $request->query()]),
            'mutationsError' => $mutationsError,
        ];
    }

    /** Baris gateway BB di Settings › Payment Gateway (tidak harus yang aktif untuk pembayaran). */
    private function gateway(): BbGateway
    {
        $config = PaymentGateway::where('gateway', 'bb')->latest('updated_at')->first();

        if (! $config || empty($config->credentials['api_key'])) {
            throw new RuntimeException('Gateway BB belum diatur. Isi dulu di Settings › Payment Gateway.');
        }

        return new BbGateway($config);
    }

    private function back(RuntimeException $e, string $form): RedirectResponse
    {
        return redirect()->route('company.payment.index', ['tab' => 'payout'])
            // Token ikut dikembalikan: kalau koneksi putus padahal BB sudah menerima, kirim ulang tetap 1 penarikan.
            ->withInput(request()->except('password'))
            ->withErrors([$form => $e->getMessage()]);
    }
}
