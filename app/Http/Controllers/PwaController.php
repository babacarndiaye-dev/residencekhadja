<?php

namespace App\Http\Controllers;

use App\Support\Branding;

class PwaController extends Controller
{
    public function manifest()
    {
        return response()->json([
            'name' => config('guestapp.name').' — Mon séjour',
            'short_name' => config('guestapp.short_name'),
            'description' => 'Votre séjour à l’Hôtel Résidence Khadija : folio, demandes, room service, fidélité.',
            'start_url' => '/app',
            'scope' => '/app',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => config('guestapp.background_color'),
            'theme_color' => config('guestapp.theme_color'),
            'lang' => 'fr',
            'icons' => [
                ['src' => asset('img/app-icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('img/app-icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('img/app-icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => Branding::logo(), 'sizes' => 'any', 'type' => 'image/svg+xml'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    public function serviceWorker()
    {
        // SW désactivé : on renvoie un worker qui se désinstalle lui-même et vide les caches,
        // pour nettoyer les téléphones où une version cassée serait déjà installée.
        if (! config('guestapp.service_worker')) {
            $js = <<<'JS'
                self.addEventListener('install', () => self.skipWaiting());
                self.addEventListener('activate', (e) => {
                    e.waitUntil((async () => {
                        for (const k of await caches.keys()) await caches.delete(k);
                        await self.registration.unregister();
                        for (const c of await self.clients.matchAll()) c.navigate(c.url);
                    })());
                });
                JS;

            return response($js, 200, ['Content-Type' => 'application/javascript', 'Cache-Control' => 'no-cache']);
        }

        $js = <<<'JS'
            const CACHE = 'khadija-guest-v2';
            const OFFLINE = '/app/hors-ligne';

            self.addEventListener('install', (e) => {
                e.waitUntil(caches.open(CACHE).then((c) => c.add(OFFLINE)).then(() => self.skipWaiting()));
            });

            self.addEventListener('activate', (e) => {
                e.waitUntil(caches.keys().then((keys) => Promise.all(
                    keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))
                )).then(() => self.clients.claim()));
            });

            self.addEventListener('fetch', (e) => {
                const req = e.request;
                if (req.method !== 'GET') return;

                // Seules les navigations reçoivent la page hors-ligne, et uniquement
                // si le réseau échoue vraiment. Tout le reste passe au réseau normalement.
                if (req.mode === 'navigate') {
                    e.respondWith(
                        fetch(req)
                            .then((res) => {
                                caches.open(CACHE).then((c) => c.put(req, res.clone())).catch(() => {});
                                return res;
                            })
                            .catch(() => caches.match(req).then((hit) => hit || caches.match(OFFLINE)))
                    );
                }
            });
            JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript',
            'Service-Worker-Allowed' => '/app',
            'Cache-Control' => 'no-cache',
        ]);
    }

    public function offline()
    {
        return view('app.offline');
    }
}
