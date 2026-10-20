<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trusted proxies are loaded by Laravel's default TrustProxies
        // middleware from config('trustedproxy.proxies') (config/trustedproxy.php,
        // sourced from the TRUSTED_PROXIES env var). Set TRUSTED_PROXIES when
        // deploying behind a load balancer / CDN (comma-separated IPs or CIDR
        // ranges) so $request->ip() resolves to the real client address.

        $middleware->alias([
            'admin' => App\Http\Middleware\StaffMiddleware::class,
            'staff' => App\Http\Middleware\StaffMiddleware::class,
            'owner' => App\Http\Middleware\OwnerMiddleware::class,
            'customer' => App\Http\Middleware\CustomerMiddleware::class,
            'signed.appurl' => App\Http\Middleware\ValidateAppUrlSignature::class,
        ]);

        $middleware->web(append: [
            App\Http\Middleware\SecurityHeaders::class,
            App\Http\Middleware\ServeProductionAssets::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
