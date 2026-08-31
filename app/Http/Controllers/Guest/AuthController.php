<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\GuestApp;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (GuestApp::resolve($request)) {
            return redirect()->route('guest.home');
        }

        return view('app.login', ['reference' => $request->query('ref')]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:24'],
            'last_name' => ['required', 'string', 'max:80'],
        ]);

        $reservation = GuestApp::match($data['reference'], $data['last_name']);

        if (! $reservation) {
            return back()->withInput()->withErrors([
                'reference' => 'Référence ou nom incorrect, ou séjour non actif.',
            ]);
        }

        $device = GuestApp::issue($reservation, $request);

        return redirect()->route('guest.home')
            ->cookie(GuestApp::COOKIE, $device->token, 60 * 24 * config('guestapp.token_ttl_days', 21), null, null, true, true);
    }

    /** Lien magique signé remis à la réception. */
    public function magic(Request $request, string $reference)
    {
        $reservation = Reservation::whereRaw('UPPER(reference) = ?', [mb_strtoupper($reference)])
            ->whereIn('status', GuestApp::ACCESSIBLE)
            ->firstOrFail();

        $device = GuestApp::issue($reservation, $request);

        return redirect()->route('guest.home')
            ->cookie(GuestApp::COOKIE, $device->token, 60 * 24 * config('guestapp.token_ttl_days', 21), null, null, true, true);
    }

    public function logout()
    {
        return redirect()->route('guest.login')->withoutCookie(GuestApp::COOKIE);
    }
}
