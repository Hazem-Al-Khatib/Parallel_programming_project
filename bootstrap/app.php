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
        
        // 1️⃣ استثناء مسارات الفحص من الـ CSRF لكي تنجح طلبات الـ POST القادمة من PowerShell
        $middleware->validateCsrfTokens(except: [
            'purchase-distributed-lock', 
            'buy', 
            'order/place',
            'order/secure-place',
        ]);

        // 2️⃣ تفعيل ميدل وير الـ AOP بشكل عالمي (Global) لقياس أداء أي Request يدخل النظام
        $middleware->append(\App\Http\Middleware\AopPerformanceMiddleware::class);

        // 3️⃣ تسجيل الأسماء المستعارة (Aliases) للميدل ويرز الأخرى لاستخدامها في الـ Routes
        $middleware->alias([
            'load.balancer' => \App\Http\Middleware\LoadBalancerMiddleware::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();