<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
{
    $cacheKey = 'all_products_list';

    if ($request->has('clear_cache')) {
        Cache::forget($cacheKey);
    }
    $isCached = Cache::has($cacheKey);
    $products = Cache::remember($cacheKey, 86400, function () {
        return Product::all();
    });

    $source = $isCached ? 'Fetched from Redis RAM (Cache Hit - Super Fast!)' 
    : 'Fetched from MySQL Database (Cache Miss - First Time)';
    return response()->json([
        'status' => 'success',
        'source' => $source,
        'count' => $products->count(),
        'data' => $products
    ]);
}
}