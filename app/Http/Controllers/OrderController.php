<?php

namespace App\Http\Controllers;
use App\Jobs\ProcessOrderConfirmation;  
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        $productId = (int) $request->input('product_id', 1);
        $quantityToBuy = (int) $request->input('quantity', 1);

        return DB::transaction(function () use ($productId, $quantityToBuy) {
            
            $product = Product::where('id', $productId)->lockForUpdate()->first();

            if (!$product || $product->stock < $quantityToBuy) {
                $currentStock = $product ? $product->stock : 'Product Not Found';
                return response()->json([
                    'message' => 'Not enough in stock...',
                    'debug_info' => [
                        'requested_quantity' => $quantityToBuy,
                        'available_stock' => $currentStock,
                        'product_id_received' => $productId
                    ]
                ], 400);
            }

            $product->stock -= $quantityToBuy;
            $product->save();

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
                'remaining_stock' => $product->stock
            ]);
        });
    }
}