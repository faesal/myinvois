<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\IntegrationInvoiceController;
use App\Http\Controllers\IntegrationInvoiceController2;
use App\Http\Controllers\SelfBillController;

Route::any('/myinvois', [IntegrationInvoiceController::class, 'storeFromIntegration']);
Route::post('/myinvois/add_customer', [IntegrationInvoiceController::class, 'addCustomer']);
Route::post('/myinvois/add_supplier', [IntegrationInvoiceController::class, 'addSupplier']);

Route::post('/myinvois/validate', [IntegrationInvoiceController::class, 'validate']);




//NORMAL INVOICE
Route::any('/myinvois/invoice', [IntegrationInvoiceController2::class, 'invoice']);
Route::any('/myinvois/note', [IntegrationInvoiceController2::class, 'note']);


//Route::any('/myinvois/selfbill/invoice', [SelfBillController::class, 'invoice']);
Route::any('/myinvois/selfbill/note', [IntegrationInvoiceController2::class, 'selfBillNote']);

//GENERAL INVOICE
Route::any('/myinvois/invoice_generaltin', [IntegrationInvoiceController2::class, 'invoice_general']);
Route::any('/myinvois/invoice_generaltin/note', [IntegrationInvoiceController2::class, 'invoice_general_note']);

//Route::any('/myinvois/invoice_generaltin/selfbill', [SelfBillController::class, 'invoice']);
Route::any('/myinvois/invoice_generaltin/selfbill/note', [IntegrationInvoiceController2::class, 'generalselfBillNote']);


Route::any('/myinvois/selfbill/invoice', [SelfBillController::class, 'invoice']); 
Route::any('/myinvois/invoice_generaltin/selfbill', function (\Illuminate\Http\Request $request) {
    return app(SelfBillController::class)->invoice($request, 'general');
});


//Route::get('/test', [InvoiceController::class, 'test']);

Route::get('/test/{id}/json', [InvoiceController::class, 'test']);



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


