<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AopPerformanceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $bottleneckThreshold = 100.0; 

        if ($executionTime > $bottleneckThreshold) {
            Log::warning("AOP Performance Alert: Bottleneck detected!", [
                'url'            => $request->fullUrl(),
                'method'         => $request->method(),
                'execution_time' => round($executionTime, 2) . ' ms',
                'controller'     => $request->route() ? $request->route()->getActionName() : 'Closure',
            ]);
        }

        $response->headers->set('X-Server-Execution-Time', round($executionTime, 2) . 'ms');

        return $response;
    }
}