<?php

namespace App\Http\Controllers\Zoom;

use App\Helpers\AdminCrud;
use App\Http\Controllers\Controller;
use App\Models\ZoomMeeting;
use App\Models\ZoomMeetingRecording;
use App\Services\Zoom\ZoomClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * CRUD "Kelola Meeting" (menu Zoom > Kelola Meeting): membuat/mengubah/
 * menghapus scheduled meeting Zoom, memaksa mengakhiri meeting yang sedang
 * berlangsung, dan menampilkan hasil recording-nya. Semua panggilan ke Zoom
 * lewat App\Services\Zoom\ZoomClient (kredensial dari menu Settings >
 * Setting Zoom).
 */
class MeetingController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $search = $request->query('search');

        $data = ZoomMeeting::query()
            ->with('user')
            ->when($search, fn ($q) => $q->where('topic', 'like', "%{$search}%"))
            ->orderByDesc('start_time')
            ->paginate(10)
            ->withQueryString();

        return view('zoom.meeting.index', compact('data', 'search'));
    }

    public function create()
    {
        return view('zoom.meeting.create', [
            'defaultSettings' => ZoomMeeting::defaultSettings(),
            'timezones' => $this->timezoneOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();
        if ($userId === null) {
            abort(401);
        }

        $validated = $this->validated($request);

        try {
            $response = (new ZoomClient())->createMeeting($this->buildZoomPayload($validated));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        ZoomMeeting::create([
            'user_id' => (string) $userId,
            'zoom_meeting_id' => (string) $response['id'],
            'zoom_uuid' => $response['uuid'] ?? null,
            'topic' => $validated['topic'],
            'agenda' => $validated['agenda'],
            'type' => 2,
            'start_time' => $validated['start_time'],
            'duration' => $validated['duration'],
            'timezone' => $validated['timezone'],
            'password' => $response['password'] ?? $validated['password'],
            'join_url' => $response['join_url'] ?? null,
            'start_url' => $response['start_url'] ?? null,
            'settings' => $validated['settings'],
            'status' => 'scheduled',
        ]);

        return redirect()
            ->route('zoom.meeting.index')
            ->with('success', 'Meeting Zoom berhasil dibuat.');
    }

    public function edit(string $id)
    {
        $data = AdminCrud::findOrFail(ZoomMeeting::class, $id);

        return view('zoom.meeting.edit', [
            'data' => $data,
            'timezones' => $this->timezoneOptions(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $meeting = AdminCrud::findOrFail(ZoomMeeting::class, $id);

        $validated = $this->validated($request);

        try {
            (new ZoomClient())->updateMeeting($meeting->zoom_meeting_id, $this->buildZoomPayload($validated));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AdminCrud::update(ZoomMeeting::class, $id, [
            'topic' => $validated['topic'],
            'agenda' => $validated['agenda'],
            'start_time' => $validated['start_time'],
            'duration' => $validated['duration'],
            'timezone' => $validated['timezone'],
            'password' => $validated['password'] ?: $meeting->password,
            'settings' => $validated['settings'],
        ]);

        return redirect()
            ->route('zoom.meeting.index')
            ->with('success', 'Meeting Zoom berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $meeting = AdminCrud::findOrFail(ZoomMeeting::class, $id);

        try {
            (new ZoomClient())->deleteMeeting($meeting->zoom_meeting_id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        AdminCrud::delete(ZoomMeeting::class, $id);

        return redirect()
            ->route('zoom.meeting.index')
            ->with('success', 'Meeting Zoom berhasil dihapus.');
    }

    /**
     * Paksa akhiri meeting yang sedang berlangsung (host lupa/tidak sempat
     * mengakhiri sendiri dari aplikasi Zoom).
     */
    public function end(string $id)
    {
        $meeting = AdminCrud::findOrFail(ZoomMeeting::class, $id);

        try {
            (new ZoomClient())->endMeeting($meeting->zoom_meeting_id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        AdminCrud::update(ZoomMeeting::class, $id, [
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        return redirect()
            ->route('zoom.meeting.index')
            ->with('success', 'Meeting berhasil diakhiri.');
    }

    /**
     * Tampilkan daftar recording 1 meeting -- disinkronkan ulang dari Zoom
     * API setiap halaman ini dibuka, supaya selalu menampilkan hasil terbaru
     * (recording baru selesai diproses Zoom beberapa saat setelah meeting
     * berakhir, jadi tidak cukup disinkronkan sekali saat meeting dibuat).
     */
    public function recordings(string $id)
    {
        $meeting = AdminCrud::findOrFail(ZoomMeeting::class, $id);

        try {
            $this->syncRecordings($meeting);
        } catch (RuntimeException $e) {
            $meeting->load('recordings');

            return view('zoom.meeting.recordings', compact('meeting'))->with('error', $e->getMessage());
        }

        $meeting->load('recordings');

        return view('zoom.meeting.recordings', compact('meeting'));
    }

    private function syncRecordings(ZoomMeeting $meeting): void
    {
        $result = (new ZoomClient())->listRecordings($meeting->zoom_meeting_id);

        $meeting->forceFill(['recordings_synced_at' => now()])->save();

        if (! $result || empty($result['recording_files'])) {
            return;
        }

        foreach ($result['recording_files'] as $file) {
            if (empty($file['id'])) {
                continue;
            }

            ZoomMeetingRecording::updateOrCreate(
                ['recording_file_id' => $file['id']],
                [
                    'zoom_meeting_id' => $meeting->id,
                    'recording_type' => $file['recording_type'] ?? null,
                    'file_type' => $file['file_type'] ?? null,
                    'file_size' => $file['file_size'] ?? null,
                    'play_url' => $file['play_url'] ?? null,
                    'download_url' => $file['download_url'] ?? null,
                    'recording_start' => $file['recording_start'] ?? null,
                    'recording_end' => $file['recording_end'] ?? null,
                    'status' => $file['status'] ?? null,
                ]
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function timezoneOptions(): array
    {
        return \DateTimeZone::listIdentifiers();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:255',
            'agenda' => 'nullable|string|max:2000',
            'start_date' => 'required|date',
            'start_time_of_day' => 'required',
            'duration' => 'required|integer|min:1|max:1440',
            'timezone' => 'required|string|max:64',
            'password' => 'nullable|string|max:10',
        ]);

        $startTime = Carbon::parse($validated['start_date'] . ' ' . $validated['start_time_of_day']);

        return [
            'topic' => $validated['topic'],
            'agenda' => $validated['agenda'] ?? null,
            'start_time' => $startTime,
            'duration' => (int) $validated['duration'],
            'timezone' => $validated['timezone'],
            'password' => $validated['password'] ?? null,
            'settings' => [
                'join_before_host' => $request->boolean('join_before_host'),
                'waiting_room' => $request->boolean('waiting_room'),
                'host_video' => $request->boolean('host_video'),
                'participant_video' => $request->boolean('participant_video'),
                'mute_upon_entry' => $request->boolean('mute_upon_entry'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function buildZoomPayload(array $validated): array
    {
        $payload = [
            'topic' => $validated['topic'],
            'type' => 2,
            'start_time' => $validated['start_time']->format('Y-m-d\TH:i:s'),
            'duration' => $validated['duration'],
            'timezone' => $validated['timezone'],
            'agenda' => $validated['agenda'] ?? '',
            'settings' => $validated['settings'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        return $payload;
    }
}
