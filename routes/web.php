<?php

use App\Http\Controllers\AppointmentsController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FleetNotesController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RemindersController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\ServiceOrderItemController;
use App\Http\Controllers\ServiceRecordsController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VehicleNotesController;
use App\Http\Controllers\VehiclesController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/style-guide', function () {
    return Inertia::render('StyleGuide');
})->name('style-guide');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

// Products — bulk import (before the {product} binding so /products/import isn't captured)
Route::get('products/import/template', [ProductsController::class, 'importTemplate'])
    ->middleware(['auth', 'verified'])
    ->name('products.import.template');
Route::post('products/import', [ProductsController::class, 'import'])
    ->middleware(['auth', 'verified'])
    ->name('products.import');

Route::resource('products', ProductsController::class)
    ->middleware(['auth', 'verified']);

Route::post('products/{product}/adjust-stock', [ProductsController::class, 'adjustStock'])
    ->middleware(['auth', 'verified'])
    ->name('products.adjust-stock');

Route::resource('services', ServicesController::class)
    ->except(['show'])
    ->middleware(['auth', 'verified']);

// Workshop — service orders (Module 12). `create` before `{serviceOrder}` binding.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('workshop', [ServiceOrderController::class, 'index'])->name('workshop.index');
    Route::get('workshop/create', [ServiceOrderController::class, 'create'])->name('workshop.create');
    Route::post('workshop', [ServiceOrderController::class, 'store'])->name('workshop.store');
    Route::get('workshop/{serviceOrder}', [ServiceOrderController::class, 'show'])->name('workshop.show');
    Route::put('workshop/{serviceOrder}', [ServiceOrderController::class, 'update'])->name('workshop.update');
    Route::patch('workshop/{serviceOrder}/advance', [ServiceOrderController::class, 'advance'])->name('workshop.advance');
    Route::patch('workshop/{serviceOrder}/cancel', [ServiceOrderController::class, 'cancel'])->name('workshop.cancel');
    Route::post('workshop/{serviceOrder}/items', [ServiceOrderItemController::class, 'store'])->name('workshop.items.store');
    Route::delete('workshop/{serviceOrder}/items/{item}', [ServiceOrderItemController::class, 'destroy'])->name('workshop.items.destroy');
});

// Notes flotte — per-vehicle notes logbook for fleet clients.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('fleet-notes', [FleetNotesController::class, 'index'])->name('fleet-notes.index');
    Route::get('fleet-notes/{vehicle}', [FleetNotesController::class, 'show'])->name('fleet-notes.show');
    Route::post('fleet-notes/{vehicle}/notes', [VehicleNotesController::class, 'store'])->name('fleet-notes.notes.store');
    Route::put('vehicle-notes/{vehicleNote}', [VehicleNotesController::class, 'update'])->name('vehicle-notes.update');
    Route::delete('vehicle-notes/{vehicleNote}', [VehicleNotesController::class, 'destroy'])->name('vehicle-notes.destroy');
});

Route::resource('invoices', InvoicesController::class)
    ->middleware(['auth', 'verified']);

Route::post('invoices/{invoice}/convert', [InvoicesController::class, 'convert'])
    ->middleware(['auth', 'verified'])
    ->name('invoices.convert');

Route::post('invoices/{invoice}/issue', [InvoicesController::class, 'issue'])
    ->middleware(['auth', 'verified'])
    ->name('invoices.issue');

Route::post('invoices/{invoice}/pay', [InvoicesController::class, 'markPaid'])
    ->middleware(['auth', 'verified'])
    ->name('invoices.pay');

Route::post('invoices/{invoice}/cancel', [InvoicesController::class, 'cancel'])
    ->middleware(['auth', 'verified'])
    ->name('invoices.cancel');

Route::get('invoices/{invoice}/pdf', [InvoicePdfController::class, 'pdf'])
    ->middleware(['auth', 'verified'])
    ->name('invoices.pdf');

Route::get('invoices/{invoice}/print', [InvoicePdfController::class, 'print'])
    ->middleware(['auth', 'verified'])
    ->name('invoices.print');

// Must be registered before the resource route to take precedence over {appointment} binding.
Route::get('appointments/today', [AppointmentsController::class, 'todayPage'])
    ->middleware(['auth', 'verified'])
    ->name('appointments.today-page');

Route::resource('appointments', AppointmentsController::class)
    ->middleware(['auth', 'verified']);

Route::post('appointments/{appointment}/cancel', [AppointmentsController::class, 'cancel'])
    ->middleware(['auth', 'verified'])
    ->name('appointments.cancel');

Route::post('appointments/{appointment}/complete', [AppointmentsController::class, 'complete'])
    ->middleware(['auth', 'verified'])
    ->name('appointments.complete');

Route::post('appointments/{appointment}/check-in', [AppointmentsController::class, 'checkIn'])
    ->middleware(['auth', 'verified'])
    ->name('appointments.check-in');

Route::get('appointments-today', [AppointmentsController::class, 'today'])
    ->middleware(['auth', 'verified'])
    ->name('appointments.today');

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

// Vehicles — bulk import (before the {vehicle} binding)
Route::get('/vehicles/import/template', [VehiclesController::class, 'importTemplate'])
    ->middleware(['auth', 'verified'])
    ->name('vehicles.import.template');
Route::post('/vehicles/import', [VehiclesController::class, 'import'])
    ->middleware(['auth', 'verified'])
    ->name('vehicles.import');

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

Route::get('reminders', [RemindersController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('reminders.index');

Route::post('reminders/{reminder}/trigger', [RemindersController::class, 'trigger'])
    ->middleware(['auth', 'verified'])
    ->name('reminders.trigger');

Route::post('reminders/{reminder}/cancel', [RemindersController::class, 'cancel'])
    ->middleware(['auth', 'verified'])
    ->name('reminders.cancel');

// Notifications
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/notifications/{id}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('notifications.read-all');
});

// Dynamic branding CSS — public (cached by browser, no auth needed)
Route::get('/branding.css', [BrandingController::class, 'css'])->name('branding-css');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/locale', [ProfileController::class, 'updateLocale'])->name('profile.locale');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
