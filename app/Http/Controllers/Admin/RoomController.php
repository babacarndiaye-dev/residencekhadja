<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category' => ['nullable', 'exists:room_categories,id'],
            'floor' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::in(array_keys(Room::STATUSES))],
            'state' => ['nullable', Rule::in(['active', 'inactive'])],
            'q' => ['nullable', 'string', 'max:20'],
        ]);

        $rooms = Room::query()
            ->with('category')
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('room_category_id', $v))
            ->when($filters['floor'] ?? null, fn ($q, $v) => $q->where('floor', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(($filters['state'] ?? null) === 'active', fn ($q) => $q->where('is_active', true))
            ->when(($filters['state'] ?? null) === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('number', 'like', "%{$v}%"))
            ->withCount('reservations')
            ->orderByRaw('LENGTH(number)')->orderBy('number')
            ->paginate(60)
            ->withQueryString();

        $categories = RoomCategory::orderBy('sort_order')->get();

        $summary = $categories->map(fn (RoomCategory $c) => [
            'name' => $c->name,
            'id' => $c->id,
            'total' => $c->rooms()->count(),
            'active' => $c->rooms()->where('is_active', true)->count(),
            'oos' => $c->rooms()->where('status', 'hors_service')->count(),
            'sellable' => $c->sellableRoomsCount(),
        ]);

        return view('admin.rooms.manage', [
            'rooms' => $rooms,
            'categories' => $categories,
            'summary' => $summary,
            'floors' => Room::whereNotNull('floor')->distinct()->orderBy('floor')->pluck('floor'),
            'statuses' => Room::STATUSES,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_category_id' => ['required', 'exists:room_categories,id'],
            'mode' => ['required', Rule::in(['single', 'bulk'])],
            'floor' => ['nullable', 'string', 'max:10'],
            'building' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(array_keys(Room::STATUSES))],
            // single
            'number' => ['required_if:mode,single', 'nullable', 'string', 'max:20'],
            // bulk : préfixe + de..à  → PREFIX + numéro
            'prefix' => ['nullable', 'string', 'max:10'],
            'from' => ['required_if:mode,bulk', 'nullable', 'integer', 'min:1', 'max:9999'],
            'to' => ['required_if:mode,bulk', 'nullable', 'integer', 'min:1', 'max:9999', 'gte:from'],
            'pad' => ['nullable', 'integer', 'min:1', 'max:6'],
        ]);

        $category = RoomCategory::findOrFail($data['room_category_id']);
        $base = [
            'hotel_id' => $category->hotel_id ?? Hotel::current()->id,
            'room_category_id' => $category->id,
            'floor' => $data['floor'] ?? null,
            'building' => $data['building'] ?? null,
            'status' => $data['status'],
            'is_active' => true,
        ];

        if ($data['mode'] === 'single') {
            if (Room::where('number', $data['number'])->exists()) {
                return back()->withInput()->withErrors(['number' => 'Ce numéro de chambre existe déjà.']);
            }
            $room = Room::create($base + ['number' => $data['number']]);
            AuditLog::record('room.created', $room);

            return back()->with('status', "Chambre {$room->number} créée.");
        }

        // bulk
        $prefix = $data['prefix'] ?? '';
        $pad = $data['pad'] ?? 0;
        $created = 0;
        $skipped = [];

        DB::transaction(function () use ($data, $base, $prefix, $pad, &$created, &$skipped) {
            for ($n = $data['from']; $n <= $data['to']; $n++) {
                $number = $prefix.($pad ? str_pad((string) $n, $pad, '0', STR_PAD_LEFT) : (string) $n);
                if (Room::where('number', $number)->exists()) {
                    $skipped[] = $number;

                    continue;
                }
                Room::create($base + ['number' => $number]);
                $created++;
            }
        });

        AuditLog::record('room.bulk_created', $category, ['created' => $created, 'skipped' => $skipped]);

        $msg = "{$created} chambre(s) créée(s).";
        if ($skipped) {
            $msg .= ' Ignorées (déjà existantes) : '.implode(', ', $skipped).'.';
        }

        return back()->with('status', $msg);
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:20', Rule::unique('rooms', 'number')->ignore($room->id)],
            'room_category_id' => ['required', 'exists:room_categories,id'],
            'floor' => ['nullable', 'string', 'max:10'],
            'building' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);

        $room->update($data);
        AuditLog::record('room.updated', $room);

        return back()->with('status', "Chambre {$room->number} mise à jour.");
    }

    public function toggle(Room $room)
    {
        if ($room->is_active && in_array($room->status, ['occupee'], true)) {
            return back()->withErrors(['room' => "Impossible de désactiver la chambre {$room->number} : elle est occupée."]);
        }

        $room->update([
            'is_active' => ! $room->is_active,
            'status' => ! $room->is_active ? $room->status : 'hors_service',
        ]);
        AuditLog::record($room->is_active ? 'room.activated' : 'room.deactivated', $room);

        return back()->with('status', "Chambre {$room->number} ".($room->is_active ? 'réactivée.' : 'désactivée (hors service).'));
    }

    public function destroy(Room $room)
    {
        if ($room->reservations()->exists()) {
            return back()->withErrors(['room' => "Chambre {$room->number} liée à un historique de réservations : désactivez-la plutôt que de la supprimer."]);
        }

        $number = $room->number;
        $room->delete();
        AuditLog::record('room.deleted', $room, ['number' => $number]);

        return back()->with('status', "Chambre {$number} supprimée.");
    }
}
