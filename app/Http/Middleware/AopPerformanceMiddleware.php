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
     * يعمل هذا الميدل وير بمثابة (AOP Around Advice) لمراقبة الأداء ورصد الاختناقات.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1️⃣ [Before Advice]: تسجيل نقطة البداية الزمنية بدقة ميكروية قبل دخول الـ Controller
        $startTime = microtime(true);

        // 2️⃣ [Proceed]: تمرير الطلب إلى مرحلة التنفيذ الفعلية (الـ JoinPoint الصافية)
        $response = $next($request);

        // 3️⃣ [After Advice]: حساب وقت التنفيذ الإجمالي بعد انتهاء معالجة الطلب
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // التحويل إلى ميلي ثانية (ms)

        // تحديد عتبة الأداء (Threshold): أي طلب يتجاوز 100ms يُصنف كاختناق محتمل
        $bottleneckThreshold = 100.0; 

        if ($executionTime > $bottleneckThreshold) {
            // تسجيل تفصيلي للاختناق في ملف الـ Logs دون تلوين كود الميزات الوظيفية
            Log::warning("AOP Performance Alert: Bottleneck detected!", [
                'url'            => $request->fullUrl(),
                'method'         => $request->method(),
                'execution_time' => round($executionTime, 2) . ' ms',
                'controller'     => $request->route() ? $request->route()->getActionName() : 'Closure',
            ]);
        }

        // حقن النتيجة الرقمية لزمن التنفيذ ضمن الـ Headers لتوثيق القياس (Benchmarking)
        $response->headers->set('X-Server-Execution-Time', round($executionTime, 2) . 'ms');

        return $response;
    }
}