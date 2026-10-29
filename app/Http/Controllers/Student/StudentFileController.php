<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\InaStudy\StudentFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Menu Students > File Student (29 Oktober 2026): daftar student yang punya file
 * (dokumen aplikasi & foto formulir) beserta jumlah & ukurannya, dan unduh ZIP
 * per student atau semua sekaligus. Logika pengumpulan file ada di App\Services\InaStudy\StudentFiles.
 * Setiap unduhan dicatat di log (siapa mengunduh file siapa), karena berisi dokumen pribadi.
 */
class StudentFileController extends Controller
{
    public function index(Request $request, StudentFiles $files): View
    {
        $user = $request->user() ?? abort(401);
        $search = trim((string) $request->query('search'));

        $students = $files->query($user, $search)->paginate(20)->withQueryString();
        $summaries = $students->getCollection()->mapWithKeys(fn ($student) => [$student->id => $files->summary($student)]);

        return view('student.files.index', [
            'students' => $students,
            'summaries' => $summaries,
            'numbers' => $students->getCollection()->mapWithKeys(fn ($student) => [$student->id => $files->applicationNumbers($student)]),
            'search' => $search,
            'files' => $files,
        ]);
    }

    /** ZIP semua file 1 student. Student di luar cakupan akses user = 404. */
    public function download(Request $request, StudentFiles $files, string $student): BinaryFileResponse|RedirectResponse
    {
        $model = $files->query($request->user())->whereKey($student)->first();
        abort_unless($model instanceof Student, 404);

        return $this->send($request, $files, [$model], $files->studentFolder($model).'.zip');
    }

    /** ZIP semua student yang terlihat (mengikuti pencarian yang sedang aktif). */
    public function downloadAll(Request $request, StudentFiles $files): BinaryFileResponse|RedirectResponse
    {
        $search = trim((string) $request->query('search'));
        $students = $files->query($request->user(), $search)->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada file student untuk diunduh.');
        }

        return $this->send($request, $files, $students, 'file-student-'.now()->format('Y-m-d_His').'.zip');
    }

    /** ZIP student yang dicentang di tabel (maksimal 200 sekali unduh). Hanya student dalam cakupan akses user. */
    public function downloadSelected(Request $request, StudentFiles $files): BinaryFileResponse|RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['string', 'max:64'],
        ], [
            'ids.required' => 'Centang dulu student yang mau diunduh.',
            'ids.max' => 'Maksimal 200 student sekali unduh.',
        ])['ids'];

        $students = $files->query($request->user())->whereKey($ids)->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Student yang dipilih tidak ditemukan atau tidak punya file.');
        }

        return $this->send($request, $files, $students, 'file-student-terpilih-'.now()->format('Y-m-d_His').'.zip');
    }

    private function send(Request $request, StudentFiles $files, iterable $students, string $name): BinaryFileResponse|RedirectResponse
    {
        set_time_limit(0);

        try {
            $path = $files->zip($students);
        } catch (RuntimeException $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        Log::info('File Student diunduh', [
            'user_id' => $request->user()->id,
            'user' => $request->user()->email,
            'students' => collect($students)->pluck('id')->all(),
            'file' => $name,
        ]);

        return response()->download($path, $name, ['Content-Type' => 'application/zip'])->deleteFileAfterSend();
    }
}
