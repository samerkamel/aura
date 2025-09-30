<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoicing\Http\Controllers\InvoiceController;
use Modules\Invoicing\Http\Controllers\InvoicePaymentController;
use Modules\Invoicing\Http\Controllers\InternalTransactionController;
use Modules\Invoicing\Http\Controllers\InvoiceSequenceController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('invoicing')->name('invoicing.')->middleware(['web', 'auth'])->group(function () {

    // Invoice Management
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/create', [InvoiceController::class, 'create'])->name('create');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
        Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');

        // Invoice status management
        Route::post('/{invoice}/send', [InvoiceController::class, 'markAsSent'])->name('send');
        Route::post('/{invoice}/pay', [InvoiceController::class, 'markAsPaid'])->name('pay');
        Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');

        // Invoice generation from contract payments
        Route::get('/generate/from-payment/{contractPayment}', [InvoiceController::class, 'generateFromPayment'])->name('generate.payment');
        Route::post('/generate/from-payments', [InvoiceController::class, 'generateFromPayments'])->name('generate.payments');
        Route::get('/generate/from-contract/{contract}', [InvoiceController::class, 'generateFromContract'])->name('generate.contract');

        // Invoice payment management
        Route::post('/{invoice}/payments', [InvoicePaymentController::class, 'store'])->name('payments.store');
        Route::put('/payments/{invoicePayment}', [InvoicePaymentController::class, 'update'])->name('payments.update');
        Route::delete('/payments/{invoicePayment}', [InvoicePaymentController::class, 'destroy'])->name('payments.destroy');
    });

    // Payment Management (Aggregated view)
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [InvoicePaymentController::class, 'index'])->name('index');
    });

    // Internal Transaction Management
    Route::prefix('internal-transactions')->name('internal-transactions.')->group(function () {
        Route::get('/', [InternalTransactionController::class, 'index'])->name('index');
        Route::get('/create', [InternalTransactionController::class, 'create'])->name('create');
        Route::post('/', [InternalTransactionController::class, 'store'])->name('store');
        Route::get('/{internalTransaction}', [InternalTransactionController::class, 'show'])->name('show');
        Route::get('/{internalTransaction}/edit', [InternalTransactionController::class, 'edit'])->name('edit');
        Route::put('/{internalTransaction}', [InternalTransactionController::class, 'update'])->name('update');
        Route::delete('/{internalTransaction}', [InternalTransactionController::class, 'destroy'])->name('destroy');

        // Transaction workflow
        Route::post('/{internalTransaction}/submit', [InternalTransactionController::class, 'submitForApproval'])->name('submit');
        Route::post('/{internalTransaction}/approve', [InternalTransactionController::class, 'approve'])->name('approve');
        Route::post('/{internalTransaction}/reject', [InternalTransactionController::class, 'reject'])->name('reject');
        Route::post('/{internalTransaction}/cancel', [InternalTransactionController::class, 'cancel'])->name('cancel');
    });

    // Invoice Sequence Management (Admin)
    Route::prefix('admin/sequences')->name('sequences.')->group(function () {
        Route::get('/', [InvoiceSequenceController::class, 'index'])->name('index');
        Route::get('/create', [InvoiceSequenceController::class, 'create'])->name('create');
        Route::post('/', [InvoiceSequenceController::class, 'store'])->name('store');
        Route::get('/{invoiceSequence}', [InvoiceSequenceController::class, 'show'])->name('show');
        Route::get('/{invoiceSequence}/edit', [InvoiceSequenceController::class, 'edit'])->name('edit');
        Route::put('/{invoiceSequence}', [InvoiceSequenceController::class, 'update'])->name('update');
        Route::delete('/{invoiceSequence}', [InvoiceSequenceController::class, 'destroy'])->name('destroy');
        Route::post('/{invoiceSequence}/reset', [InvoiceSequenceController::class, 'reset'])->name('reset');
        Route::post('/{invoiceSequence}/toggle', [InvoiceSequenceController::class, 'toggle'])->name('toggle');
    });

    // Internal Sequence Management (Admin)
    Route::prefix('admin/internal-sequences')->name('internal-sequences.')->group(function () {
        Route::get('/', [InvoiceSequenceController::class, 'internalIndex'])->name('index');
        Route::get('/create', [InvoiceSequenceController::class, 'internalCreate'])->name('create');
        Route::post('/', [InvoiceSequenceController::class, 'internalStore'])->name('store');
        Route::get('/{internalSequence}', [InvoiceSequenceController::class, 'internalShow'])->name('show');
        Route::get('/{internalSequence}/edit', [InvoiceSequenceController::class, 'internalEdit'])->name('edit');
        Route::put('/{internalSequence}', [InvoiceSequenceController::class, 'internalUpdate'])->name('update');
        Route::delete('/{internalSequence}', [InvoiceSequenceController::class, 'internalDestroy'])->name('destroy');
        Route::post('/{internalSequence}/reset', [InvoiceSequenceController::class, 'internalReset'])->name('reset');
        Route::post('/{internalSequence}/toggle', [InvoiceSequenceController::class, 'internalToggle'])->name('toggle');
    });
});
