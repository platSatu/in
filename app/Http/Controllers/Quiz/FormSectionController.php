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
use Illuminate\Support\Facades\Validator;

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

        $query = FormSection::query()->with(['form', 'parentSection']);

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

        // Daftar Section top-level (calon "induk") yang bisa dipilih sebagai
        // Parent Section — lihat docblock isValidParentSection(). Section
        // baru selalu boleh jadi Sub Section dari section top-level manapun
        // di form yang sama (validasi form_id yang sama dicek ulang di
        // server saat submit, dropdown ini cuma bantuan tampilan).
        $topLevelSections = $this->queryTopLevelSections();

        return view('quiz.form-section.create', compact('forms', 'selectedFormId', 'topLevelSections'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'form_id' => 'required|string|exists:forms,id',
            'parent_section_id' => 'nullable|string|exists:form_sections,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validator->after(function ($validator) use ($request) {
            $formId = $request->input('form_id');
            $parentSectionId = $request->input('parent_section_id') ?: null;

            if ($parentSectionId !== null && !$this->isValidParentSection($parentSectionId, $formId)) {
                $validator->errors()->add(
                    'parent_section_id',
                    'Parent Section tidak valid — harus Section top-level milik form yang sama (hierarki dibatasi 2 level).'
                );
            }
        });

        $validated = $validator->validate();
        $validated['parent_section_id'] = $validated['parent_section_id'] ?? null;

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

        // Section ini sendiri tidak boleh muncul di pilihan Parent Section-nya
        // sendiri (tidak boleh jadi induk diri sendiri).
        $topLevelSections = $this->queryTopLevelSections()->reject(fn (FormSection $s) => $s->id === $data->id)->values();

        return view('quiz.form-section.edit', compact('data', 'forms', 'topLevelSections'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        /** @var FormSection $existing */
        $existing = $this->resolveVisibleSection($id);

        $validator = Validator::make($request->all(), [
            'form_id' => 'required|string|exists:forms,id',
            'parent_section_id' => 'nullable|string|exists:form_sections,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validator->after(function ($validator) use ($request, $existing) {
            $formId = $request->input('form_id');
            $parentSectionId = $request->input('parent_section_id') ?: null;

            if ($parentSectionId === null) {
                return;
            }

            if ($parentSectionId === $existing->id) {
                $validator->errors()->add('parent_section_id', 'Section tidak boleh menjadi induk dirinya sendiri.');

                return;
            }

            if (!$this->isValidParentSection($parentSectionId, $formId, $existing->id)) {
                $validator->errors()->add(
                    'parent_section_id',
                    'Parent Section tidak valid — harus Section top-level milik form yang sama (hierarki dibatasi 2 level).'
                );

                return;
            }

            // Section ini sendiri sudah punya Sub Section (dipakai sebagai
            // parent oleh section lain) — tidak boleh sekaligus jadi Sub
            // Section dari section lain (akan membentuk hierarki 3 level).
            if ($this->hasChildSections($existing->id)) {
                $validator->errors()->add(
                    'parent_section_id',
                    'Section ini sudah memiliki Sub Section sendiri, jadi tidak bisa dijadikan Sub Section dari Section lain.'
                );
            }
        });

        $validated = $validator->validate();
        $validated['parent_section_id'] = $validated['parent_section_id'] ?? null;

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

        // Section ini bisa saja masih punya Sub Section di bawahnya (hierarki
        // 2 level) — sama alasannya dengan pengecekan pertanyaan di atas,
        // supaya Sub Section tidak jadi "yatim" tanpa disadari admin.
        if ($this->hasChildSections($existing->id)) {
            return redirect()
                ->route('quiz.form-section.index')
                ->withErrors(['delete' => 'Section ini masih memiliki Sub Section di dalamnya. Hapus atau pindahkan dulu Sub Section-nya sebelum menghapus Section ini.']);
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

    /**
     * Section top-level (parent_section_id NULL) yang boleh diakses user ini,
     * dipakai untuk isi dropdown "Parent Section" di create()/edit() —
     * hierarki sengaja dibatasi 2 level (lihat isValidParentSection()), jadi
     * yang boleh jadi pilihan "induk" cuma section yang sendirinya top-level.
     */
    private function queryTopLevelSections()
    {
        $visibleFormIds = DataScope::visibleFormIds(Auth::user());

        return FormSection::query()
            ->whereNull('parent_section_id')
            ->where('status', 'active')
            ->with('form')
            ->when($visibleFormIds !== null, fn ($q) => $q->whereIn('form_id', $visibleFormIds))
            ->orderBy('name')
            ->get();
    }

    /**
     * Validasi Parent Section yang dipilih admin: harus (1) benar-benar ada,
     * (2) milik form YANG SAMA dengan section yang sedang disimpan (supaya
     * tidak bisa "meminjam" section milik form lain — pola sama dengan
     * validasi section_id di FormQuestionController), dan (3) section itu
     * sendiri TOP-LEVEL (parent_section_id-nya NULL) — supaya hierarki
     * benar-benar dibatasi 2 level (Section -> Sub Section), tidak bisa
     * berlapis-lapis.
     */
    private function isValidParentSection(string $parentSectionId, ?string $formId, ?string $excludeSectionId = null): bool
    {
        if (!$formId || $parentSectionId === $excludeSectionId) {
            return false;
        }

        $parent = FormSection::where('id', $parentSectionId)
            ->where('form_id', $formId)
            ->first();

        return $parent !== null && $parent->parent_section_id === null;
    }

    /**
     * True kalau section ini punya Sub Section (section lain yang menunjuk
     * ke section ini lewat parent_section_id) — dipakai untuk mencegah
     * hierarki 3 level (update()) dan mencegah Sub Section jadi yatim
     * (destroy()).
     */
    private function hasChildSections(string $sectionId): bool
    {
        return FormSection::where('parent_section_id', $sectionId)->exists();
    }
}
