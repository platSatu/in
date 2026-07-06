<?php

namespace App\Http\Controllers;

use App\Models\ProfileBussines;
use App\Helpers\CrudHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileBussinesController extends Controller
{
    /**
     * GET /profile-bussines
     * Menampilkan semua data dengan paginate & search
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $searchColumns = ['name', 'description'];
        
        $data = CrudHelper::getAll(
            ProfileBussines::class,
            ['user', 'parent'],
            $search,
            $searchColumns,
            10
        );

        return view('profile-bussines.index', compact('data'));
    }

    /**
     * GET /profile-bussines/my-data
     * Data milik saya & anak buah
     */
    public function myData(Request $request)
    {
        $search = $request->query('search');
        $searchColumns = ['name', 'description'];
        
        $data = CrudHelper::getByUser(
            ProfileBussines::class,
            ['user', 'parent'],
            $search,
            $searchColumns,
            10
        );

        return view('profile-bussines.index', compact('data'));
    }

    /**
     * GET /profile-bussines/create
     * Form tambah data
     */
    public function create()
    {
        $parents = ProfileBussines::where('user_id', Auth::id())->get();
        
        return view('profile-bussines.create', compact('parents'));
    }

    /**
     * POST /profile-bussines
     * Simpan data baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'parent_id' => 'nullable|string',
        ]);

        $data = CrudHelper::store(ProfileBussines::class, $validated);

        return redirect()
            ->route('profile-bussines.show', $data->id)
            ->with('success', 'Profile Bussines berhasil dibuat!');
    }

    /**
     * GET /profile-bussines/{id}
     * Detail data
     */
    public function show(string $id)
    {
        $data = CrudHelper::findById(ProfileBussines::class, $id, ['user', 'parent', 'children']);
        
        return view('profile-bussines.show', compact('data'));
    }

    /**
     * GET /profile-bussines/{id}/edit
     * Form edit data
     */
    public function edit(string $id)
    {
        $data = CrudHelper::findById(ProfileBussines::class, $id);
        $parents = ProfileBussines::where('user_id', Auth::id())
            ->where('id', '!=', $id)
            ->get();
        
        return view('profile-bussines.edit', compact('data', 'parents'));
    }

    /**
     * PUT/PATCH /profile-bussines/{id}
     * Update data
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:active,inactive',
            'parent_id' => 'nullable|string',
        ]);

        $data = CrudHelper::update(ProfileBussines::class, $id, $validated);

        return redirect()
            ->route('profile-bussines.show', $data->id)
            ->with('success', 'Profile Bussines berhasil diupdate!');
    }

    /**
     * DELETE /profile-bussines/{id}
     * Hapus data
     */
    public function destroy(string $id)
    {
        CrudHelper::delete(ProfileBussines::class, $id);

        return redirect()
            ->route('profile-bussines.index')
            ->with('success', 'Profile Bussines berhasil dihapus!');
    }
}