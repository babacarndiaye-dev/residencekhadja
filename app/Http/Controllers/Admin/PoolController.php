<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\PoolAsset;
use App\Models\PoolReservation;
use App\Models\Reservation;
use App\Services\PoolBooking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PoolController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date('date') ?? today();

        return view('admin.pool.index', [
            'date' => $date,
            'board' => PoolBooking::board($date->toDateString()),
            'assets' => PoolAsset::active()->orderBy('sort_order')->orderBy('label')->get(),
            'slots' => config('pool.slots'),
            'statuses' => config('pool.statuses'),
            'inHouse' => Reservation::with('guest', 'room')
                ->where('status', 'checked_in')->orderBy('room_id')->get(),
            'summary' => [
                'booked' => PoolReservation::where('hotel_id', Hotel::current()->id)
                    ->whereDate('date', $date)->blocking()->count(),
                'guests' => (int) PoolReservation::where('hotel_id', Hotel::current()->id)
                    ->whereDate('date', $date)->blocking()->sum('guests'),
                'revenue' => (int) PoolReservation::where('hotel_id', Hotel::current()->id)
                    ->whereDate('date', $date)->whereIn('status', ['booked', 'checked_in', 'done'])->sum('price'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pool_asset_id' => ['required', 'exists:pool_assets,id'],
            'date' => ['required', 'date'],
            'slot' => ['required', Rule::in(array_keys(config('pool.slots')))],
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:12'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
            'price' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $booking = PoolBooking::book($data, $request->user()->id);
        AuditLog::record('pool.booked', $booking);

        return back()->with('status', "{$booking->asset->label} réservé pour {$booking->guest_name} ({$booking->slotLabel()}).");
    }

    public function updateStatus(Request $request, PoolReservation $poolReservation)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('pool.statuses')))],
        ]);

        $poolReservation->update(['status' => $data['status']]);
        AuditLog::record('pool.status', $poolReservation, ['status' => $data['status']]);

        return back()->with('status', "{$poolReservation->asset->label} : {$poolReservation->statusLabel()}.");
    }

    /* ------------------------------ Parc ------------------------------ */

    public function assets()
    {
        return view('admin.pool.assets', [
            'assets' => PoolAsset::orderBy('sort_order')->orderBy('label')->withCount('reservations')->get(),
            'kinds' => config('pool.kinds'),
        ]);
    }

    public function storeAsset(Request $request)
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(config('pool.kinds')))],
            'label' => ['required', 'string', 'max:60', Rule::unique('pool_assets', 'label')->where('hotel_id', Hotel::current()->id)],
            'capacity' => ['required', 'integer', 'min:1', 'max:12'],
            'half_day_price' => ['required', 'integer', 'min:0'],
            'full_day_price' => ['required', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        $asset = PoolAsset::create($data + ['hotel_id' => Hotel::current()->id, 'is_active' => true]);
        AuditLog::record('pool.asset_created', $asset);

        return back()->with('status', "{$asset->label} ajouté au parc.");
    }

    public function toggleAsset(PoolAsset $poolAsset)
    {
        $poolAsset->update(['is_active' => ! $poolAsset->is_active]);

        return back()->with('status', "{$poolAsset->label} ".($poolAsset->is_active ? 'réactivé.' : 'retiré du service.'));
    }
}
