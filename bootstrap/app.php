<?php

use App\Http\Middleware\EnsureGuest;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'guest.app' => EnsureGuest::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        // Webhooks externes (paiement, canaux) : pas de jeton CSRF (signés côté service).
        $middleware->validateCsrfTokens(except: [
            'paiement/webhook/*',
            'distribution/webhook/*',
        ]);

        // Jeton d'accès invité : opaque (48 car. aléatoires), pas de chiffrement cookie.
        $middleware->encryptCookies(except: ['guest_token']);

        // Derrière un tunnel HTTPS (Expose) ou un reverse proxy : faire confiance
        // aux en-têtes X-Forwarded-* pour générer des URL https correctes.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
