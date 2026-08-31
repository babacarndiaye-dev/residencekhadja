<?php

namespace App\Http\Controllers;

use App\Models\QrLocation;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceRequestController extends Controller
{
    /** « Appeler le serveur » (§27). */
    public function store(Request $request, string $code)
    {
        $location = QrLocation::where('code', $code)->firstOrFail();
        abort_unless($location->is_active, 422);

        $data = $request->validate([
            'type' => ['required', Rule::in(['assistance', 'water', 'cutlery', 'info', 'other'])],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        // Anti-spam léger : pas de doublon ouvert du même type sur 2 min.
        $recent = ServiceRequest::where('qr_location_id', $location->id)
            ->where('type', $data['type'])
            ->where('status', '!=', 'done')
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if (! $recent) {
            ServiceRequest::create([
                'hotel_id' => $location->hotel_id,
                'qr_location_id' => $location->id,
                'type' => $data['type'],
                'note' => $data['note'] ?? null,
                'status' => 'open',
            ]);
        }

        return back()->with('status', 'Un membre de l’équipe arrive.');
    }
}
