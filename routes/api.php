<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\IntegrationInvoiceController;
use App\Http\Controllers\IntegrationInvoiceController2;
use App\Http\Controllers\SelfBillController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController2;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- INTEGRATION & WEBHOOKS ---
Route::any('/myinvois', [IntegrationInvoiceController::class, 'storeFromIntegration']);
Route::post('/myinvois/add_customer', [IntegrationInvoiceController::class, 'addCustomer']);
Route::post('/myinvois/add_supplier', [IntegrationInvoiceController::class, 'addSupplier']);
Route::post('/myinvois/validate', [IntegrationInvoiceController::class, 'validate']);

// --- NORMAL INVOICE ---
Route::any('/myinvois/invoice', [IntegrationInvoiceController2::class, 'invoice']);
Route::any('/myinvois/note', [IntegrationInvoiceController2::class, 'note']);

// --- EMAIL API ---
Route::post('/invoice/send-email', [InvoiceController2::class, 'sendInvoiceEmail']);

Route::post('/v1/external/cancel-document/{unique_id}', [IntegrationInvoiceController2::class, 'cancelDocumentExternal']);

// --- TESTING INVOICES ---
Route::get('/generate-test-invoices', function () {
    // =========================================================
    // 1. SERVER PROTECTION: Prevent Timeouts and Memory Crashes
    // =========================================================
    set_time_limit(0); // Tell PHP not to time out
    ini_set('memory_limit', '1024M'); // Temporarily allow more RAM
    DB::disableQueryLog(); // CRITICAL: Stops Laravel from saving 10k queries to RAM

    $now = now();
    $totalInvoices = 1000;
    $batchSize = 500; // Process 500 at a time to keep memory totally clean

    // Clear out old test data
    DB::table('invoice_item')->where('item_description', 'like', 'Test Token%')->delete();
    DB::table('invoice')->where('invoice_no', 'like', 'INV-TEST-V%')->delete();

    // =========================================================
    // 2. CHUNKED PROCESSING LOOP
    // =========================================================
    $totalBatches = ceil($totalInvoices / $batchSize);

    for ($batch = 0; $batch < $totalBatches; $batch++) {
        $itemsToInsert = []; // Reset this array every 500 invoices to free up RAM
        
        // Wrap the batch in a transaction. This stops the DB from writing to the 
        // physical hard drive until all 500 are ready, making it incredibly fast.
        DB::beginTransaction(); 

        try {
            for ($i = 1; $i <= $batchSize; $i++) {
                $currentNumber = ($batch * $batchSize) + $i; // Keeps track from 1 to 10,000
                
                if ($currentNumber > $totalInvoices) break; // Safety catch

                $uniqueId = sha1(uniqid('', true) . $currentNumber); 
                $saleId = 20267000 + $currentNumber;
                
                // Insert Header
                $invoiceId = DB::table('invoice')->insertGetId([
                    'unique_id' => $uniqueId,
                    'sale_id_integrate' => $saleId,
                    'connection_integrate' => 'CUST-0724294615',
                    'id_developer' => 9,
                    'id_customer' => 165,
                    'id_supplier' => 73,
                    'invoice_status' => 'Valid',
                    'submission_status' => 'Pending',
                    'is_processing' => 0,
                    'invoice_no' => 'INV-TEST-V' . $currentNumber,
                    'invoice_type_code' => '01',
                    'issue_date' => $now,
                    'price' => '1818.18',
                    'total_price_discount' => '10.00',
                    'taxable_amount' => '1808.18',
                    'tax_amount' => 181.82,
                    'tax_category_id' => '01',
                    'tax_scheme_id' => 'OTH',
                    'include_signature' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'is_failed' => 0,
                    'is_deleted' => 0,
                ]);

                // Prepare 3 Items for this Invoice
                $itemMath = [
                    ['price' => 600.00, 'discount' => 3.33, 'taxable' => 596.67, 'tax' => 59.67],
                    ['price' => 600.00, 'discount' => 3.33, 'taxable' => 596.67, 'tax' => 59.67],
                    ['price' => 618.18, 'discount' => 3.34, 'taxable' => 614.84, 'tax' => 62.48],
                ];

                foreach ($itemMath as $index => $math) {
                    $itemsToInsert[] = [
                        'id_developer' => 9,
                        'item_id_integrate' => '999-' . ($index + 1),
                        'unique_id' => $uniqueId,
                        'sale_id_integrate' => $saleId,
                        'connection_integrate' => 'CUST-0724294615',
                        'id_customer' => 165,
                        'id_invoice' => $invoiceId, 
                        'line_id' => (string)($index + 1),
                        'invoiced_quantity' => 1.00,
                        'line_extension_amount' => $math['price'], 
                        'item_description' => 'Test Token ' . ($index + 1) . ' (INV: '.$currentNumber.')',
                        'price_amount' => $math['price'],
                        'price_discount' => $math['discount'],
                        'price_extension_amount' => $math['taxable'],
                        'tax' => $math['tax'],
                        'item_clasification_value' => '022',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Bulk insert the 1,500 items for this batch
            foreach (array_chunk($itemsToInsert, 500) as $chunk) {
                DB::table('invoice_item')->insert($chunk);
            }

            DB::commit(); // Lock in the 500 invoices + 1500 items to the database

        } catch (\Exception $e) {
            DB::rollBack(); // If anything fails, undo this batch
            return response()->json(['success' => false, 'message' => 'Failed at invoice #'.$currentNumber.': ' . $e->getMessage()], 500);
        }
    }

    // Re-enable query logging just in case other parts of your app need it later in the lifecycle
    DB::enableQueryLog();

    return response()->json(['success' => true, 'message' => '10,000 Test Invoices (and 30,000 items) successfully generated!']);
});

/**
 * NEW: Cancel Document Route (Fixed)
 * We use 'web' middleware here. This enables the Session so the LHDN SDK 
 * can find the Auth Token and cancel the document successfully.
 */
Route::middleware('web')->group(function () {
    Route::post('/myinvois/cancelDocument/{unique_id}', [IntegrationInvoiceController2::class, 'cancelDocument']);
    Route::get('/invoices/auto-resubmit-failed', [InvoiceController::class, 'autoResubmit']);
    Route::post('/invoices/bulk-resubmit', [InvoiceController::class, 'bulkResubmit']);
    Route::post('/invoice/resubmit/{unique_id}', [InvoiceController::class, 'apiResubmit']);
});

Route::any('/myinvois/selfbill/note', [IntegrationInvoiceController2::class, 'selfBillNote']);

// --- GENERAL INVOICE ---
Route::any('/myinvois/invoice_generaltin', [IntegrationInvoiceController2::class, 'invoice_general']);
Route::any('/myinvois/invoice_generaltin/note', [IntegrationInvoiceController2::class, 'invoice_general_note']);
Route::any('/myinvois/invoice_generaltin/selfbill/note', [IntegrationInvoiceController2::class, 'generalselfBillNote']);

// --- SELF BILL ---
Route::any('/myinvois/selfbill/invoice', [SelfBillController::class, 'invoice']); 
Route::any('/myinvois/invoice_generaltin/selfbill', function (Request $request) {
    return app(SelfBillController::class)->invoice($request, 'general');
});

// --- TESTING ---
Route::get('/test/{id}/json', [InvoiceController::class, 'test']);
Route::get('/test4', [InvoiceController::class, 'test4']);

// --- INVOICE CRUD ---
Route::prefix('invoices')->group(function () {
    Route::get('/', [InvoiceController::class, 'index']);
    Route::any('/show', [InvoiceController::class, 'show']);
    Route::post('/', [InvoiceController::class, 'store']);
    Route::get('/{id}', [InvoiceController::class, 'show']);
    Route::put('/{id}', [InvoiceController::class, 'update']);
    Route::delete('/{id}', [InvoiceController::class, 'destroy']);
    Route::post('/{id}/submit', [InvoiceController::class, 'submit']);
    Route::get('/{id}/qr', [InvoiceController::class, 'qr']);
    Route::post('/cancel/{id}', [InvoiceController::class, 'cancelDocument']); // Note: internal ID cancel
    Route::post('/reject/{id}', [InvoiceController::class, 'rejectDocument']);
    Route::get('/search', [InvoiceController::class, 'searchDocuments']);
    Route::get('/recent', [InvoiceController::class, 'getRecentDocuments']);
    Route::get('/submission/{id}', [InvoiceController::class, 'getSubmission']);
    Route::get('/detail/{id}', [InvoiceController::class, 'getDocumentDetail']);
    Route::post('/bulk-resubmit', [InvoiceController::class, 'bulkResubmit']);
});

// --- CUSTOMERS ---
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'store']);
    Route::get('/{id}', [CustomerController::class, 'show']);
    Route::put('/{id}', [CustomerController::class, 'update']);
    Route::delete('/{id}', [CustomerController::class, 'destroy']);
});
