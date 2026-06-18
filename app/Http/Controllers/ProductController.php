<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * جلب قائمة المنتجات بالكامل مع تطبيق استراتيجية الكاش (Cache-Aside Pattern)
     */
    public function index(\Illuminate\Http\Request $request)
{
    $cacheKey = 'all_products_list';

    // 🚨 حركة هندسية: إذا السكربت طلب تصفير الكاش، امسحه فوراً من الـ Redis RAM غصب عنه!
    if ($request->has('clear_cache')) {
        Cache::forget($cacheKey);
    }

    // التحقق من وجود الكاش قبل جلب البيانات لتحديد المصدر بدقة
    $isCached = Cache::has($cacheKey);

    $products = Cache::remember($cacheKey, 86400, function () {
        return Product::all();
    });

    $source = $isCached ? 'Fetched from Redis RAM (Cache Hit - Super Fast!)' : 'Fetched from MySQL Database (Cache Miss - First Time)';

    return response()->json([
        'status' => 'success',
        'source' => $source,
        'count' => $products->count(),
        'data' => $products
    ]);
}
}