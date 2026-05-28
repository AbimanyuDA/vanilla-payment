<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Middleware\AdminAuthenticate;
use Illuminate\Support\Facades\Route;

Route::get('/test-db', function() {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        return "Database connection is OK!";
    } catch (\Exception $e) {
        return "Database connection failed: " . $e->getMessage();
    }
});

Route::get('/test-env', function() {
    return [
        'APP_KEY_exists' => !empty(env('APP_KEY')),
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_HOST' => env('DB_HOST'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'DB_USERNAME' => env('DB_USERNAME'),
        'DB_PASSWORD_exists' => !empty(env('DB_PASSWORD')),
        'DB_PORT' => env('DB_PORT'),
    ];
});

Route::get('/', fn() => redirect()->route('admin.dashboard'));

// Admin Auth
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(AdminAuthenticate::class)->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Invoices
        Route::resource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/mark-sent', [InvoiceController::class, 'markSent'])->name('invoices.mark-sent');
        Route::post('invoices/{invoice}/confirm-payment', [InvoiceController::class, 'confirmPayment'])->name('invoices.confirm-payment');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('invoices/{invoice}/send-notification', [InvoiceController::class, 'sendNotification'])->name('invoices.send-notification');
        Route::get('invoices/{invoice}/pdf', [ReportController::class, 'invoicePdf'])->name('invoices.pdf');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
    });
});

// Customer Payment Page
Route::prefix('pay')->name('payment.')->group(function () {
    Route::get('{token}', [PaymentController::class, 'show'])->name('show');
    Route::post('{token}/method', [PaymentController::class, 'chooseMethod'])->name('choose-method');
    Route::post('{token}/reset-method', [PaymentController::class, 'resetMethod'])->name('reset-method');
    Route::post('{token}/proof', [PaymentController::class, 'uploadProof'])->name('upload-proof');
    Route::get('{token}/invoice.pdf', [PaymentController::class, 'downloadInvoice'])->name('invoice-pdf');
});
