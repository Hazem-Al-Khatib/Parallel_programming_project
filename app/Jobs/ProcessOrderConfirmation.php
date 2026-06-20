<?php
namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrderConfirmation implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;


    public function __construct(Order $order)
    {
        $this->order = $order;
    }


    public function handle(): void
    {
        sleep(5); 

        \Log::info("Parallel Processing: تمت معالجة تأكيد الطلب رقم (#{$this->order->id}) بنجاح.");
    }
}