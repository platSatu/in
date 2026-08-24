<?php

namespace App\Http\Controllers\Quiz;

use App\Helpers\AdminCrud;
use App\Helpers\DataScope;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormQuestion;
use App\Models\FormSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormSectionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $formId = $request->query('form_id');

        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        // SEBELUMNYA: selalu di-scope ketat `user_id = pembuatnya sendiri`.
        // SEKARANG: FormSection sendiri tidak punya kolom branch/divisi,
        // makanya cakupannya ikut Form induknya lewat form_id (lihat
        // App\Helpers\DataScope::visibleFormIds()) — section dari form yang
        // boleh dilihat user ini (sesuai role/branch/divisi), bukan cuma
        // section buatan sendiri.
        $visibleFormIds = DataScope::visibleFormIds($user);

        $query = FormSection::query()->with('form');

        if ($visibleFormIds !== null) {
            $query->whereIn('form_id', $visibleFormIds);
        }

        // Dipanggil dari tombol "Show Sections" di quiz/form/index.blade.php ->
        // langsung terfilter cuma section milik form itu saja. Pola sama persis
        // dengan filter form_id di FormQuestionController::index().
        $filterForm = null;

        if (!empty($formId)) {
            $filterForm = Form::where('id', $formId)
                ->when($visibleFormIds !== null, fn ($q) => $q->whereIn('id', $visibleFormIds))
                ->first();

            $query->where('form_id', $formId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $data = $query
            ->withCount(['questions as questions_count'])
            ->orderBy('order')
            ->orderBy('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('quiz.form-section.index', compact('data', 'filterForm'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        $forms = DataScope::applyBranchDivisionScope(Form::query(), $user, 'user_id')
            ->orderBy('name')
            ->get();

        $selectedFormId = $request->query('form_id');

        return view('quiz.form-section.create', compact('forms', 'selectedFormId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'form_id' => 'required|string|exists:forms,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        if (!$this->isFormAccessible($validated['form_id'])) {
            abort(403, 'Form tidak valid untuk cakupan akses Anda.');
        }

        $validated['user_id'] = (string) $userId;
        $validated['status'] = $validated['status'] ?? 'active';

        // Section baru selalu ditaruh di urutan paling akhir dari section-section
        // yang sudah ada di form ini — pola sama dengan `order` pertanyaan/opsi
        // di FormQuestionController::store() & FormQuestionOptionController::store().
        $existingCount = FormSection::where('form_id', $validated['form_id'])->count();
        $validated['order'] = $existingCount > 0
            ? ((int) FormSection::where('form_id', $validated['form_id'])->max('order')) + 1
            : 0;

        AdminCrud::create(FormSection::class, $validated);

        return redirect()
            ->route('quiz.form-section.index', ['form_id' => $validated['form_id']])
            ->with('success', 'Section berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $user = Auth::user();
        if ($user === null) {
            abort(401);
        }

        $data = $this->resolveVisibleSection($id);

        $forms = DataScope::applyBranchDivisionScope(Form::query(), $user, 'user_id')
            ->orderBy('name')
            ->get();

        return view('quiz.form-section.edit', compact('data', 'forms'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $this->resolveVisibleSection($id);

        $validated = $request->validate([
            'form_id' => 'required|string|exists:forms,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        if (!$this->isFormAccessible($validated['form_id'])) {
            abort(403, 'Form tidak valid untuk cakupan akses Anda.');
        }

        $validated['order'] = $validated['order'] ?? 0;

        AdminCrud::update(FormSection::class, $id, $validated, null);

        return redirect()
            ->route('quiz.form-section.index')
            ->with('success', 'Section berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        if (Auth::id() === null) {
            abort(401);
        }

        /** @var FormSection $existing */
        $existing = $this->resolveVisibleSection($id);

        // Section ini bisa saja masih punya pertanyaan yang menempel (section_id).
        // Kalau dihapus begitu saja, pertanyaan itu tidak hilang (section_id
        // memang nullable) tapi admin bisa kebingungan kenapa section-nya
        // mendadak tidak ada — diblokir dulu di sini, pola sama dengan
        // FormQuestionOptionController::destroy() yang memblokir hapus opsi
        // yang masih punya pertanyaan cabang.
        $hasQuestions = FormQuestion::where('section_id', $existing->id)
            ->where('status', 'active')
            ->exists();

        if ($hasQuestions) {
            return redirect()
                ->route('quiz.form-section.index')
                ->withErrors(['delete' => 'Section ini masih memiliki pertanyaan di dalamnya. Pindahkan atau hapus dulu pertanyaannya sebelum menghapus section ini.']);
        }

        AdminCrud::delete(FormSection::class, $id, null);

        return redirect()
            ->route('quiz.form-section.index')
            ->with('success', 'Section berhasil dihapus.');
    }

    /**
     * Section yang boleh diakses user ini, ditentukan lewat cakupan Form
     * induknya (lihat App\Helpers\DataScope::visibleFormIds()) — dipakai
     * menggantikan AdminCrud::findOrFail(FormSection::class, $id, $userId)
     * yang sebelumnya scope ketat "punya sendiri".
     */
    private function resolveVisibleSection(string $id): FormSection
    {
        $section = FormSection::with('form')->findOrFail($id);

        if (!$this->isFormAccessible($section->form_id)) {
            abort(403, 'Section tidak valid untuk cakupan akses Anda.');
        }

        return $section;
    }

    private function isFormAccessible(?string $formId): bool
    {
        if (!$formId) {
            return false;
        }

        $visibleFormIds = DataScope::visibleFormIds(Auth::user());

        return $visibleFormIds === null || in_array($formId, $visibleFormIds, true);
    }
}
