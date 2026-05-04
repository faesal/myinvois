<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\IntegrationInvoiceController;
use App\Http\Controllers\IntegrationInvoiceController2;
use App\Http\Controllers\SelfBillController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController2;
use App\Http\Controllers\InvoiceSubmissionController;

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

Route::post('/myinvois/cancel-document/{unique_id}', [IntegrationInvoiceController2::class, 'cancelDocumentExternal']);

// --- NORMAL INVOICE ---
Route::any('/myinvois/invoice', [IntegrationInvoiceController2::class, 'invoice']);
Route::any('/myinvois/note', [IntegrationInvoiceController2::class, 'note']);

// --- EMAIL API ---
Route::post('/invoice/send-email', [InvoiceController2::class, 'sendInvoiceEmail']);



// --- TESTING INVOICES ---
Route::get('/generate-test-invoices', function () {
    // =========================================================
    // 1. SERVER PROTECTION: Prevent Timeouts and Memory Crashes
    // =========================================================
    set_time_limit(0); // Tell PHP not to time out
    ini_set('memory_limit', '1024M'); // Temporarily allow more RAM
    DB::disableQueryLog(); // CRITICAL: Stops Laravel from saving 10k queries to RAM

    $now = now();
    $totalInvoices = 5000;
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
            foreach (array_chunk($itemsToInsert, 15000) as $chunk) {
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
// --- TESTING REAL LHDN API REJECTIONS ---
Route::get('/generate-lhdn-rejects', function () {
    // =========================================================
    // 1. SERVER PROTECTION
    // =========================================================
    set_time_limit(0); 
    ini_set('memory_limit', '1024M'); 
    \Illuminate\Support\Facades\DB::disableQueryLog(); 

    $now = now();
    $totalInvoices = 10; // 🚀 Only generating 5 to test the flow
    $batchSize = 10; 

    // Clear out old failed test data
    \Illuminate\Support\Facades\DB::table('invoice_item')->where('item_description', 'like', 'LHDN Reject Token%')->delete();
    \Illuminate\Support\Facades\DB::table('invoice')->where('invoice_no', 'like', 'INV-LHDN-REJ-%')->delete();

    // =========================================================
    // 2. CHUNKED PROCESSING LOOP
    // =========================================================
    $totalBatches = ceil($totalInvoices / $batchSize);

    for ($batch = 0; $batch < $totalBatches; $batch++) {
        $itemsToInsert = []; 
        \Illuminate\Support\Facades\DB::beginTransaction(); 

        try {
            for ($i = 1; $i <= $batchSize; $i++) {
                $currentNumber = ($batch * $batchSize) + $i; 
                
                if ($currentNumber > $totalInvoices) break; 

                $uniqueId = sha1(uniqid('', true) . 'LHDNREJ' . $currentNumber); 
                $saleId = 8080000 + $currentNumber; 
                
                // 🚀 Insert Header (SET TO PENDING SO SUBMIT API PICKS IT UP)
                $invoiceId = \Illuminate\Support\Facades\DB::table('invoice')->insertGetId([
                    'unique_id' => $uniqueId,
                    'sale_id_integrate' => $saleId,
                    'connection_integrate' => 'CUST-0724294615',
                    'id_developer' => 9,
                    'id_customer' => 165,
                    'id_supplier' => 73,
                    'invoice_status' => 'Valid',
                    'submission_status' => 'Failed', // 🟢 Set to Pending normally!
                    'is_processing' => 0,
                    'invoice_no' => 'INV-LHDN-REJ-' . $currentNumber,
                    'invoice_type_code' => '99', // 🔴 FATAL LHDN ERROR: Invalid Invoice Type Code
                    'issue_date' => $now,
                    'price' => '100.00',
                    'total_price_discount' => '0.00',
                    'taxable_amount' => '100.00',
                    'tax_amount' => '50000.00', // 🔴 FATAL LHDN ERROR: Impossible Tax Math
                    'tax_category_id' => '01',
                    'tax_scheme_id' => 'OTH',
                    'include_signature' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'is_failed' => 1, // Starts normal
                    'retry_count' => 0, // Starts at 0
                    'is_failed_email_sent' => 0,
                    'is_deleted' => 0,
                ]);

                // Prepare 1 Sabotaged Item
                $itemsToInsert[] = [
                    'id_developer' => 9,
                    'item_id_integrate' => 'LHDNREJ-' . $currentNumber,
                    'unique_id' => $uniqueId,
                    'sale_id_integrate' => $saleId,
                    'connection_integrate' => 'CUST-0724294615',
                    'id_customer' => 165,
                    'id_invoice' => $invoiceId, 
                    'line_id' => '1',
                    'invoiced_quantity' => 1.00,
                    'line_extension_amount' => 100.00, 
                    'item_description' => 'LHDN Reject Token ' . $currentNumber,
                    'price_amount' => 100.00,
                    'price_discount' => 0.00,
                    'price_extension_amount' => 100.00,
                    'tax' => 50000.00, // 🔴 Bad Math
                    'item_clasification_value' => '022',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            \Illuminate\Support\Facades\DB::table('invoice_item')->insert($itemsToInsert);
            \Illuminate\Support\Facades\DB::commit(); 

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack(); 
            return response()->json(['success' => false, 'message' => 'Failed at invoice #'.$currentNumber.': ' . $e->getMessage()], 500);
        }
    }

    \Illuminate\Support\Facades\DB::enableQueryLog();

    return response()->json(['success' => true, 'message' => '5 Sabotaged LHDN Invoices successfully generated! Ready for realistic testing.']);
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

// ====================================================================
// 👉 NEW: BATCH API ROUTES (No Prefix)
// These are perfectly stateless and bypass CSRF protection automatically.
// ====================================================================
    
    // 1. The Main Submission Route (Uses ANY to accept POST from UI and GET from Cron)
    Route::any('/submit', [InvoiceSubmissionController::class, 'SubmitApi']);

    // 2. The Worker Trigger (Points to the newly upgraded triggerWorkerAPI)
    Route::any('/worker/trigger-api', [InvoiceSubmissionController::class, 'triggerWorkerAPI']);

    // 3. The Progress Tracker (Original visual tracker)
    Route::get('/submit/progress', [InvoiceSubmissionController::class, 'checkBatchProgress']);
    
    // 4. The Final Batch Sweeper (Forces statuses to Submitted/Failed at 100%)
    Route::post('/check-batch', [InvoiceSubmissionController::class, 'checkBatchApi']);

    // 5. The Auto Consolidate Route (Uses ANY for Cron compatibility)
    Route::any('/cron/consolidate', [InvoiceSubmissionController::class, 'autoConsolidate']);

    // 6. Status tracking
    Route::get('/consolidate/status', [InvoiceSubmissionController::class, 'consolidateStatus']);

    // 7. Background fallback route
    Route::post('/internal/submit-batch-background', [InvoiceSubmissionController::class, 'submitBatchBackground']);

    // 8. Auto Retry Failed (Uses ANY for Cron compatibility)
    Route::any('/cron/retry-failed', [InvoiceSubmissionController::class, 'retryFailedApi']);

    // ==============================================================================
    // 🚀 DYNAMIC CRON JOB WORKER LINK (Handles unlimited workers automatically)
    // ==============================================================================
    // Points to triggerWorkerAPI so it gets the "unstoppable sweeper" crash protection
    Route::any('/cron/worker-{worker_id}', [InvoiceSubmissionController::class, 'triggerWorkerAPI']);
    
