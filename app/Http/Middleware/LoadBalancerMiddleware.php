<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoadBalancerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $servers = ['Server_A', 'Server_B'];
        
        $assignedServer = $servers[array_rand($servers)];

        Log::info("Load Balancer: تم توجيه الطلب إلى الخادم: $assignedServer");
        
        $response = $next($request);
        $response->headers->set('X-Server-Id', $assignedServer);

        return $response;
    }
}