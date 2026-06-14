<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessOrderConfirmation;  
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // استدعاء واجهة الكاش

class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        $productId = (int) $request->input('product_id', 1);
        $quantityToBuy = (int) $request->input('quantity', 1);
        
        $redisStockKey = "product_stock_{$productId}";

        // 1. خط الدفاع الأول (Cache Check): فحص الستوك من الـ RAM (Redis) مباشرة
        // إذا كان الستوك مخزن في كاش Redis وقيمته أقل من الكمية المطلوبة، ارفض فوراً!
        if (Cache::has($redisStockKey)) {
            $cachedStock = (int) Cache::get($redisStockKey);
            if ($cachedStock < $quantityToBuy) {
                return response()->json([
                    'message' => 'Not enough in stock (Rejected swiftly by Redis Cache)...',
                    'debug_info' => [
                        'source' => 'Redis RAM',
                        'requested_quantity' => $quantityToBuy,
                        'available_stock' => $cachedStock,
                        'product_id_received' => $productId
                    ]
                ], 400);
            }
        }

        // 2. خط الدفاع الثاني: إذا مر الطلب من الكاش أو لم يكن الكاش موجوداً بعد، يدخل للـ DB
        return DB::transaction(function () use ($productId, $quantityToBuy, $redisStockKey) {
            
            // القفل التشاؤمي للحماية النهائية من الـ Race Condition
            $product = Product::where('id', $productId)->lockForUpdate()->first();

            if (!$product || $product->stock < $quantityToBuy) {
                $currentStock = $product ? $product->stock : 'Product Not Found';
                
                // تحديث الكاش بالـ 0 لكي يرفض Redis الطلبات القادمة فوراً
                if ($product) {
                    Cache::put($redisStockKey, $product->stock, 60);
                }

                return response()->json([
                    'message' => 'Not enough in stock...',
                    'debug_info' => [
                        'source' => 'MySQL DB',
                        'requested_quantity' => $quantityToBuy,
                        'available_stock' => $currentStock,
                        'product_id_received' => $productId
                    ]
                ], 400);
            }

            // الخصم الحقيقي من قاعدة البيانات
            $product->stock -= $quantityToBuy;
            $product->save();

            // 3. تحديث الكاش (Cache Update): مزامنة الـ Redis فوراً بالقيمة الجديدة للستوك
            // نضعها بصلاحية 60 ثانية (تتحدث مع كل عملية شراء ناجحة)
            Cache::put($redisStockKey, $product->stock, 60);

            // إنشاء الطلب والعناصر
            $order = Order::create([
                'user_id' => 1, 
                'total_price' => $product->price * $quantityToBuy,
                'status' => 'completed'
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantityToBuy,
                'price' => $product->price
            ]);
            
            ProcessOrderConfirmation::dispatch($order);

            return response()->json([
                'message' => 'Purchase complete...',
                'source' => 'MySQL updated & Redis synced',
                'remaining_stock' => $product->stock
            ]);
        });
    }
}