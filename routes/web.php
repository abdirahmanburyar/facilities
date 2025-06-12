<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ExpiredController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\DispenceController;
use App\Http\Controllers\BackOrderController;
use App\Http\Controllers\FacilityInventoryMovementController;
use App\Http\Controllers\MonthlyInventoryReportController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;

// Broadcast routes
Broadcast::routes(['middleware' => ['web', 'auth']]);

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
    

    
    // Expired Management Routes
    Route::prefix('expired')->group(function () {
        Route::get('/', [ExpiredController::class, 'index'])->name('expired.index');
        Route::get('/create', [ExpiredController::class, 'create'])->name('expired.create');
        Route::post('/', [ExpiredController::class, 'store'])->name('expired.store');
        Route::get('/{expired}/edit', [ExpiredController::class, 'edit'])->name('expired.edit');
        Route::put('/{expired}', [ExpiredController::class, 'update'])->name('expired.update');
        Route::delete('/{expired}', [ExpiredController::class, 'destroy'])->name('expired.destroy');
        Route::get('/{transfer}/transfer', [ExpiredController::class, 'transfer'])->name('expired.transfer');
        Route::post('/dispose', [ExpiredController::class, 'dispose'])->name('expired.dispose');
    });
   
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

        // Backorder routes
        Route::controller(BackOrderController::class)->prefix('backorders')->group(function () {
            Route::get('/', 'index')->name('backorders.index');
            // backorders.liquidate
            Route::post('/liquidate', 'liquidate')->name('backorders.liquidate');
            // backorders.dispose
            Route::post('/dispose', 'dispose')->name('backorders.dispose');

            // backorders.received
            Route::post('/received', 'received')->name('backorders.received');
            
            // Route::get('/{id}/show', 'show')->name('backorders.show');
            // Route::post('/store', 'store')->name('backorders.store');
            // Route::get('/create', 'create')->name('backorders.create');
            // Route::get('/{backorder}/edit', 'edit')->name('backorders.edit');
            // Route::put('/{backorder}', 'update')->name('backorders.update');
            // Route::delete('/{backorder}', 'destroy')->name('backorders.destroy');
        });

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

            // Transfer Management Routes
    // ->middleware(PermissionMiddleware::class . ':transfer.view')
    Route::prefix('transfers')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('transfers.index');
        Route::get('/{id}/show', [TransferController::class, 'show'])->name('transfers.show');
        Route::get('/create', [TransferController::class, 'create'])->name('transfers.create');
        Route::post('/store', [TransferController::class, 'store'])->name('transfers.store');
        Route::get('/{transfer}/edit', [TransferController::class, 'edit'])->name('transfers.edit');
        Route::put('/{transfer}', [TransferController::class, 'update'])->name('transfers.update');
        Route::delete('/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');
        
        // Transfer Status Change Routes
        Route::post('/{id}/approve', [TransferController::class, 'approve'])->name('transfers.approve');
        Route::post('/{id}/reject', [TransferController::class, 'reject'])->name('transfers.reject');
        Route::post('/{id}/in-process', [TransferController::class, 'inProcess'])->name('transfers.inProcess');
        Route::post('/{id}/dispatch', [TransferController::class, 'dispatch'])->name('transfers.dispatch');
        
        // Route to get available inventories for transfer
        Route::get('/get-inventories', [TransferController::class, 'getInventories'])->name('transfers.getInventories');
               
        
        // Back order functionality
        Route::post('/backorder', [TransferController::class, 'backorder'])->name('transfers.backorder');
        Route::post('/remove-back-order', [TransferController::class, 'removeBackOrder'])->name('transfers.remove-back-order');
        
        // Item status change
        Route::post('/change-item-status', [TransferController::class, 'changeItemStatus'])->name('transfers.changeItemStatus');
        
        // receive transfer
        Route::post('/receive', [TransferController::class, 'receiveTransfer'])->name('transfers.receiveTransfer');
        Route::get('/items/{id}', [TransferController::class, 'destroyItem'])->name('transfers.items.destroy');

        // update transfer item quantity
        Route::post('/update-item', [TransferController::class, 'updateItem'])->name('transfers.update-item');
    });

    // Facility Inventory Movement Routes
    Route::controller(FacilityInventoryMovementController::class)
        ->prefix('facility-inventory-movements')
        ->group(function () {
            Route::get('/', 'index')->name('facility-inventory-movements.index');
            Route::get('/summary', 'summary')->name('facility-inventory-movements.summary');
            Route::get('/export', 'export')->name('facility-inventory-movements.export');
            Route::get('/product-balance', 'productBalance')->name('facility-inventory-movements.product-balance');
            Route::get('/facility/{facility}/movements', 'facilityMovements')->name('facility-inventory-movements.facility');
        });

    // Monthly Inventory Report Routes
    Route::controller(ReportController::class)
        ->prefix('reports')
        ->name('reports.')
        ->group(function () {
            // Reports Dashboard
            Route::get('/', 'index')->name('index');
            
            // Monthly Inventory Report Interface
            Route::get('/monthly-inventory', 'monthlyInventory')->name('monthly-inventory');
            
            // Generate Monthly Inventory Report
            Route::post('/monthly-inventory/generate', 'generateMonthlyReport')->name('monthly-inventory.generate');
            
            // View Monthly Inventory Report
            Route::get('/monthly-inventory/view', 'viewMonthlyReport')->name('monthly-inventory.view');
            
            // Check Report Status
            Route::get('/monthly-inventory/status', 'getReportStatus')->name('monthly-inventory.status');
            
            // Update Report Item
            Route::post('/monthly-inventory/update-item', 'updateReportItem')->name('monthly-inventory.update-item');
            
            // Save Report
            Route::post('/monthly-inventory/save', 'saveReport')->name('monthly-inventory.save');
            
            // Export Reports
            Route::get('/monthly-inventory/export/excel', 'exportMonthlyReportExcel')->name('monthly-inventory.export.excel');
            Route::get('/monthly-inventory/export/pdf', 'exportMonthlyReportPdf')->name('monthly-inventory.export.pdf');
            
            // Report Workflow Routes
            Route::post('/monthly-inventory/submit', [ReportController::class, 'submitMonthlyReport'])->name('monthly-inventory.submit');
            Route::post('/monthly-inventory/start-review', [ReportController::class, 'startMonthlyReportReview'])->name('monthly-inventory.start-review');
            Route::post('/monthly-inventory/approve', [ReportController::class, 'approveMonthlyReport'])->name('monthly-inventory.approve');
            Route::post('/monthly-inventory/reject', [ReportController::class, 'rejectMonthlyReport'])->name('monthly-inventory.reject');
            Route::post('/monthly-inventory/return-to-draft', [ReportController::class, 'returnMonthlyReportToDraft'])->name('monthly-inventory.return-to-draft');
            Route::post('/monthly-inventory/reopen', [ReportController::class, 'reopenMonthlyReport'])->name('monthly-inventory.reopen');
        });

    // Report Routes
    // Route::controller(ReportController::class)
    //     ->prefix('reports')
    //     ->group(function () {
    //         Route::get('/', 'index')->name('reports.index');
    //         Route::get('/create', 'create')->name('reports.create');
    //         Route::post('/store', 'store')->name('reports.store');
    //         Route::get('/{report}/show', 'show')->name('reports.show');
    //         Route::get('/{report}/edit', 'edit')->name('reports.edit');
    //         Route::put('/{report}', 'update')->name('reports.update');
    //         Route::delete('/{report}', 'destroy')->name('reports.destroy');
    //     });
});

require __DIR__.'/auth.php';
