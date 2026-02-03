<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/ready', [HealthController::class, 'ready']);

Route::get('/', function () {
    return view('welcome');
});

// Admin routes - protected by admin role
Route::middleware(['auth', 'role:admin|super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Admin Dashboard',
            'user' => auth()->user(),
        ]);
    })->name('dashboard');
});
