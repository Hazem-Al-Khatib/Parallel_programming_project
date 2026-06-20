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
    ->withMiddleware(function (Middleware $middleware) {
        
        $middleware->validateCsrfTokens(except: [
            'purchase-distributed-lock',
            '/buy', 
            'purchase-without-lock',
        ]);

        $middleware->append(\App\Http\Middleware\AopPerformanceMiddleware::class);

        $middleware->alias([
            'load.balancer' => \App\Http\Middleware\LoadBalancerMiddleware::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })->create();



    
