<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Jobs\ProcessDailySalesBatch;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/buy', [OrderController::class, 'placeOrder'])->middleware('throttle:purchase_limit');

Route::middleware(['load.balancer'])->group(function () {
    Route::get('/start-parallel-test', function () {
        \App\Jobs\ProcessDailySalesBatch::dispatch();
        return "تم إطلاق المهام بالتوازي.. راقب السجلات!";
    });
    
});




