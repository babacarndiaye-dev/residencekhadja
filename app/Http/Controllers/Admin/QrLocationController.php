<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrLocation;
use App\Models\Venue;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QrLocationController extends Controller
{
    public function index(Request $request)
    {
        $venueId = $request->integer('venue') ?: null;
        $type = $request->query('type');

        $locations = QrLocation::query()
            ->with('venue', 'room')
            ->when($venueId, fn ($q) => $q->where('venue_id', $venueId))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('venue_id')->orderBy('label')
            ->paginate(40)
            ->withQueryString();

        return view('admin.qr.index', [
            'locations' => $locations,
            'venues' => Venue::where('is_active', true)->orderBy('sort_order')->get(),
            'activeVenue' => $venueId,
            'activeType' => $type,
        ]);
    }

    /** QR code SVG inline pour impression. */
    public function svg(QrLocation $qrLocation)
    {
        $result = (new Builder(
            writer: new SvgWriter,
            data: $qrLocation->url(),
            size: 240,
            margin: 8,
        ))->build();

        return response($result->getString(), 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function print(Request $request)
    {
        $venueId = $request->integer('venue') ?: null;

        $locations = QrLocation::query()->with('venue')
            ->where('is_active', true)
            ->when($venueId, fn ($q) => $q->where('venue_id', $venueId))
            ->orderBy('venue_id')->orderBy('label')
            ->get();

        return view('admin.qr.print', compact('locations'));
    }

    public function update(Request $request, QrLocation $qrLocation)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'zone' => ['nullable', 'string', 'max:60'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $qrLocation->update([
            'label' => $data['label'],
            'zone' => $data['zone'] ?? $qrLocation->zone,
            'seats' => $data['seats'] ?? $qrLocation->seats,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', "« {$qrLocation->label} » mis à jour.");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'venue_id' => ['required', 'exists:venues,id'],
            'label' => ['required', 'string', 'max:80'],
            'zone' => ['nullable', 'string', 'max:60'],
            'type' => ['required', Rule::in(['table', 'spot'])],
            'seats' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $venue = Venue::findOrFail($data['venue_id']);

        QrLocation::create([
            'hotel_id' => $venue->hotel_id,
            'venue_id' => $venue->id,
            'code' => 'LOC-'.strtoupper(Str::random(6)),
            'label' => $data['label'],
            'zone' => $data['zone'] ?? null,
            'type' => $data['type'],
            'seats' => $data['seats'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('status', 'Point QR créé.');
    }
}
