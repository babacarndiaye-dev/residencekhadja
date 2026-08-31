<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Autorise la requête si l'utilisateur connecté est actif et possède
     * l'un des rôles listés (le rôle « admin » passe toujours).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active, 403);
        abort_unless($user->hasRole(...$roles), 403, "Accès réservé : {$request->route()?->getName()}");

        return $next($request);
    }
}
