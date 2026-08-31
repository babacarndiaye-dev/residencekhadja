<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\HousekeepingTask;
use App\Models\MaintenanceTicket;
use App\Models\Reservation;
use App\Models\Room;
use App\Support\GuestSegment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomBoardController extends Controller
{
    public function index(Request $request)
    {
        $floor = $request->query('etage');

        $rooms = Room::query()
            ->with('category')
            ->when($floor, fn ($q) => $q->where('floor', $floor))
            ->orderBy('number')
            ->get();

        return view('admin.rooms.index', [
            'rooms' => $rooms,
            'floors' => Room::query()->whereNotNull('floor')->distinct()->orderBy('floor')->pluck('floor'),
            'activeFloor' => $floor,
            'statuses' => Room::STATUSES,
            'counts' => Room::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /**
     * Tableau de bord intelligent d'une chambre (GEMS §1) : agrège en direct
     * le séjour en cours, le client, le folio, les demandes et l'état
     * opérationnel. Le contenu s'adapte à l'occupant et au rôle connecté.
     */
    public function show(Room $room)
    {
        $room->load('category');

        $with = [
            'guest.loyaltyAccount.tier',
            'ratePlan',
            'charges' => fn ($q) => $q->orderByDesc('id'),
            'orders' => fn ($q) => $q->with('items')->orderByDesc('id'),
            'payments',
            'guestRequests' => fn ($q) => $q->orderByDesc('id'),
        ];

        $stay = Reservation::query()
            ->where('room_id', $room->id)->where('status', 'checked_in')
            ->with($with)->orderByDesc('checked_in_at')->first()
            ?? Reservation::query()
                ->where('room_id', $room->id)->whereIn('status', ['confirmed', 'pending'])
                ->whereDate('check_out', '>=', today())
                ->with($with)->orderBy('check_in')->first();

        $history = Reservation::query()
            ->where('room_id', $room->id)->where('status', 'checked_out')
            ->when($stay, fn ($q) => $q->where('id', '!=', $stay->id))
            ->with('guest')->orderByDesc('check_out')->limit(8)->get();

        return view('admin.rooms.show', [
            'room' => $room,
            'stay' => $stay,
            'segment' => $stay ? GuestSegment::for($stay) : null,
            'openRequests' => $stay
                ? $stay->guestRequests->whereIn('status', ['open', 'acknowledged'])
                : collect(),
            'history' => $history,
            'housekeeping' => HousekeepingTask::where('room_id', $room->id)->orderByDesc('id')->first(),
            'maintenance' => MaintenanceTicket::where('room_id', $room->id)->open()->orderByDesc('id')->get(),
            'services' => config('gems.services', []),
            'checkinTime' => config('gems.checkin_time', '14:00'),
            'checkoutTime' => config('gems.checkout_time', '12:00'),
            'canFolio' => auth()->user()->hasRole('reception', 'direction', 'finance'),
            'canOperate' => auth()->user()->hasRole('reception', 'direction'),
            'statuses' => Room::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Room $room)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Room::STATUSES))],
        ]);

        $room->update(['status' => $data['status']]);
        AuditLog::record('room.status', $room, ['status' => $data['status']]);

        return back()->with('status', "Chambre {$room->number} : {$room->statusLabel()}.");
    }
}
