<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'organization' => \App\Http\Middleware\EnsureOrganizationAccess::class,
            'client.office' => \App\Http\Middleware\EnsureClientAccess::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            $slug = $request->route('slug');
            if (is_string($slug) && str_contains($request->path(), '/portal')) {
                return route('portal.login', $slug);
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
