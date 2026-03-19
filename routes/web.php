<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EnumeratorController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExternalMembersController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Register');
});

Route::get('/register-enumerator', function () {
    return Inertia::render('Register');
})->name('enumerator.register');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout')->middleware('auth:admin');
    
    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/enumerators', [AdminController::class, 'enumerators'])->name('enumerators');
        Route::get('/enumerator-performance', [AdminController::class, 'enumeratorPerformance'])->name('enumerator.performance');
        Route::get('/enumerator/{code}/members', [AdminController::class, 'showEnumeratorMembers'])->name('enumerator.members');
        Route::get('/enumerators/{enumerator}', [AdminController::class, 'showEnumerator'])->name('enumerators.show');
    });
});

// Enumerator API routes
Route::prefix('api/enumerator')->group(function () {
    Route::get('/lgas', [EnumeratorController::class, 'getLGAs']);
    Route::get('/wards', [EnumeratorController::class, 'getWardsByLGA']);
    Route::get('/polling-units', [EnumeratorController::class, 'getPollingUnitsByWard']);
    Route::get('/count', [EnumeratorController::class, 'getCount']);
    Route::post('/register', [EnumeratorController::class, 'register']);
});

// Paystack API routes
Route::prefix('api/paystack')->group(function () {
    Route::get('/banks', [PaystackController::class, 'listBanks']);
    Route::get('/resolve-account', [PaystackController::class, 'resolveAccount']);
});

// External Members API routes
Route::prefix('api/external-members')->group(function () {
    Route::get('/', [ExternalMembersController::class, 'index']);
    Route::get('/statistics', [ExternalMembersController::class, 'statistics']);
    Route::get('/test-connection', [ExternalMembersController::class, 'testConnection']);
    Route::get('/{id}', [ExternalMembersController::class, 'show']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
