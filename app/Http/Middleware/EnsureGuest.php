<?php

namespace App\Http\Middleware;

use App\Services\GuestApp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        $device = GuestApp::resolve($request);

        if (! $device) {
            return redirect()->route('guest.login');
        }

        $reservation = $device->reservation->loadMissing('guest', 'room', 'roomCategory', 'hotel');

        $request->attributes->set('guest_device', $device);
        $request->attributes->set('guest_reservation', $reservation);
        View::share('stay', $reservation);

        return $next($request);
    }
}
