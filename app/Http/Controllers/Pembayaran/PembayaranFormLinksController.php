<?php

namespace App\Http\Controllers\Pembayaran;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\PembayaranForm;
use App\Models\PembayaranFormLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PembayaranFormLinksController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::paginate(
            PembayaranFormLink::class,
            (string) $userId,
            ['status', 'payment_status', 'payment_method', 'order_id'],
            $search,
            10,
            ['user', 'parent', 'pembayaranForm']
        );

        return view('pembayaran.form-link.index', compact('data'));
    }

    public function create()
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $users = User::query()
            ->orderBy('name')
            ->get();

        $forms = PembayaranForm::query()
            ->where('user_id', (string) $userId)
            ->orderBy('name')
            ->get();

        return view('pembayaran.form-link.create', compact('users', 'forms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'required|string|exists:users,id',
            'pembayaran_form_id' => 'required|string|exists:pembayaran_forms,id',
            'status' => 'required|in:active,inactive',
            'payment_status' => 'nullable|in:pending,paid,failed,cancelled',
            'payment_method' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'order_id' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $formOwned = PembayaranForm::query()
            ->where('id', $validated['pembayaran_form_id'])
            ->where('user_id', (string) $userId)
            ->exists();

        if (!$formOwned) {
            abort(403, 'Form pembayaran tidak valid untuk user ini.');
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::create(PembayaranFormLink::class, $validated);

        return redirect()
            ->route('pembayaran.form-link.index')
            ->with('success', 'Form link pembayaran berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $data = AdminCrud::findOrFail(PembayaranFormLink::class, $id, (string) $userId, ['user', 'parent', 'pembayaranForm']);

        $users = User::query()
            ->orderBy('name')
            ->get();

        $forms = PembayaranForm::query()
            ->where('user_id', (string) $userId)
            ->orderBy('name')
            ->get();

        return view('pembayaran.form-link.edit', compact('data', 'users', 'forms'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::findOrFail(PembayaranFormLink::class, $id, (string) $userId);

        $validated = $request->validate([
            'parent_id' => 'required|string|exists:users,id',
            'pembayaran_form_id' => 'required|string|exists:pembayaran_forms,id',
            'status' => 'required|in:active,inactive',
            'payment_status' => 'nullable|in:pending,paid,failed,cancelled',
            'payment_method' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'order_id' => 'nullable|string|max:255',
        ]);

        $formOwned = PembayaranForm::query()
            ->where('id', $validated['pembayaran_form_id'])
            ->where('user_id', (string) $userId)
            ->exists();

        if (!$formOwned) {
            abort(403, 'Form pembayaran tidak valid untuk user ini.');
        }

        $validated['user_id'] = (string) $userId;

        AdminCrud::update(PembayaranFormLink::class, $id, $validated, (string) $userId);

        return redirect()
            ->route('pembayaran.form-link.index')
            ->with('success', 'Form link pembayaran berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        AdminCrud::delete(PembayaranFormLink::class, $id, (string) $userId);

        return redirect()
            ->route('pembayaran.form-link.index')
            ->with('success', 'Form link pembayaran berhasil dihapus.');
    }
}
