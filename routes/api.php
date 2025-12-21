<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\IntegrationInvoiceController;
use App\Http\Controllers\IntegrationInvoiceController2;

Route::any('/myinvois/cust_invoice', [IntegrationInvoiceController2::class, 'store']);

Route::any('/myinvois', [IntegrationInvoiceController::class, 'storeFromIntegration']);
Route::post('/myinvoice/add_customer', [IntegrationInvoiceController::class, 'addCustomer']);
Route::post('/myinvois/validate', [IntegrationInvoiceController::class, 'validate']);


Route::prefix('invoices')->group(function () {
    Route::get('/', [InvoiceController::class, 'index']);
    Route::any('/show', [InvoiceController::class, 'show']);
    Route::post('/', [InvoiceController::class, 'store']);
    Route::get('/{id}', [InvoiceController::class, 'show']);
    Route::put('/{id}', [InvoiceController::class, 'update']);
    Route::delete('/{id}', [InvoiceController::class, 'destroy']);
    Route::post('/{id}/submit', [InvoiceController::class, 'submit']);
    Route::get('/{id}/qr', [InvoiceController::class, 'qr']);
    Route::post('/cancel/{id}', [InvoiceController::class, 'cancelDocument']);
    Route::post('/reject/{id}', [InvoiceController::class, 'rejectDocument']);
    Route::get('/search', [InvoiceController::class, 'searchDocuments']);
    Route::get('/recent', [InvoiceController::class, 'getRecentDocuments']);
    Route::get('/submission/{id}', [InvoiceController::class, 'getSubmission']);
    Route::get('/detail/{id}', [InvoiceController::class, 'getDocumentDetail']);
});

// Add these routes to your existing routes file
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'store']);
    Route::get('/{id}', [CustomerController::class, 'show']);
    Route::put('/{id}', [CustomerController::class, 'update']);
    Route::delete('/{id}', [CustomerController::class, 'destroy']);
});


