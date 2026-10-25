<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/kasir', [SaleController::class, 'pos'])->name('sales.pos');
    Route::post('/kasir/keranjang', [SaleController::class, 'addToCart'])->name('sales.cart.add');
    Route::patch('/kasir/keranjang/{product}', [SaleController::class, 'updateCart'])->name('sales.cart.update');
    Route::delete('/kasir/keranjang/{product}', [SaleController::class, 'removeFromCart'])->name('sales.cart.remove');
    Route::post('/kasir/checkout', [SaleController::class, 'checkout'])->name('sales.checkout');

    Route::get('/transaksi', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/transaksi/{sale}/struk', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::get('/transaksi/{sale}', [SaleController::class, 'show'])->name('sales.show');

    Route::middleware('admin')->group(function () {
        Route::resource('kategori', CategoryController::class)
            ->except('show')
            ->parameters(['kategori' => 'category'])
            ->names('categories');
        Route::resource('produk', ProductController::class)
            ->parameters(['produk' => 'product'])
            ->names('products');
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::resource('pengguna', UserController::class)
            ->except('show')
            ->parameters(['pengguna' => 'user'])
            ->names('users');
    });
});
