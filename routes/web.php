<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OdooController;
use App\Services\OdooApi;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Common routes for authenticated users
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    
    // Doctor routes
    Route::middleware(['role:doctor'])->group(function () {
        Route::get('/doctor/dashboard', [HomeController::class, 'doctorDashboard'])->name('doctor.dashboard');        
        // Moved patients resource to shared group
    });

    // Admin routes
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', [HomeController::class, 'adminDashboard'])->name('admin.dashboard');
        Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        
  

        Route::get('/admin/sync-status', [HomeController::class, 'syncStatus'])->name('admin.sync-status');
    });

    // Routes accessible by both doctor and admin
    Route::middleware(['role:doctor,admin'])->group(function () {
        // Existing routes
        Route::resource('prescriptions', PrescriptionController::class);
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::post('/api/patients', [PatientController::class, 'apiStore'])->name('api.patients.store');
        Route::get('/api/patients/search', [PatientController::class, 'search'])->name('api.patients.search');

        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::get('/users/all', [UserController::class, 'all'])->name('users.all');
        Route::resource('users', UserController::class)->except(['index']);

        // Added patients resource here for both doctor and admin access
        Route::resource('patients', PatientController::class);
    });

    // Odoo routes
    Route::get('/welcome', [OdooController::class, 'welcome']);
    Route::get('/authenticate', [OdooController::class, 'authenticateUser']);
    Route::get('/fetch-users', [OdooController::class, 'fetchUsers']);


    // Add this to a test route to verify configuration
Route::get('/odoo-config-test', function() {
   
    return [
        'environment' => config('app.env'),
        'odoo_url' => config('odoo.url'),
        'odoo_db' => config('odoo.db'),
        'odoo_username' => config('odoo.username'),
        // Don't expose password in production
        'odoo_password' => config('app.env') === 'production' ? '***' : config('odoo.password'),
        
    ];
});
});


// In routes/web.php or via tinker
Route::get('/odoo-env-test', function () {
    return [
        'environment' => config('app.env'),
        'odoo_url' => config('odoo.url'),
        'odoo_db' => config('odoo.db'),
        'odoo_username' => config('odoo.username'),
        'is_production' => config('app.env') === 'production'
    ];
});




// Add these test routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Test Odoo connection and configuration
    Route::get('/odoo-test', function () {
        try {
            $odooApi = new OdooApi();
            
            return [
                'status' => 'success',
                'environment' => config('app.env'),
                'connection' => [
                    'url' => config('odoo.url'),
                    'db' => config('odoo.db'),
                    'username' => config('odoo.username'),
                    'password' => config('app.env') === 'production' ? '***' : '***',
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'environment' => config('app.env')
            ];
        }
    });




    // Test medication list retrieval
    Route::get('/medications-test', function () {
        try {
            $odooApi = new OdooApi();
            
            // First verify authentication
            $auth = $odooApi->authenticate();
            
            // Then get medications
            $medications = $odooApi->getMedicationList([], 10); // Limit to 10 for testing
            
            return [
                'status' => 'success',
                'environment' => config('app.env'),
                'total_medications' => count($medications),
                'medications' => $medications,
                'auth_status' => $auth ? 'authenticated' : 'failed'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'environment' => config('app.env'),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ];
        }
    });





    Route::get('/sales-orders-test/{filter?}', function ($filter = null) {
        try {
            $odooApi = new OdooApi();
            
            // Base filters - always exclude cancelled orders
            $filters = [['state', '!=', 'cancel']];
            
            // Add date filters if specified
            switch($filter) {
                case 'today':
                    $filters[] = ['date_order', '>=', date('Y-m-d 00:00:00')];
                    $filters[] = ['date_order', '<=', date('Y-m-d 23:59:59')];
                    break;
                case 'this-week':
                    $filters[] = ['date_order', '>=', date('Y-m-d 00:00:00', strtotime('monday this week'))];
                    $filters[] = ['date_order', '<=', date('Y-m-d 23:59:59', strtotime('sunday this week'))];
                    break;
                case 'draft':
                    $filters[] = ['state', '=', 'draft'];
                    break;
                case 'confirmed':
                    $filters[] = ['state', '=', 'sale'];
                    break;
            }

            // First get orders
            $orders = $odooApi->search_read(
                'sale.order',
                $filters,
                [
                    'name',
                    'partner_id',
                    'date_order',
                    'amount_total',
                    'state',
                    'order_line'
                ],
                50
            );

            // Then get order lines details
            $orderLineIds = array_merge(...array_map(fn($order) => $order['order_line'], $orders));
            $orderLines = !empty($orderLineIds) ? $odooApi->search_read(
                'sale.order.line',
                [['id', 'in', $orderLineIds]],
                [
                    'order_id',
                    'product_id',
                    'product_uom_qty',
                    'price_unit',
                    'price_total',
                    'name'
                ]
            ) : [];

            // Index order lines by ID for easier lookup
            $orderLinesById = [];
            foreach ($orderLines as $line) {
                $orderLinesById[$line['id']] = $line;
            }
            
            return [
                'status' => 'success',
                'filter_type' => $filter ?? 'all',
                'total_orders' => count($orders),
                'orders' => array_map(function($order) use ($orderLinesById) {
                    return [
                        'id' => $order['id'],
                        'reference' => $order['name'],
                        'customer' => $order['partner_id'] ? [
                            'id' => $order['partner_id'][0],
                            'name' => $order['partner_id'][1]
                        ] : null,
                        'date' => $order['date_order'],
                        'total_amount' => $order['amount_total'],
                        'status' => $order['state'],
                        'order_lines' => array_map(function($lineId) use ($orderLinesById) {
                            $line = $orderLinesById[$lineId] ?? null;
                            return $line ? [
                                'id' => $line['id'],
                                'product' => $line['product_id'] ? [
                                    'id' => $line['product_id'][0],
                                    'name' => $line['product_id'][1]
                                ] : null,
                                'quantity' => $line['product_uom_qty'],
                                'unit_price' => $line['price_unit'],
                                'total_price' => $line['price_total'],
                                'description' => $line['name']
                            ] : null;
                        }, $order['order_line'])
                    ];
                }, $orders)
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'filter_type' => $filter ?? 'all',
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ];
        }
    });





    /*

    Route::get('/sales-orders-test/{filter?}', function ($filter = null) {
        try {
            $odooApi = new OdooApi();
            
            // Base filters - always exclude cancelled orders
            $filters = [
                ['state', '!=', 'cancel']
            ];
            
            // Add date filters if specified
            switch($filter) {
                case 'today':
                    $filters[] = ['date_order', '>=', date('Y-m-d 00:00:00')];
                    $filters[] = ['date_order', '<=', date('Y-m-d 23:59:59')];
                    break;
                case 'this-week':
                    $filters[] = ['date_order', '>=', date('Y-m-d 00:00:00', strtotime('monday this week'))];
                    $filters[] = ['date_order', '<=', date('Y-m-d 23:59:59', strtotime('sunday this week'))];
                    break;
                case 'draft':
                    $filters[] = ['state', '=', 'draft'];
                    break;
                case 'confirmed':
                    $filters[] = ['state', '=', 'sale'];
                    break;
            }

            // Get orders with expanded fields
            $result = $odooApi->search_read(
                'sale.order',
                $filters,
                [
                    'name',
                    'partner_id',
                    'date_order',
                    'amount_total',
                    'state',
                    'order_line'
                ],
                50
            );
            
            return [
                'status' => 'success',
                'filter_type' => $filter ?? 'all',
                'total_orders' => count($result),
                'orders' => array_map(function($order) {
                    return [
                        'id' => $order['id'],
                        'reference' => $order['name'],
                        'customer' => $order['partner_id'] ? [
                            'id' => $order['partner_id'][0],
                            'name' => $order['partner_id'][1]
                        ] : null,
                        'date' => $order['date_order'],
                        'total_amount' => $order['amount_total'],
                        'status' => $order['state'],
                        'order_lines' => $order['order_line']
                    ];
                }, $result)
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'filter_type' => $filter ?? 'all',
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ];
        }
    });

*/


    /*
    // Test sales orders retrieval
    Route::get('/sales-orders-test', function () {
        try {
            $odooApi = new OdooApi();
            
            // Step 1: Test authentication
            $authResult = $odooApi->authenticate();
            
            // Step 2: Try a simple search with minimal filters
            $filters = [
                ['state', '!=', 'cancel']
            ];
            
            // Step 3: Get minimal fields first
            $fields = [
                'name',
                'state',
                'date_order'
            ];
            
            // Step 4: Limit to just a few records
            $result = $odooApi->search_read(
                'sale.order',
                $filters,
                $fields,
                5
            );
            
            return [
                'status' => 'success',
                'auth_result' => $authResult,
                'total_orders' => count($result),
                'orders' => $result,
                'environment' => config('app.env'),
                'odoo_url' => config('odoo.url'),
                'odoo_db' => config('odoo.db')
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'environment' => config('app.env'),
                'odoo_config' => [
                    'url' => config('odoo.url'),
                    'db' => config('odoo.db'),
                    'username' => config('odoo.username'),
                    // Don't expose password in production
                    'password_length' => strlen(config('odoo.password'))
                ],
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ];
        }
    });
    */

});