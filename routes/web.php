<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EnumeratorController;
use App\Http\Controllers\PaystackController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Register');
});

Route::get('/register-enumerator', function () {
    return Inertia::render('Register');
})->name('register');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
