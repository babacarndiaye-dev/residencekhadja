<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GuestRequest;
use App\Services\Sms;
use Illuminate\Http\Request;

class GuestRequestController extends Controller
{
    public function index(Request $request)
    {
        $routed = $request->query('routed');
        $showDone = $request->boolean('done');

        $requests = GuestRequest::query()
            ->with('reservation.guest', 'room', 'handledBy')
            ->when($routed, fn ($q) => $q->where('routed_to', $routed))
            ->when(! $showDone, fn ($q) => $q->whereIn('status', ['open', 'acknowledged']))
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'acknowledged' THEN 1 ELSE 2 END")
            ->latest()
            ->get();

        return view('admin.guest-requests.index', [
            'requests' => $requests,
            'routed' => $routed,
            'showDone' => $showDone,
            'openCount' => GuestRequest::open()->count(),
        ]);
    }

    public function acknowledge(Request $request, GuestRequest $guestRequest)
    {
        abort_unless($guestRequest->status === 'open', 422);
        $guestRequest->update([
            'status' => 'acknowledged',
            'handled_by' => $request->user()->id,
            'acknowledged_at' => now(),
        ]);
        AuditLog::record('guest_request.acknowledged', $guestRequest);

        Sms::queueTemplate(
            $guestRequest->reservation?->guest?->phone,
            'request_ack',
            ['label' => $guestRequest->typeLabel()],
        );

        return back()->with('status', 'Demande prise en compte.');
    }

    public function resolve(Request $request, GuestRequest $guestRequest)
    {
        abort_if(in_array($guestRequest->status, ['done', 'cancelled'], true), 422);
        $guestRequest->update([
            'status' => 'done',
            'handled_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);
        AuditLog::record('guest_request.resolved', $guestRequest);

        Sms::queueTemplate(
            $guestRequest->reservation?->guest?->phone,
            'request_done',
            ['label' => $guestRequest->typeLabel()],
        );

        return back()->with('status', 'Demande traitée.');
    }
}
