<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportStockMovementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('dashboard'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::post('stock-movements/import', ImportStockMovementController::class)->name('stock-movements.import');
});

require __DIR__.'/settings.php';
