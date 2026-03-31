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
})->name('dashboard');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
    
    // Admin routes without authentication
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/enumerators', [AdminController::class, 'enumerators'])->name('enumerators');
    Route::get('/enumerator-performance', [AdminController::class, 'enumeratorPerformance'])->name('enumerator.performance');
    Route::get('/enumerator/{code}/members', [AdminController::class, 'showEnumeratorMembers'])->name('enumerator.members');
    Route::get('/enumerators/export', [AdminController::class, 'exportEnumerators'])->name('enumerators.export');
    Route::get('/enumerators/{enumerator}', [AdminController::class, 'showEnumerator'])->name('enumerators.show');
    Route::put('/enumerators/{enumerator}', [AdminController::class, 'updateEnumerator'])->name('enumerators.update');
    Route::get('/data-sub', [AdminController::class, 'dataSub'])->name('data.sub');
    Route::get('/data-sub-transactions', [AdminController::class, 'dataSubTransactions'])->name('data.sub.transactions');
    Route::post('/send-batch-data', [AdminController::class, 'sendBatchData'])->name('send.batch.data');
    Route::post('/send-individual-data', [AdminController::class, 'sendIndividualData'])->name('send.individual.data');
    Route::post('/mark-all-completed', [AdminController::class, 'markAllCompleted'])->name('mark.all.completed');
    Route::post('/revert-today-manual-completions', [AdminController::class, 'revertTodayManualCompletions'])->name('revert.today.manual.completions');
    Route::post('/retry-transaction', [AdminController::class, 'retryTransaction'])->name('retry.transaction');
    Route::get('/failed-transactions', [AdminController::class, 'getFailedTransactions'])->name('failed.transactions');
    Route::get('/data-plan-management', [AdminController::class, 'dataPlanManagement'])->name('data.plan.management');
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

// Profile routes without authentication
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

// Authentication routes disabled - no login required
// require __DIR__.'/auth.php';
