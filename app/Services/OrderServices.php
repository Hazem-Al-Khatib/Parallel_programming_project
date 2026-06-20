<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Exception;

class OrderService
{

    public function placeOrder($user, $productId, $quantity)
    {

        return DB::transaction(function () use ($user, $productId, $quantity) {
            
            $product = Product::where('id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw new Exception("المنتج برقم {$productId} غير موجود");
            }

            if ($product->stock < $quantity) {
                throw new Exception(
                    "لا توجد كمية كافية في المخزون. "
                    . "المطلوب: {$quantity}، المتوفر: {$product->stock}"
                );
            }

            $product->decrement('stock', $quantity);
            $product->refresh();

            $totalPrice = $product->price * $quantity;
            
            $order = Order::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'total_price' => $totalPrice,
                'quantity' => $quantity,
                'status' => 'completed',
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);

            return $order;

        }, $attempts = 3);
    }
    
    public function cancelOrder(Order $order)
    {
        return DB::transaction(function () use ($order) {
            
            if ($order->status === 'cancelled') {
                throw new Exception("الطلب مُلغى بالفعل");
            }

            $product = Product::find($order->product_id);
            if ($product) {
                $product->increment('stock', $order->quantity);
            }

            $order->update(['status' => 'cancelled']);

            return true;
        });
    }
}