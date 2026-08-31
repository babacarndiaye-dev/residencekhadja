<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\QrLocation;
use App\Services\LoyaltyProgram;
use App\Services\PaymentGateway;
use Illuminate\Http\Request;

class AppController extends Controller
{
    private function stay(Request $request)
    {
        return $request->attributes->get('guest_reservation');
    }

    public function home(Request $request)
    {
        $stay = $this->stay($request);

        return view('app.home', [
            'balance' => $stay->balance(),
            'openRequests' => $stay->guestRequests()->open()->count(),
            'roomMenuCode' => $this->roomMenuCode($stay),
            'quickActions' => config('guestapp.quick_actions'),
        ]);
    }

    public function stayPage(Request $request)
    {
        $stay = $this->stay($request);
        $stay->load(['charges', 'payments']);

        return view('app.stay', [
            'balance' => $stay->balance(),
            'nights' => $stay->nights(),
        ]);
    }

    public function payBalance(Request $request)
    {
        $stay = $this->stay($request);
        abort_if($stay->balance() <= 0, 422, 'Aucun solde à régler.');

        $intent = PaymentGateway::open($stay, 'reservation_balance', $stay->balance(), [
            'hotel_id' => $stay->hotel_id,
            'payer_name' => $stay->guest->fullName(),
            'payer_email' => $stay->guest->email,
            'meta' => ['channel' => 'guest_app'],
        ]);

        return redirect()->to(PaymentGateway::checkoutUrl($intent));
    }

    public function menu(Request $request)
    {
        $code = $this->roomMenuCode($this->stay($request));

        return $code
            ? redirect()->route('qr.show', $code)
            : redirect()->route('menu.public');
    }

    public function wifi(Request $request)
    {
        return view('app.wifi', ['wifi' => config('guestapp.wifi')]);
    }

    public function loyalty(Request $request)
    {
        $stay = $this->stay($request);
        $account = $stay->guest->loadMissing('loyaltyAccount.tier')->loyaltyAccount;

        return view('app.loyalty', [
            'account' => $account,
            'programName' => config('loyalty.program_name'),
        ]);
    }

    public function enrol(Request $request)
    {
        $guest = $this->stay($request)->guest;
        LoyaltyProgram::enroll($guest);

        return redirect()->route('guest.loyalty')->with('status', 'Bienvenue dans le programme fidélité !');
    }

    private function roomMenuCode($stay): ?string
    {
        if (! $stay->room_id) {
            return null;
        }

        return QrLocation::where('room_id', $stay->room_id)->where('is_active', true)->value('code');
    }
}
