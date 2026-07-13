<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// La raíz del sitio ahora muestra directamente la pantalla de Login de NexusFlow
Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

// Todas las rutas protegidas del sistema bajo un mismo grupo middleware
Route::middleware(['auth', 'verified'])->group(function () {

    // Registra automáticamente index, create, show, edit, etc. para el POS
    Route::resource('pos', PosController::class)
        ->only(['index', 'create', 'store'])
        ->names([
            'index'  => 'dashboard',
            'create' => 'inventory.index',
        ]);

    Route::get('/pos/analytics', [PosController::class, 'analytics'])
        ->name('analytics.index');

    Route::get('/pos/catalog', [PosController::class, 'catalog'])
        ->name('catalog.index');

    // Rutas del Perfil de Usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('product', ProductController::class)->names([
        'index' => 'products.index'
    ]);

    // Route::post('/products/quick-create', [ProductController::class, 'quickCreate'])
    //     ->name('products.quickCreate');

    Route::post('/products/import', [ProductController::class, 'import'])
        ->name('products.import');

    Route::get('/products/search-barcode',[ProductController::class, 'searchBarcode'])
        ->name('products.barcode.search');
});

// Carga el resto de las rutas de autenticación (como el POST de login, logout, etc.)
require __DIR__.'/auth.php';
