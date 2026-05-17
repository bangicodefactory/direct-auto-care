<?php

use App\Http\Controllers\CustomersController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceRecordsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VehiclesController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/style-guide', function () {
    return Inertia::render('StyleGuide');
})->name('style-guide');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::resource('products', ProductsController::class)
    ->middleware(['auth', 'verified']);

Route::post('products/{product}/adjust-stock', [ProductsController::class, 'adjustStock'])
    ->middleware(['auth', 'verified'])
    ->name('products.adjust-stock');

Route::resource('invoices', InvoicesController::class)
    ->middleware(['auth', 'verified']);

Route::resource('customers', CustomersController::class)
    ->middleware(['auth', 'verified']);

Route::resource('users', UsersController::class)
    ->middleware(['auth', 'verified'])
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

// Vehicles — global index
Route::get('/vehicles', [VehiclesController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('vehicles.index');

// Vehicles — standalone create + store (customer selected from dropdown)
Route::get('/vehicles/create', [VehiclesController::class, 'standaloneCreate'])
    ->middleware(['auth', 'verified'])
    ->name('vehicles.create');
Route::post('/vehicles', [VehiclesController::class, 'standaloneStore'])
    ->middleware(['auth', 'verified'])
    ->name('vehicles.store');

// Vehicles — create + store nested under customer (customer_id comes from route)
Route::resource('customers.vehicles', VehiclesController::class)
    ->middleware(['auth', 'verified'])
    ->only(['create', 'store']);

// Vehicles — show, edit, update, destroy as top-level resources
Route::resource('vehicles', VehiclesController::class)
    ->middleware(['auth', 'verified'])
    ->except(['index', 'create', 'store']);

// Service Records — shallow nested under vehicle
Route::resource('vehicles.service-records', ServiceRecordsController::class)
    ->middleware(['auth', 'verified'])
    ->only(['create', 'store', 'show', 'edit', 'update', 'destroy'])
    ->shallow();

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
