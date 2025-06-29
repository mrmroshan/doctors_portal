<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OdooController;
use App\Services\OdooApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\OdooApiException;

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

        Route::get('/prescription/odoo-prescriptions', [PrescriptionController::class,'odoo_index']);


        //Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        
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


});






Route::get('/medications-test', function () {
    try {
        $odooApi = new OdooApi();
        
        // First verify authentication
        $auth = $odooApi->authenticate();
        
        // Then get medications
        $filters = [
            ['active', '=', true],  // Only fetch active medications
            // Add more filters if needed
        ];
        $fields = ['id', 'name', 'default_code', 'list_price'];  // Specify the fields to fetch
        $limit = 10;  // Limit to 10 for testing
        
        $medications = $odooApi->call('/web/dataset/call_kw', [
            'model' => 'product.product',
            'method' => 'search_read',
            'args' => [
                $filters,
                $fields
            ],
            'kwargs' => [
                'limit' => $limit
            ]
        ]);
        
        return response()->json([
            'status' => 'success',
            'environment' => config('app.env'),
            'total_medications' => count($medications),
            'medications' => $medications,
            'auth_status' => $auth ? 'authenticated' : 'failed'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'environment' => config('app.env'),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null
        ], 500);
    }
});




  

Route::get('/get_all_sales_orders/{filter?}', function ($filter = null) {
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

        $salesOrders = $odooApi->call('/web/dataset/call_kw', [
            'model' => 'sale.order',
            'method' => 'search_read',
            'args' => [
                $filters,
                ['id', 'name', 'date_order', 'state', 'amount_total']
            ],
            'kwargs' => [
                'order' => 'date_order desc',
                'limit' => 10
            ]
        ]);

        return response()->json($salesOrders);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});





Route::get('/get_sales_orders_by_doctor/{filter?}', function ($filter = null) {
    try {
        $odooApi = new OdooApi();

        // Base filters - always exclude cancelled orders
        $filters = [
            ['state', '!=', 'cancel']
        ];

        // Add doctor filter if specified
        if ($filter) {
            if (is_numeric($filter)) {
                // Filter by doctor ID
                $filters[] = ['doctor_id', '=', (int) $filter];
            } else {
                // Filter by doctor name
                $doctorId = $odooApi->getDoctorIdByName($filter);
                if ($doctorId) {
                    $filters[] = ['doctor_id', '=', $doctorId];
                } else {
                    return response()->json([
                        'error' => 'Doctor not found'
                    ], 404);
                }
            }
        }

        $salesOrders = $odooApi->call('/web/dataset/call_kw', [
            'model' => 'sale.order',
            'method' => 'search_read',
            'args' => [
                $filters,
                ['id', 'name', 'date_order', 'state', 'amount_total', 'doctor_id']
            ],
            'kwargs' => [
                'order' => 'date_order desc',
                'limit' => 10
            ]
        ]);

        return response()->json($salesOrders);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});



Route::get('/get-sales-order-data/{id}', function ($id) {
    $odooApi = new OdooApi();

    try {
        $saleOrder = $odooApi->getSalesOrder($id);

        if ($saleOrder) {
            return response()->json([
                'success' => true,
                'data' => $saleOrder
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Sales order not found'
            ], 404);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch sales order',
            'error' => $e->getMessage()
        ], 500);
    }
});





// Route to manually update order statuses (admin only)

Route::middleware(['auth', 'role:admin'])->get('/update-order-statuses', function () {
    try {
        Artisan::call('orders:update-status');
        $output = Artisan::output();
        
        return response()->json([
            'success' => true,
            'message' => 'Order statuses updated successfully',
            'details' => $output
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update order statuses',
            'error' => $e->getMessage()
        ], 500);
    }
});





Route::get('/test-create-sales-order', function () {
    $odooApi = new OdooApi();

    try {
        // Prepare order data
        $orderData = [
            'partner_id' => 19938,  // Replace with a valid partner ID
            'date_order' => date('Y-m-d H:i:s'),
            'doctor_id' => 19938,  // Replace with a valid doctor ID
            'patient_phone' => '33377333',
            'patient' => "ADNAN AL ADEEB (SHC)",
            'patient_portal_id' => 111,
            'prescription_reference' => 'TEST-' . time(),  // Add a unique reference
        ];

        // Prepare order lines
        $orderLines = [
            [
                'product_id' => 68717,  // Replace with a valid product ID
                'product_uom_qty' => 2,
                'name' => 'Test Medication 1',
                'price_unit' => 18.85,
                'directions' => 'Take twice daily with food'
            ],
            [
                'product_id' => 54186,  // Replace with a valid product ID
                'product_uom_qty' => 1,
                'name' => 'Test Medication 2',
                'price_unit' => 4,
                'directions' => 'Take once daily before bed'
            ],
        ];

        // Create the sales order using the new standardized method
        $saleOrderId = $odooApi->createPrescriptionOrder($orderData, $orderLines);

        // Fetch the newly created sales order
        $saleOrder = $odooApi->getSalesOrder($saleOrderId);

        return response()->json([
            'success' => true,
            'message' => 'Prescription order created successfully',
            'data' => $saleOrder
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create prescription order',
            'error' => $e->getMessage()
        ], 500);
    }
});




Route::get('/test-create-patient', function () {
    $odooApi = new OdooApi();
    
    $patientData = [
        'name' => 'Test Patient',
        'phone' => '1234567890',
        'email' => 'test@example.com',
    ];
    
    try {
        $partnerId = $odooApi->createPartner($patientData);
        
        // Fetch the newly created patient
        $patient = $odooApi->getPartner($partnerId);
        
        return response()->json([
            'success' => true,
            'message' => 'Patient created successfully',
            'data' => $patient
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create patient',
            'error' => $e->getMessage()
        ], 500);
    }
});





Route::get('/test-latest-patients', function () {
    $odooApi = new OdooApi();
    
    try {
        $latestPatients = $odooApi->getLatestPartners();
        
        return response()->json([
            'success' => true,
            'data' => $latestPatients
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/api/medications/search', function (Request $request) {
    try {
        $searchTerm = $request->get('search');
        
        if (strlen($searchTerm) < 3) {
            return response()->json([
                'error' => 'Search term must be at least 3 characters long'
            ], 422);
        }

        $odooApi = app(OdooApi::class);
        $medications = $odooApi->searchMedications($searchTerm);
        
        return response()->json([
            'success' => true,
            'data' => $medications
        ]);
    } catch (OdooApiException $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
})->name('api.medications.search');

