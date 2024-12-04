<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OdooController;

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
});