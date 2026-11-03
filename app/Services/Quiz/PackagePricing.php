<?php

namespace App\Services\Quiz;

use App\Models\Form;
use App\Models\FormQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Pilihan Paket (pertanyaan bertipe 'package'): tiap pilihan punya harga. Tagihan pembayaran
 * = nominal tetap form (payment_amount, boleh 0) + jumlah harga pilihan yang dipilih peserta.
 *
 * Harga SELALU dihitung di server dari database -- tidak pernah dari angka yang dikirim browser.
 * Pilihan yang dibayar disimpan di form_payments.package_option_ids dan dicocokkan lagi saat
 * submit (lihat FrontendController), supaya peserta tidak bisa membayar paket murah lalu
 * mengirim pilihan paket yang lebih mahal.
 *
 * Form tanpa pertanyaan bertipe package: hasPackages() false, alur pembayaran lama tidak berubah.
 */
class PackagePricing
{
    /** Pertanyaan paket aktif (bukan pertanyaan cabang) milik form, beserta pilihannya. */
    public function questions(Form $form): Collection
    {
        return FormQuestion::where('form_id', $form->id)
            ->where('type', 'package')
            ->where('status', 'active')
            ->whereNull('parent_option_id')
            ->with('options')
            ->orderBy('order')
            ->orderBy('created_at')
            ->get();
    }

    public function hasPackages(Form $form): bool
    {
        return FormQuestion::where('form_id', $form->id)->where('type', 'package')->where('status', 'active')->whereNull('parent_option_id')->exists();
    }

    /**
     * Validasi pilihan peserta lalu hitung tagihannya. Tiap pertanyaan paket wajib dipilih tepat
     * satu pilihan aktif miliknya; id lain (milik pertanyaan/form lain) ditolak.
     *
     * @param  array<int, string>  $optionIds
     * @return array{amount: float, option_ids: array<int, string>, labels: array<int, string>}
     *
     * @throws InvalidArgumentException  pesan siap ditampilkan ke peserta
     */
    public function quote(Form $form, array $optionIds): array
    {
        $optionIds = array_values(array_unique(array_filter(array_map('strval', $optionIds))));
        $questions = $this->questions($form);
        $chosen = [];

        foreach ($questions as $question) {
            $picked = $question->options->whereIn('id', $optionIds)->values();

            if ($picked->count() !== 1) {
                throw new InvalidArgumentException('Pilih satu paket untuk "' . ($question->question_text ?: 'Pilihan Paket') . '".');
            }

            $chosen[] = $picked->first();
        }

        if (count($chosen) !== count($optionIds)) {
            throw new InvalidArgumentException('Pilihan paket tidak valid. Muat ulang halaman lalu pilih lagi.');
        }

        $amount = (float) ($form->payment_amount ?? 0) + array_sum(array_map(fn ($option) => (float) ($option->price ?? 0), $chosen));

        return [
            'amount' => round($amount, 2),
            'option_ids' => $this->normalize(array_map(fn ($option) => (string) $option->id, $chosen)),
            'labels' => array_map(fn ($option) => (string) $option->option_text, $chosen),
        ];
    }

    /** Pilihan paket yang dikirim bersama submit (input question_{id} tiap pertanyaan paket). */
    public function selectedFromRequest(Form $form, Request $request): array
    {
        return $this->normalize($this->questions($form)
            ->map(fn (FormQuestion $question) => (string) $request->input('question_' . $question->id))
            ->filter()
            ->all());
    }

    /** Sama atau tidak dengan pilihan yang tercatat di pembayaran (urutan tidak berpengaruh). */
    public function matchesPayment(array $selected, ?array $paid): bool
    {
        return $this->normalize($selected) === $this->normalize($paid ?? []);
    }

    private function normalize(array $ids): array
    {
        $ids = array_values(array_unique(array_map('strval', $ids)));
        sort($ids);

        return $ids;
    }
}
