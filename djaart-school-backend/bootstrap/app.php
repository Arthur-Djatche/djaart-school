<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Backend 100% API/SPA : il n'existe aucune route web nommee "login".
        // Sans ce garde, toute requete /api/* invitee sans en-tete
        // Accept: application/json (ex. navigation directe vers un lien de
        // telechargement) declenche route('login') -> RouteNotFoundException -> 500,
        // au lieu du 401 JSON attendu.
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Les requetes /api/* sans en-tete Accept: application/json (ex. navigation
        // directe vers un lien de telechargement) ne sont pas detectees par
        // expectsJson() ; sans ce garde, Laravel tente de rediriger vers la route
        // nommee "login" (inexistante sur ce backend API-only) et plante en 500.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }
        });
    })->create();
