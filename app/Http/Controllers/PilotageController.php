<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PosController;
use App\Models\Order;
use App\Services\Analytics;
use App\Support\Branding;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Application directeur — tableau de bord mobile installable (PWA `/pilotage`),
 * sans notification push : les alertes s'affichent dans la page.
 */
class PilotageController extends Controller
{
    public function home(Request $request)
    {
        $today = [Carbon::today(), Carbon::today()];
        $sales = Analytics::posSales(...$today);
        $occ = Analytics::occupancy(...$today);
        $pms = Analytics::pms(Carbon::today()->subDays(6), Carbon::today());

        return view('pilotage.home', [
            'sales' => $sales,
            'occ' => $occ,
            'pms' => $pms,
            'payments' => Analytics::payments(Carbon::today()->subDays(29), Carbon::today()),
            'roomServicePending' => Order::where('sale_type', 'room_service')->open()->count(),
            'alerts' => $request->user()->unreadNotifications()->latest()->take(12)->get(),
            'saleTypes' => PosController::SALE_TYPES,
        ]);
    }

    public function manifest()
    {
        return response()->json([
            'name' => config('hotel.name').' — Pilotage',
            'short_name' => 'Pilotage',
            'description' => 'Indicateurs et alertes de l’hôtel, en un coup d’œil.',
            'start_url' => '/pilotage',
            'scope' => '/pilotage',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#202628',
            'theme_color' => '#202628',
            'lang' => 'fr',
            'icons' => [
                ['src' => asset('img/app-icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('img/app-icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => Branding::logo(), 'sizes' => 'any', 'type' => 'image/svg+xml'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    public function serviceWorker()
    {
        // SW minimal : n'intercepte que les navigations (jamais les assets), page hors-ligne simple.
        $js = <<<'JS'
            const CACHE = 'khadija-pilotage-v1';
            self.addEventListener('install', (e) => { self.skipWaiting(); });
            self.addEventListener('activate', (e) => { e.waitUntil(self.clients.claim()); });
            self.addEventListener('fetch', (e) => {
                const req = e.request;
                if (req.method !== 'GET' || req.mode !== 'navigate') return;
                e.respondWith(
                    fetch(req)
                        .then((res) => { caches.open(CACHE).then((c) => c.put(req, res.clone())).catch(() => {}); return res; })
                        .catch(() => caches.match(req).then((hit) => hit || new Response('<h1>Hors ligne</h1>', { headers: { 'Content-Type': 'text/html' } })))
                );
            });
            JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript',
            'Service-Worker-Allowed' => '/pilotage',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
