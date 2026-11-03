<?php

namespace App\Http\Controllers\Student;

use App\Helpers\DataScope;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormQuestion;
use App\Models\FormSubmission;
use App\Models\Major;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu Students > Ina Visa (3 November 2026): daftar peserta yang mengisi form/quiz
 * berjenis "Ina Visa" (Edit Form > Jenis Form). Data TIDAK disalin ke tabel lain --
 * tetap dibaca dari form_submissions & form_answers, jadi semua fitur form (upload file,
 * pembayaran, batas peserta, WhatsApp, dll.) tetap berlaku seperti biasa. Halaman ini
 * hanya tampilan terpisah, read-only.
 *
 * Cakupan data sama dengan menu Quiz Form: form yang boleh dilihat user ini menurut
 * DataScope (branch/divisi/company role aktifnya).
 */
class InaVisaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user() ?? abort(401);
        $search = trim((string) $request->query('search'));
        $forms = $this->visibleForms($user)->orderBy('name')->get(['id', 'name', 'requires_payment']);
        $formId = $forms->contains('id', (string) $request->query('form')) ? (string) $request->query('form') : '';

        $submissions = FormSubmission::query()
            ->whereIn('form_id', $formId !== '' ? [$formId] : $forms->modelKeys())
            ->when($search !== '', fn (Builder $q) => $q->whereHas('student', fn (Builder $s) => $s->where(fn (Builder $w) => $w
                ->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$search}%"])
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('handphone', 'like', "%{$search}%"))))
            ->with(['student:id,first_name,last_name,email,handphone', 'form:id,name,requires_payment', 'payment'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('student.ina-visa.index', [
            'submissions' => $submissions,
            'files' => $this->fileAnswers($submissions->getCollection()->modelKeys()),
            'packages' => $this->packageAnswers($submissions->getCollection()->modelKeys()),
            'forms' => $forms,
            'formId' => $formId,
            'search' => $search,
        ]);
    }

    /** Isi modal "Jawaban" (fragmen HTML, dipanggil lewat fetch). Hanya submission form Ina Visa dalam cakupan user. */
    public function answers(Request $request, string $submissionId): View
    {
        $user = $request->user() ?? abort(401);

        $submission = FormSubmission::with(['student', 'form', 'result'])
            ->whereIn('form_id', $this->visibleForms($user)->select('id'))
            ->findOrFail($submissionId);

        $answersByQuestion = FormAnswer::where('submission_id', $submission->id)->with('option')->get()->groupBy('question_id');

        // Sama dengan FormController::submissionAnswers(): hanya pertanyaan yang benar-benar dijawab.
        $groupedAnswers = FormQuestion::where('form_id', $submission->form_id)
            ->orderBy('order')
            ->orderBy('created_at')
            ->get()
            ->map(fn (FormQuestion $question) => (object) [
                'question' => $question,
                'rows' => $answersByQuestion->get($question->id, collect()),
            ])
            ->filter(fn ($group) => $group->rows->isNotEmpty())
            ->values();

        $majorIds = $groupedAnswers
            ->filter(fn ($group) => $group->question->type === 'major')
            ->flatMap(fn ($group) => $group->rows->pluck('answer_text'))
            ->filter()
            ->unique()
            ->values();
        $majors = $majorIds->isNotEmpty() ? Major::whereIn('id', $majorIds)->pluck('name', 'id') : collect();

        return view('quiz.form._submission-answers', compact('submission', 'groupedAnswers', 'majors'));
    }

    /** Form berjenis Ina Visa yang boleh dilihat user ini (aturan cakupan sama dengan menu Quiz Form). */
    private function visibleForms(User $user): Builder
    {
        $query = Form::query()->where('form_type', Form::TYPE_INA_VISA);
        DataScope::applyBranchDivisionScope($query, $user, 'user_id');

        return $query;
    }

    /** Paket yang dipilih peserta (pertanyaan bertipe 'package'), dikelompokkan per submission. */
    private function packageAnswers(array $submissionIds)
    {
        if ($submissionIds === []) {
            return collect();
        }

        return FormAnswer::whereIn('submission_id', $submissionIds)
            ->whereNotNull('option_id')
            ->whereHas('question', fn (Builder $q) => $q->where('type', 'package'))
            ->with('option:id,option_text,price')
            ->get()
            ->groupBy('submission_id');
    }

    /**
     * File yang diunggah peserta (pertanyaan bertipe 'file', mis. paspor -- boleh lebih dari satu
     * pertanyaan), dikelompokkan per submission. Satu query untuk satu halaman tabel.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, FormAnswer>>
     */
    private function fileAnswers(array $submissionIds)
    {
        if ($submissionIds === []) {
            return collect();
        }

        return FormAnswer::whereIn('submission_id', $submissionIds)
            ->whereNotNull('answer_text')
            ->whereHas('question', fn (Builder $q) => $q->where('type', 'file'))
            ->with('question:id,question_text,order')
            ->get()
            ->sortBy(fn (FormAnswer $answer) => $answer->question->order ?? 0)
            ->groupBy('submission_id');
    }
}
