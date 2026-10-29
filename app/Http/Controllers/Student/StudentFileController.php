<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\InaStudy\StudentFiles;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu Students > File Student (29 Oktober 2026): daftar student yang punya file
 * (dokumen aplikasi & foto formulir) beserta jumlah & ukurannya, untuk backup.
 * Logika pengumpulan file ada di App\Services\InaStudy\StudentFiles.
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
            'search' => $search,
        ]);
    }
}
