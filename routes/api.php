<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MedicineSearchController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/medicines/search', [MedicineSearchController::class, 'search'])->name('api.medicines.search');
    Route::get('/medicines/master', [MedicineSearchController::class, 'masterSearch'])->name('api.medicines.master');
    Route::get('/medicines/stats', [MedicineSearchController::class, 'stats'])->name('api.medicines.stats');

    Route::middleware(['role:admin,pharmacist,cashier'])->group(function () {
        Route::post('/medicines/promote', [MedicineSearchController::class, 'promote'])->name('api.medicines.promote');
    });
});
Route::get('/setup', function (\Illuminate\Http\Request $request) {
    set_time_limit(120);
    $output = [];
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output[] = 'Migrations: ' . trim(\Illuminate\Support\Facades\Artisan::output());
    } catch (\Exception $e) {
        $output[] = 'Migration Error: ' . $e->getMessage();
    }
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $output[] = 'Seeding: ' . trim(\Illuminate\Support\Facades\Artisan::output());
    } catch (\Exception $e) {
        $output[] = 'Seed Error: ' . $e->getMessage();
    }
    try {
        $users = \App\Models\User::select('name','email','role')->get();
        $output[] = 'Users in DB: ' . $users->toJson();
    } catch (\Exception $e) {
        $output[] = 'User query error: ' . $e->getMessage();
    }
    return response()->json(['status' => 'Setup Complete', 'details' => $output]);
});
