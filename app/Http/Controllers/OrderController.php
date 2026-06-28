<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessOrderConfirmation;  
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{

    public function placeOrder(Request $request)
    {
        $user = \App\Models\User::first();
        if (!$user) {
            return response()->json(['message' => 'No user found'], 500);
        }

        $productId = (int) $request->input('product_id', 1);
        $quantity = (int) $request->input('quantity', 1);
        
        $redisStockKey = "product_stock_{$productId}";
        $order = $this->orderService->placeOrder($user, $productId, $quantity);

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
        
        try {
            $user = \App\Models\User::first();
            $order = $this->orderService->placeOrder($user, $productId, $quantity);
            return response()->json(['message' => 'Success', 'data' => $order], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return DB::transaction(function () use ($productId, $quantityToBuy, $redisStockKey) {
            
            $product = Product::where('id', $productId)->lockForUpdate()->first();

            if (!$product || $product->stock < $quantityToBuy) {
                $currentStock = $product ? $product->stock : 'Product Not Found';
                
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

            $product->stock -= $quantityToBuy;
            $product->save();

            Cache::put($redisStockKey, $product->stock, 60);

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

    /**
     */
    public function purchaseWithDistributedLock(Request $request)
    {
        $productId = $request->input('product_id', 1);
        $lock = Cache::lock('lock:product:' . $productId, 5);
        
        $remainingStock = 0;

        if ($lock->get()) {
            try {
                $purchaseResult = DB::transaction(function () use ($productId, &$remainingStock) {
                    
                    $product = Product::where('id', $productId)->lockForUpdate()->first();

                    if (!$product || $product->stock <= 0) {
                        return false; 
                    }

                    $product->decrement('stock', 1);
                    $product->refresh();
                    $remainingStock = $product->stock;

                    Order::create([
                        'product_id'  => $product->id,
                        'user_id'     => 1,             
                        'total_price' => $product->price * 1,   
                        'status'      => 'completed'
                    ]);

                    return true; 
                });

                if (!$purchaseResult) {
                    Cache::put('product_stock_' . $productId, 0, 60);
                    return response()->json([
                        'status' => 'rejected',
                        'message' => 'Out of stock / Request Blocked'
                    ], 422);
                }

                Cache::put('product_stock_' . $productId, $remainingStock, 60);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Purchase complete safely with Redis Distributed Lock!',
                    'remaining_stock' => $remainingStock
                ]);

            } finally {
                $lock->release();
            }
        }

        return response()->json([
            'status' => 'rejected',
            'message' => 'System Busy / Request Blocked by Redis Distributed Lock'
        ], 423);
    }


    public function purchaseWithoutLock(Request $request)
    {
        $productId = $request->input('product_id', 1);
        $product = Product::find($productId);

        if (!$product || $product->stock <= 0) {
            return response()->json([
                'status' => 'rejected',
                'message' => 'Out of stock / Request Blocked (Traditional Check)'
            ], 422);
        }

        $currentStock = $product->stock; 
        $product->stock = $currentStock - 1;
        $product->save(); 

        Order::create([
            'product_id'  => $product->id,
            'user_id'     => 1,             
            'total_price' => $product->price,   
            'status'      => 'completed'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase processed WITHOUT LOCK!',
            'remaining_stock' => $product->stock 
        ]);
       }
}