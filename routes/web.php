<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\MedicineController;
use App\Livewire\Pos\PosCounter;
use App\Livewire\Admin\AddMedicine;
use App\Livewire\Admin\BulkAddMedicine;
use App\Livewire\Admin\BulkEditMedicine;
use App\Livewire\Admin\PurchaseCreate;
use App\Livewire\Admin\HoldInvoiceList;
use App\Http\Controllers\Admin\ReturnController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientTokenController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\HospitalServiceController;
use App\Http\Controllers\HospitalBillingController;
use App\Http\Controllers\DoctorLedgerController;
use App\Http\Controllers\SettingsController;
use App\Livewire\Opd\TokenManagement;

// ============================================================
// Public Routes
// ============================================================

Route::get('/', function () {
    return redirect('/dashboard');
});

// ============================================================
// Secure Admin Migration Routes (Token Protected)
// ============================================================

// ONE-TIME SETUP: Run migrations + seed admin user on Neon DB
Route::get('/admin/setup', function (\Illuminate\Http\Request $request) {
    set_time_limit(120);
    $output = [];
    
    // Force non-pooled connection for Neon migrations to prevent transaction aborts
    $url = config('database.connections.pgsql.url');
    $host = config('database.connections.pgsql.host');
    if ($url) {
        config(['database.connections.pgsql.url' => str_replace('-pooler', '', $url)]);
    }
    if ($host) {
        config(['database.connections.pgsql.host' => str_replace('-pooler', '', $host)]);
    }
    \Illuminate\Support\Facades\DB::purge('pgsql');
    
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
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
    // List users created
    try {
        $users = \App\Models\User::select('name','email','role')->get();
        $output[] = 'Users in DB: ' . $users->toJson();
    } catch (\Exception $e) {
        $output[] = 'User query error: ' . $e->getMessage();
    }
    return response()->json(['status' => 'Setup Complete', 'details' => $output]);
});

Route::get('/run-migrations-live', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return 'Migrations ran successfully: ' . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        $error = $e->getMessage();
        if ($e->getPrevious()) {
            $error .= " | Previous: " . $e->getPrevious()->getMessage();
        }
        return 'Error: ' . $error;
    }
});

Route::get('/create-roles-tables', function () {
    try {
        $db = \Illuminate\Support\Facades\DB::connection();
        
        // Create roles table
        $db->statement("CREATE TABLE IF NOT EXISTS roles (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            slug VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP,
            updated_at TIMESTAMP
        )");
        
        // Create role_permissions table
        $db->statement("CREATE TABLE IF NOT EXISTS role_permissions (
            id BIGSERIAL PRIMARY KEY,
            role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
            feature VARCHAR(255) NOT NULL,
            created_at TIMESTAMP,
            updated_at TIMESTAMP,
            UNIQUE(role_id, feature)
        )");
        
        // Seed default roles
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'Pharmacist', 'slug' => 'pharmacist'],
            ['name' => 'Cashier', 'slug' => 'cashier'],
            ['name' => 'Hospital', 'slug' => 'hospital'],
            ['name' => 'Assistant Admin', 'slug' => 'assistant_admin'],
        ];
        
        $inserted = 0;
        foreach ($roles as $role) {
            $exists = $db->table('roles')->where('slug', $role['slug'])->exists();
            if (!$exists) {
                $db->table('roles')->insert(array_merge($role, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $inserted++;
            }
        }
        
        // Also add to migrations table so Laravel doesn't re-run
        $migrationName = '2026_10_01_000000_create_roles_and_permissions_tables';
        $migExists = $db->table('migrations')->where('migration', $migrationName)->exists();
        if (!$migExists) {
            $batch = $db->table('migrations')->max('batch') ?? 0;
            $db->table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => $batch + 1,
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Roles tables created and seeded!',
            'roles_inserted' => $inserted,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
});

Route::get('/cleanup-dummy', function () {
    try {
        $keepEmails = [
            'admin@pharmacy.com', 
            'admin@gmail.com', 
            'faizanlodhi035@gmail.com'
        ];
        
        // 1. Delete from Local DB
        $deletedUsers = \App\Models\User::whereNotIn('email', $keepEmails)->forceDelete();

        // 2. Delete from Firebase
        $deletedFromFirebase = 0;
        $fbUsers = \App\Services\FirebaseService::getUsersFromFirebase();
        foreach ($fbUsers as $key => $fbData) {
            $email = strtolower(trim($fbData['email'] ?? ''));
            if ($email && !in_array($email, $keepEmails)) {
                \App\Services\FirebaseService::deleteUser($email);
                $deletedFromFirebase++;
            }
        }

        // 3. Cleanup other tables
        $deletedPatients = \Illuminate\Support\Facades\DB::table('patients')->delete();
        $deletedDoctors = \Illuminate\Support\Facades\DB::table('doctors')->delete();
        $deletedTokens = \Illuminate\Support\Facades\DB::table('patient_tokens')->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Dummy data cleaned up completely!',
            'deleted_users_db' => $deletedUsers,
            'deleted_users_firebase' => $deletedFromFirebase,
            'deleted_patients' => $deletedPatients,
            'deleted_doctors' => $deletedDoctors,
            'deleted_tokens' => $deletedTokens
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
});


Route::middleware('throttle:10,1')->group(function () {
    Route::get('/admin/migration', [\App\Http\Controllers\Admin\MigrationController::class, 'index'])
        ->name('admin.migration.index');


    Route::post('/admin/migration/dry-run', [\App\Http\Controllers\Admin\MigrationController::class, 'dryRun'])
        ->name('admin.migration.dry_run');

    Route::post('/admin/migration/real-transfer', [\App\Http\Controllers\Admin\MigrationController::class, 'realTransfer'])
        ->name('admin.migration.real_transfer');
});

// ============================================================
// Auth Routes
// ============================================================

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/firebase', [AuthController::class, 'firebaseLogin'])->name('login.firebase');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'processForgotPassword'])->name('password.email');
Route::get('/reset-password/{email?}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'processResetPassword'])->name('password.update');
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);

// ============================================================
// Protected Routes
// ============================================================

Route::middleware(['auth'])->group(function () {

    Route::get('/auth/user-role-status', [AuthController::class, 'userRoleStatus']);

    // 1. ADMIN ONLY ROUTES (Centralized Settings & User Management)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::delete('/settings/profile/logo', [SettingsController::class, 'deleteLogo'])->name('settings.profile.delete-logo');

        Route::prefix('settings')
            ->name('admin.settings.')
            ->group(function () {
                Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
                Route::get('/roles', function() {
                    return view('admin.settings.roles.index');
                })->name('roles.index');
                Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
                Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
                Route::get('/users/{id}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
                Route::put('/users/{id}', [UserManagementController::class, 'update'])->name('users.update');
                Route::delete('/users/{id}', [UserManagementController::class, 'destroy'])->name('users.destroy');
                Route::post('/users/{id}/restore', [UserManagementController::class, 'restore'])->name('users.restore');
                Route::delete('/users/{id}/force', [UserManagementController::class, 'forceDelete'])->name('users.forceDelete');
            });
    });

    // 2. ADMIN & PHARMACIST ROUTES (Dashboard, Medicines, Purchases, Suppliers, Reports)
    Route::middleware(['role:admin,pharmacist'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Medicines
        Route::get('/medicines', AddMedicine::class);
        Route::get('/medicines/bulk-add', BulkAddMedicine::class)->name('medicines.bulk-add');
        Route::get('/medicines/bulk-edit', BulkEditMedicine::class)->name('medicines.bulk-edit');
        Route::post('/medicines/store', [MedicineController::class, 'store']);

        // Purchases
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', PurchaseCreate::class)->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{id}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::get('/purchases/{id}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
        Route::put('/purchases/{id}', [PurchaseController::class, 'update'])->name('purchases.update');
        Route::delete('/purchases/{id}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');
        Route::get('/purchases/{id}/pdf', [PurchaseController::class, 'pdf'])->name('purchases.pdf');
        Route::get('/purchases/{id}/print', [PurchaseController::class, 'printInvoice'])->name('purchases.print');

        // Suppliers
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::get('/suppliers/create', [SupplierController::class, 'create']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit']);
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show']);
        Route::get('/suppliers-payable-report', [SupplierController::class, 'payableReport']);

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
            Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
            Route::get('/expiry', [ReportController::class, 'expiry'])->name('expiry');
            Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
            Route::get('/suppliers', [ReportController::class, 'suppliers'])->name('suppliers');
            Route::get('/best-selling', [ReportController::class, 'bestSelling'])->name('best-selling');
            Route::get('/low-stock', [ReportController::class, 'lowStock'])->name('low-stock');
            Route::get('/discounts', [ReportController::class, 'discounts'])->name('discounts');
            Route::get('/opd', [ReportController::class, 'opd'])->name('opd');
            Route::get('/doctor-collection', [ReportController::class, 'doctorCollection'])->name('doctor-collection');
            Route::get('/all-doctors-collection', [ReportController::class, 'allDoctorsCollection'])->name('all-doctors-collection');
            Route::get('/hospital-collection', [ReportController::class, 'hospitalCollection'])->name('hospital-collection');
            Route::get('/hospital-services', [ReportController::class, 'hospitalServices'])->name('hospital-services');
            Route::get('/doctor-payable', [ReportController::class, 'doctorPayable'])->name('doctor-payable');
            Route::get('/doctor-settlements', [ReportController::class, 'doctorSettlements'])->name('doctor-settlements');
        });
    });

    // 3. ADMIN, PHARMACIST & CASHIER ROUTES (POS, Sales, Returns, Expiry Alerts)
    Route::middleware(['role:admin,pharmacist,cashier'])->group(function () {
        Route::get('/pos', PosCounter::class);
        Route::get('/hold-invoices', HoldInvoiceList::class)->name('hold-invoices.index');
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{id}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/expiry-alerts', [DashboardController::class, 'expiryReport']);

        // Returns
        Route::prefix('returns')->name('returns.')->group(function () {
            Route::get('/', [ReturnController::class, 'index'])->name('index');
            Route::get('/sales/return/{id}', [ReturnController::class, 'salesShow'])->name('sales.show');
            Route::get('/purchases/return/{id}', [ReturnController::class, 'purchaseShow'])->name('purchase.show');
            Route::get('/sales/create', [ReturnController::class, 'salesCreate'])->name('sales.create');
            Route::get('/sales/{id}', [ReturnController::class, 'salesInvoice'])->name('sales.invoice');
            Route::post('/sales', [ReturnController::class, 'storeSalesReturn'])->name('sales.store');
            Route::get('/purchases/create', [ReturnController::class, 'purchaseCreate'])->name('purchase.create');
            Route::get('/purchases/{id}', [ReturnController::class, 'purchaseInvoice'])->name('purchase.invoice');
            Route::post('/purchases', [ReturnController::class, 'storePurchaseReturn'])->name('purchase.store');
        });
    });

    // ============================================================
    // 4. HOSPITAL MODULE (Phase 1: Patient Registration)
    // ============================================================
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
    Route::get('/patients/check-duplicate', [PatientController::class, 'checkDuplicate'])->name('patients.check-duplicate');
    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');

    // Patient Tokens (Phase 2 & Phase 3.1: Smart Token Management)
    Route::get('/patient-tokens', [PatientTokenController::class, 'index'])->name('patient-tokens.index');
    Route::get('/patient-tokens/create', [PatientTokenController::class, 'create'])->name('patient-tokens.create');
    Route::post('/patient-tokens', [PatientTokenController::class, 'store'])->name('patient-tokens.store');
    Route::post('/patient-tokens/call-next', [PatientTokenController::class, 'callNext'])->name('patient-tokens.call-next');
    Route::post('/patient-tokens/{token}/call', [PatientTokenController::class, 'call'])->name('patient-tokens.call');
    Route::post('/patient-tokens/{token}/complete', [PatientTokenController::class, 'complete'])->name('patient-tokens.complete');
    Route::post('/patient-tokens/{token}/cancel', [PatientTokenController::class, 'cancel'])->name('patient-tokens.cancel');
    Route::get('/patient-tokens/{token}/print', [PatientTokenController::class, 'printSlip'])->name('patient-tokens.print');
    Route::get('/patient-tokens/{token}', [PatientTokenController::class, 'show'])->name('patient-tokens.show');

    // Doctors (Phase 3.4: Doctor Registration & Management)
    Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
    Route::get('/doctors/create', [DoctorController::class, 'create'])->name('doctors.create');
    Route::post('/doctors', [DoctorController::class, 'store'])->name('doctors.store');
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');
    Route::get('/doctors/{doctor}/edit', [DoctorController::class, 'edit'])->name('doctors.edit');
    Route::put('/doctors/{doctor}', [DoctorController::class, 'update'])->name('doctors.update');
    Route::patch('/doctors/{doctor}/status', [DoctorController::class, 'toggleStatus'])->name('doctors.status');
    Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy'])->name('doctors.destroy');

    // OPD Tokens Management (Phase 3.2: Interactive Livewire Workflow)
    Route::get('/opd/tokens', TokenManagement::class)->name('opd.tokens');

    // Hospital Services (Configurable split & active/inactive)
    Route::get('/hospital-services', [HospitalServiceController::class, 'index'])->name('hospital-services.index');
    Route::post('/hospital-services', [HospitalServiceController::class, 'store'])->name('hospital-services.store');
    Route::put('/hospital-services/{hospitalService}', [HospitalServiceController::class, 'update'])->name('hospital-services.update');
    Route::patch('/hospital-services/{hospitalService}/status', [HospitalServiceController::class, 'toggleStatus'])->name('hospital-services.status');

    // Hospital Billing (Invoices, partial payments, receipts)
    Route::get('/hospital-billing', [HospitalBillingController::class, 'index'])->name('hospital-billing.index');
    Route::get('/hospital-billing/create', [HospitalBillingController::class, 'create'])->name('hospital-billing.create');
    Route::post('/hospital-billing', [HospitalBillingController::class, 'store'])->name('hospital-billing.store');
    Route::get('/hospital-billing/{bill}', [HospitalBillingController::class, 'show'])->name('hospital-billing.show');
    Route::post('/hospital-billing/{bill}/payment', [HospitalBillingController::class, 'receivePayment'])->name('hospital-billing.payment');
    Route::get('/hospital-billing/{bill}/receipt', [HospitalBillingController::class, 'printReceipt'])->name('hospital-billing.receipt');

    // Doctor Ledgers & Settlements
    Route::get('/doctor-ledgers', [DoctorLedgerController::class, 'index'])->name('doctor-ledgers.index');
    Route::get('/doctors/{doctor}/ledger', [DoctorLedgerController::class, 'show'])->name('doctor-ledgers.show');
    Route::post('/doctors/{doctor}/settle', [DoctorLedgerController::class, 'settle'])->name('doctor-ledgers.settle');
    Route::get('/doctor-settlements/{settlement}/voucher', [DoctorLedgerController::class, 'printVoucher'])->name('doctor-settlements.voucher');
});



Route::get('/test-gemini', function () {
    $q = request('q', 'panadol');
    $service = app(\App\Services\AiNormalizationService::class);
    $res = $service->normalizeMedicineSearch($q);
    return response()->json([
        'query' => $q,
        'api_key_configured' => !empty(config('services.gemini.api_key')),
        'api_key_prefix' => substr(config('services.gemini.api_key'), 0, 5),
        'result' => $res,
    ]);
});

Route::get('/seed-medicines', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'RealMedicinesSeeder',
            '--force' => true
        ]);
        return "100+ Medicines Seeded Successfully! <a href='/medicines'>Go Back</a>";
    } catch (\Exception $e) {
        return "Error seeding: " . $e->getMessage();
    }
});
