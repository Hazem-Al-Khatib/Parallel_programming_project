<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDailySalesBatch implements ShouldQueue
{
    use Queueable;
    public function __construct()
    {
    }


public function handle(): void
{

    \App\Models\Order::whereDate('created_at', now()->today())
        ->chunk(100, function ($orders) {
            $batchJobs = [];
            
            foreach ($orders as $order) {
                $batchJobs[] = new \App\Jobs\ProcessOrderConfirmation($order);
            }

            if (count($batchJobs) > 0) {
                \Illuminate\Support\Facades\Bus::batch($batchJobs)->dispatch();
                \Illuminate\Support\Facades\Log::info("تم إطلاق دفعة معالجة متوازية لـ " . count($batchJobs) . " طلب.");
            }
        });

    if (\App\Models\Order::whereDate('created_at', now()->today())->count() == 0) {
        \Illuminate\Support\Facades\Log::warning("لم يتم العثور على طلبات جديدة لمعالجتها اليوم.");
    }
}
}


