<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DispenceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;

// Broadcast routes
Route::get('/broadcasting/auth', function () {
    return \Laravel\Echo\Broadcasting\Auth::check();
})->middleware('auth')->name('broadcasting.auth');

// Two-Factor Authentication Routes - These must be accessible without 2FA
Route::middleware('auth')->group(function () {
    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])->name('two-factor.resend');
});

// All routes that require 2FA
Route::middleware(['auth', 'verified', \App\Http\Middleware\TwoFactorAuth::class])->group(function () {
    // Dashboard route
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // User Management Routes
    Route::middleware(PermissionMiddleware::class.':user.view')
    ->prefix('users')
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        // Create and Edit routes for navigation purposes
        Route::get('/create', function() {
            return Inertia::render('User/Create');
        })->middleware(PermissionMiddleware::class.':user.create')->name('users.create');
        Route::post('/store', [UserController::class, 'store'])->middleware(PermissionMiddleware::class.':user.create')->name('users.store');
        
        // User roles management
        Route::get('/{user}/roles', [UserController::class, 'showRoles'])
            ->middleware(PermissionMiddleware::class.':user.edit')
            ->name('users.roles');
        Route::delete('/{user}', [UserController::class, 'destroy'])->middleware(PermissionMiddleware::class.':user.delete')->name('users.destroy');
    });
    
    // Role Management Routes
    Route::middleware(PermissionMiddleware::class.':user.view')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles-permissions', [RoleController::class, 'getAllRolesAndPermissions'])->name('roles.get-all');
    });
    Route::post('/roles', [RoleController::class, 'store'])->middleware(PermissionMiddleware::class.':user.create')->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware(PermissionMiddleware::class.':user.edit')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware(PermissionMiddleware::class.':user.delete')->name('roles.destroy');
    Route::post('/users/{user}/roles', [RoleController::class, 'assignRoles'])->middleware(PermissionMiddleware::class.':user.edit')->name('users.roles.assign');
    
   
    // Inventory Routes
    Route::controller(InventoryController::class)
        ->prefix('/inventories')
        ->group(function () {
            Route::get('/', 'index')->middleware(PermissionMiddleware::class.':inventory.view')->name('inventories.index');
            Route::post('/store', 'store')->middleware(PermissionMiddleware::class.':inventory.create')->name('inventories.store');
            Route::put('/{inventory}', 'update')->middleware(PermissionMiddleware::class.':inventory.edit')->name('inventories.update');
            Route::delete('/{inventory}', 'destroy')->middleware(PermissionMiddleware::class.':inventory.delete')->name('inventories.destroy');
            Route::post('/bulk', 'bulk')->middleware(PermissionMiddleware::class.':inventory.delete')->name('inventories.bulk');
        });
    
    // Settings Routes
    Route::middleware(PermissionMiddleware::class.':settings.view')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    });
    // Expired Routes
    Route::controller(ExpiredController::class)
        ->prefix('/expired')->group(function () {
            Route::get('/', 'index')->name('expired.index');
            Route::post('/dispose', 'markAsDisposed')->name('expired.dispose');
        });
    
    Route::post('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::controller(FacilityController::class)
        ->prefix('/facilities')
        ->group(function () {
            Route::get('/', 'index')->name('facilities.index');
            Route::post('/store', 'store')->name('facilities.store');
            Route::delete('/{facility}', 'destroy')->name('facilities.destroy');
        });

        // Route::controller(OrderController::class)
        // ->prefix('/orders')
        // ->group(function () {
        //     Route::get('/', 'index')->name('orders.index');
        //     Route::post('/store', 'store')->name('orders.store');
        //     Route::post('/search', 'search')->name('orders.search');
        //     Route::post('/create', 'createOrder')->name('orders.create');
        //     Route::get('/remove', 'remove')->name('orders.remove');
        //     Route::post('/submit', 'submitOrder')->name('orders.submit');
        //     Route::post('/received-items', 'receivedItems')->name('orders.receivedItems');           
        //     Route::post('/update-items', 'updateItem')->name('orders.update-item');           

        // });

        // Order Management Routes
        Route::controller(OrderController::class)->prefix('orders')->group(function () {
            Route::get('/', 'index')->name('orders.index');
            Route::get('/{id}/show', 'show')->name('orders.show');
            Route::post('/change-status', 'changeItemStatus')->name('orders.change-status');
            Route::post('/reject', 'rejectOrder');

            Route::get('/create', 'create')->name('orders.create');
            Route::post('/store', 'store')->name('orders.store');
            Route::get('/{order}/edit', 'edit')->name('orders.edit');
            Route::put('/{order}', 'update')->name('orders.update');
            Route::delete('/{order}', 'destroy')->name('orders.destroy');

            // facility orders
            Route::get('/create', 'create')->name('orders.create');
            
            // Inventory check
            Route::post('/check/inventory', 'checkInventory')->name('orders.check-inventory');

            // Back order
            Route::post('/backorder', 'backorder')->name('orders.backorder');
            Route::post('/remove-back-order', 'removeBackOrder')->name('orders.remove-back-order');
        });

        Route::controller(DispenceController::class)
        ->prefix('/dispence')
        ->group(function () {
            Route::get('/', 'index')->name('dispence.index');
            Route::get('/create', 'create')->name('dispence.create');
            Route::post('/store', 'store')->name('dispence.store');
            Route::get('/{id}/show', 'show')->name('dispence.show');
        });
});

require __DIR__.'/auth.php';
