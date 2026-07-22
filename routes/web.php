<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // POS
    Route::resource('pos', PosController::class)
        ->only(['index', 'create', 'store']);

    // Rutas del Perfil de Usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Productos (Es mejor definir las rutas personalizadas ANTES del resource)
    Route::post('/products/import', [ProductController::class, 'import'])
        ->name('products.import');

    Route::get('/products/search-barcode', [ProductController::class, 'searchBarcode'])
        ->name('products.barcode.search');

    Route::resource('products', ProductController::class);

    // Inventarios
    Route::post('/inventories/adjust', [InventoryController::class, 'adjustStock'])
        ->name('inventories.adjust');

    Route::resource('inventories', InventoryController::class);
});

// Carga el resto de las rutas de autenticación
require __DIR__.'/auth.php';
