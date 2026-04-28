<?php



use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\InvoiceController;

use App\Http\Controllers\CustomerController;

use App\Http\Controllers\LoginController;

use App\Http\Controllers\LinkController;

use App\Http\Controllers\DashboardController;

use App\Http\Controllers\ConsolidateController;

use App\Http\Controllers\CreditNoteController;

use App\Http\Controllers\DebitNoteController;

use App\Http\Controllers\NoteController;

use App\Http\Controllers\AuthController;

use App\Http\Controllers\IntegrationInvoiceController;

use App\Http\Controllers\DeveloperDocumentationController;

use App\Http\Controllers\MyInvoisRedirectController;

use App\Http\Controllers\DeveloperController;

use App\Http\Controllers\DeveloperDashboardController;

use App\Http\Controllers\InvoiceSubmissionController;

use App\Http\Controllers\DeveloperCustomerController;

use App\Http\Controllers\ClientController;

use Illuminate\Support\Facades\Artisan;

use App\Http\Controllers\MySyncTaxUserController;

use App\Http\Controllers\DeveloperProfileController;

use App\Http\Controllers\ConsolidateImportController;

use App\Http\Controllers\ConsolidateListingController;

use App\Http\Controllers\SubscriberController;

use App\Http\Controllers\ManageDeveloperController;

use App\Http\Controllers\ClientSettingController;

use App\Http\Controllers\ManageCustomerController;

use App\Http\Controllers\SelfBillNoteController;

use App\Http\Controllers\SelfInvoiceController;

use App\Http\Controllers\IntegrationInvoiceController2;



Route::get('/admin/mysynctax/send-credential/{id}', [

    MySyncTaxUserController::class,

    'sendCredentialEmail'

]);

Route::get('/sendApproachEmail', [

    MySyncTaxUserController::class,

    'sendApproachEmail'

]);



Route::get('/clear-controller-cache', function () {

    Artisan::call('route:clear');

    return 'Controller (route) cache cleared';

});


Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

Route::post('/login', [AuthController::class, 'login']);



Route::any('/user/login', [AuthController::class, 'login']);



Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/subscriberLogin/{uuid}', [AuthController::class, 'subscriberLogin'])

    ->name('subscriber.login');





// Developer Documentation Route asd

Route::get('/developer/documentation', [DeveloperDocumentationController::class, 'index'])->name('developer.documentation');
Route::get('/developer/release_note', [DeveloperDocumentationController::class, 'release_note'])->name('developer.release_note');



// New route for MyInvois redirection processing

Route::get('/myinvois/{mysynctax_uuid}', [MyInvoisRedirectController::class,'process'])->name('mysynctax.redirect');







//NOTE

Route::middleware('auth')->group(function () {


    Route::get('/', function () {

        return view('welcome');

    });

    

// 1. STANDARD NOTES (Credit/Debit/Refund)
    // URL: /credit_note/listing
    Route::prefix('{note_type}')
        ->whereIn('note_type', ['credit_note', 'debit_note', 'refund_note'])
        ->group(function () {
            Route::get('/listing', [NoteController::class, 'listing'])->name('note.listing');
            Route::get('/create', [NoteController::class, 'create'])->name('note.create');
            
            Route::get('/fetchInvoiceItems/{id_invoice}', [NoteController::class, 'fetchInvoiceItems'])
                ->where('id_invoice', '[0-9]+')
                ->name('note.fetchItems');

            Route::post('/store', [NoteController::class, 'store'])->name('note.store');
        });

    // 2. SELF BILL NOTES (Credit/Debit/Refund)
    // URL: /self_bill/credit_note/listing
    // ✅ FIXED: Converted to Fluent Syntax to allow ->whereIn()
    Route::middleware('auth')
        ->prefix('self_bill/{note_type}')
        ->whereIn('note_type', ['credit_note', 'debit_note', 'refund_note'])
        ->group(function () {
            
            Route::get('/listing', [App\Http\Controllers\SelfBillNoteController::class, 'listing'])
                ->name('self_bill_note.listing');

            Route::get('/create', [App\Http\Controllers\SelfBillNoteController::class, 'create'])
                ->name('self_bill_note.create');

            Route::post('/store', [App\Http\Controllers\SelfBillNoteController::class, 'store'])
                ->name('self_bill_note.store');

            // Fetch items helper
            Route::get('/fetchInvoiceItems/{id_invoice}', [App\Http\Controllers\SelfBillNoteController::class, 'fetchInvoiceItems'])
                ->name('self_bill_note.fetchItems');
            
                Route::delete('/destroy/{id}', [App\Http\Controllers\SelfBillNoteController::class, 'destroy'])
            ->name('self_bill_note.destroy');
        });


        //CUSTOMER

        Route::any('/main', [DashboardController::class, 'main']);

        Route::any('/customer/destroy/{id}', [CustomerController::class, 'destroy']);

        Route::any('/customer/add_customer', [CustomerController::class, 'add_customer']);

        Route::get('/customer/form_customer', [CustomerController::class, 'form_customer']);

        Route::get('/customer/form_customer/{id}', [CustomerController::class, 'form_customer']);

        Route::any('/customer/listing_customer', [CustomerController::class, 'listing_customer']);



        //INVOICE

       Route::any('/listing_submission', [InvoiceController::class, 'listing_submission'])->name('invoice.listing_submission');

        Route::get('/invoice/create', [InvoiceController::class, 'create'])->name('invoice.create');
        
        Route::post('/invoice/store_create', [InvoiceController::class, 'store_create'])->name('invoice.store_create');

       // Route::any('/invoice/resubmit/{id_invoice}', [InvoiceController::class, 'cancelDocument']);

        Route::any('submit_items', [InvoiceController::class, 'submitSelected'])->name('consolidate.submit');

        Route::get('/show_invoice/{unique_id}', [InvoiceController::class, 'show_invoice'])->name('invoice.show');

        Route::any('select_items', [InvoiceController::class, 'selectItems'])->name('consolidate.select');

        Route::delete('/consolidate/item/delete/{id}', [InvoiceController::class, 'deleteConsolidateItem'])->name('consolidate.item.delete');

        Route::post('/invoice/submit-selected-lhdn', [InvoiceController::class, 'submitSelectedLHDN'])->name('invoice.submit_selected_lhdn');
        
        Route::get('/invoice/ajax-data', [InvoiceController::class, 'getSubmissionData'])->name('invoice.ajax_data');

        Route::get('/delete_invoice/{id}', [InvoiceController::class, 'deleteInvoice'])->name('invoice.delete');
        



        
        

  // ========================================================================
// CONSOLIDATE BATCH IMPORT ROUTES
// ========================================================================

Route::prefix('consolidate')->name('consolidate.')->group(function () {

    // 1. MAIN PAGES
    Route::get('/import', [ConsolidateImportController::class, 'index'])->name('import');
    Route::get('/template', [ConsolidateImportController::class, 'downloadTemplate'])->name('template');
    Route::post('/import', [ConsolidateImportController::class, 'importBatch'])->name('import.process');
    Route::get('/view/{id}', [ConsolidateImportController::class, 'view'])->name('view');
    Route::post('/update/{id}', [ConsolidateImportController::class, 'update'])->name('update');
    Route::get('/delete/{id}', [ConsolidateImportController::class, 'destroy'])->name('delete');

    // 2. EXPORT & LHDN
    Route::get('/export-csv', [ConsolidateImportController::class, 'exportCSV'])->name('export.csv');
    Route::get('/export-pdf', [ConsolidateImportController::class, 'exportPDF'])->name('export.pdf');
    Route::post('/submit-lhdn', [ConsolidateImportController::class, 'consolidateSubmitSelected'])->name('submit_lhdn');

    // 3. CHILD ITEMS
    Route::post('/item/add/{invoice_id}', [ConsolidateImportController::class, 'addItem'])->name('item.add');
    Route::post('/item/update/{id}', [ConsolidateImportController::class, 'updateItem'])->name('item.update');
    Route::post('/item/delete-record/{id}', [ConsolidateImportController::class, 'deleteItem'])->name('item.delete');

    // 4. LISTING & SUBMISSION
    Route::match(['get', 'post'], '/listing', [ConsolidateListingController::class, 'index'])->name('listing');
    Route::post('/submit-selected', [ConsolidateListingController::class, 'submitSelected'])->name('submitSelected');

    // 5. SHOW INVOICE (FIXED)
    // The prefix 'consolidate' is already applied by the group.
    // The name 'consolidate.' is already applied by the group.
    Route::get('/show/{id_supplier}/{id_invoice}', [ConsolidateListingController::class, 'showInvoice'])->name('show');
});

        // ========================================================================
        // END CONSOLIDATE ROUTES
        // ========================================================================



        //PROFILE

        Route::any('/user/profile', [AuthController::class, 'profile']);

});



//CONSOLIDATE

Route::any('/monthlyConsolidateToInvoice', [ConsolidateController::class, 'monthlyConsolidateToInvoice']);

Route::any('/compare', [ConsolidateController::class, 'compare']);

Route::any('/pull', [ConsolidateController::class, 'pullFromConnections']);



//INVOICE

Route::get('/invoice/validateTaxPayerTin/{tin}/{idType}/{idValue}', [InvoiceController::class, 'validateTaxPayerTin']);

Route::post('/invoice/store', [InvoiceController::class, 'store_create'])->name('invoice.store');

Route::any('/qr_link/{uuid}', [InvoiceController::class, 'qr_link']);







//LINK

Route::any('/shorten', [LinkController::class, 'shorten'])->name('shorten');

Route::any('/redirect/{shortCode}', [LinkController::class, 'redirect']);





//CUSTOMER

Route::any('/storecustomer', [CustomerController::class, 'store']);

Route::any('/public_store', [CustomerController::class, 'public_store']);

Route::any('/checkTinNo', [CustomerController::class, 'checkTinNo']);

Route::any('/createcustomer/{invoice_unique_id}', [CustomerController::class, 'create']);

Route::any('/public_customer', [CustomerController::class, 'public_create']);



// Developer Authentication (register)

// Public routes

Route::get('/developer/register', [DeveloperController::class, 'showRegistrationForm'])->name('developer.register');

Route::post('/developer/register', [DeveloperController::class, 'register'])->name('developer.register.submit');

Route::get('/developer/cron/consolidate', [InvoiceSubmissionController::class, 'autoConsolidate']);

Route::any('/developer/submit', [InvoiceSubmissionController::class, 'SubmitApi']);

Route::get('/developer/submit/progress', [InvoiceSubmissionController::class, 'checkApiBatchProgress']);

Route::post('/internal/submit-batch-background', [InvoiceSubmissionController::class, 'submitBatchBackground']);

Route::get('/developer/consolidate/status', [InvoiceSubmissionController::class, 'consolidateStatus']);



// Protected Developer Section (login required)

Route::middleware(['auth'])->group(function () {



    Route::any('/developer/ConsolidateSelected', [InvoiceSubmissionController::class, 'ConsolidateSelected']);

    Route::get('/developer/consolidate/data', [InvoiceSubmissionController::class, 'getConsolidateData'])->name('developer.consolidate.data');

    Route::any('/developer/consolidate', [InvoiceSubmissionController::class, 'consolidate']);

    Route::delete('/developer/consolidate/delete/{id}', [InvoiceSubmissionController::class, 'destroyConsolidateItem']);

    Route::get('/developer/consolidate/export-search', [InvoiceSubmissionController::class, 'exportConsolidate'])->name('developer.consolidate.export_search');

    // Inside the auth middleware group
    Route::post('/developer/consolidate/bulk-delete', [InvoiceSubmissionController::class, 'bulkDeleteConsolidateItems']);


    // Route for CronJob Auto Consolidation
    //Route::get('/developer/cron/consolidate', [InvoiceSubmissionController::class, 'autoConsolidate']);

    Route::get('/developer/settings', [DeveloperDashboardController::class, 'settings'])
        ->name('developer.settings');


    Route::get('/developer/dashboard', [DeveloperDashboardController::class, 'index'])

        ->name('developer.dashboard');



    Route::get('/developer/client/create', [ClientController::class, 'create'])

        ->name('developer.client.create');



    Route::post('/developer/client/store', [ClientController::class, 'store'])

        ->name('developer.client.store');



    Route::get('/developer/clients/export', [ClientController::class, 'export'])

        ->name('developer.clients.export');



    Route::get('/developer/client/edit/{id_customer}', [ClientController::class, 'edit'])

        ->name('developer.client.edit');

    Route::post('/developer/client/regenerate-keys/{id_customer}', [ClientController::class, 'regenerateKeys'])
        ->name('developer.client.regenerate_keys');



    Route::post('/developer/client/update/{id_customer}', [ClientController::class, 'update'])

        ->name('developer.client.update');
    
    Route::post('/client/settings/ip-whitelist-toggle/{id}', [ClientController::class, 'updateIpWhitelistToggle'])
    ->name('client.settings.ip_toggle');

    // 1. Consolidation Settings (Frequency & Toggle)
    Route::post('/client/settings/consolidate/{id}', [ClientController::class, 'saveConsolidation'])
        ->name('client.settings.consolidate');

        Route::post('/client/settings/update-version/{id}', [ClientController::class, 'updateApiVersion'])
    ->name('client.settings.update_version');

// ==========================================
    // NEW CLIENT SETTING ROUTES (Paste Here)
    // ==========================================
Route::post('/client/settings/consolidate/{id}', [ClientController::class, 'saveConsolidation'])
        ->name('client.settings.consolidate');

    Route::post('/client/settings/ip/{id}', [ClientController::class, 'storeIp'])
        ->name('client.settings.ip.store');

    Route::delete('/client/settings/ip/{id}', [ClientController::class, 'destroyIp'])
        ->name('client.settings.ip.delete');
    // ==========================================


    Route::get('/developer/profile', [DeveloperProfileController::class, 'edit'])

        ->name('developer.profile.edit');



    Route::put('/developer/profile', [DeveloperProfileController::class, 'update'])

        ->name('developer.profile.update');


    // ==========================================
    // CONSOLIDATION IMPORT ROUTES
    // ==========================================
    
    // 1. Main Import Page
    Route::get('/developer/consolidate/import', [ConsolidateImportController::class, 'index'])
        ->name('consolidate.import.index');

    // 2. Process CSV Upload
    Route::post('/developer/consolidate/import/process', [ConsolidateImportController::class, 'importBatch'])
        ->name('consolidate.import.process');

    // 3. Download CSV Template
    Route::get('/developer/consolidate/template', [ConsolidateImportController::class, 'downloadTemplate'])
        ->name('consolidate.template');

    // 4. Submit Selected Batches to Invoice Listing
    Route::post('/developer/consolidate/submit-lhdn', [ConsolidateImportController::class, 'consolidateSubmitSelected'])
        ->name('consolidate.submit_lhdn');

    // 5. Manage Batches (View, Delete, Update Header)
    Route::get('/developer/consolidate/view/{id}', [ConsolidateImportController::class, 'view'])
        ->name('consolidate.view');

    Route::get('/developer/consolidate/delete/{id}', [ConsolidateImportController::class, 'destroy'])
        ->name('consolidate.delete');

    Route::post('/developer/consolidate/update/{id}', [ConsolidateImportController::class, 'update'])
        ->name('consolidate.update');

    // 6. Manage Items inside a Batch (Add, Edit, Delete Rows)
    Route::post('/developer/consolidate/item/update/{id}', [ConsolidateImportController::class, 'updateItem'])
        ->name('consolidate.item.update');

    Route::post('/developer/consolidate/item/add/{invoice_id}', [ConsolidateImportController::class, 'addItem'])
        ->name('consolidate.item.add');

    Route::get('/developer/consolidate/item/delete/{id}', [ConsolidateImportController::class, 'deleteItem'])
        ->name('consolidate.item.delete');

    // 7. Export Routes
    Route::get('/developer/consolidate/export/csv', [ConsolidateImportController::class, 'exportCSV'])
        ->name('consolidate.export.csv');

    Route::get('/developer/consolidate/export/pdf', [ConsolidateImportController::class, 'exportPDF'])
        ->name('consolidate.export.pdf');
   


    // Invoices

    Route::any('/invoices', [InvoiceSubmissionController::class, 'index'])

        ->name('developer.invoices.index');

    Route::get('/developer/invoices/export', [InvoiceSubmissionController::class, 'export'])

        ->name('developer.invoices.export');



    Route::get('/invoices/{id_invoice}/view', [InvoiceSubmissionController::class, 'view'])

        ->name('developer.invoices.view');



// ==========================================
    // INVOICES & SUBMISSIONS
    // ==========================================
    Route::any('/invoices', [InvoiceSubmissionController::class, 'index'])
        ->name('developer.invoices.index');

    Route::get('/developer/invoices/export', [InvoiceSubmissionController::class, 'export'])
        ->name('developer.invoices.export');

    Route::get('/invoices/{id_invoice}/view', [InvoiceSubmissionController::class, 'view'])
        ->name('developer.invoices.view');

    Route::post('/developer/invoices/submit-selected', [InvoiceSubmissionController::class, 'submitSelectedInvoices'])
        ->name('developer.invoices.submitSelected');

    // --- INDIVIDUAL ACTIONS (Updated to POST for JS SweetAlerts) ---
    Route::post('/developer/invoice/delete', [InvoiceSubmissionController::class, 'deleteInvoice'])
        ->name('developer.invoices.delete');

    Route::post('/developer/invoice/cancel', [InvoiceSubmissionController::class, 'cancelDocument'])
        ->name('developer.invoices.cancel');

    // --- BULK ACTIONS ---
    Route::post('/developer/invoices/bulk-delete', [InvoiceSubmissionController::class, 'bulkDeleteInvoices'])
        ->name('developer.invoices.bulkDelete');
        
    Route::post('/developer/invoices/bulk-cancel', [InvoiceSubmissionController::class, 'bulkCancelInvoices'])
        ->name('developer.invoices.bulkCancel');



    // Companies

    Route::get('/companies', [DeveloperCustomerController::class, 'index'])

        ->name('developer.companies.index');



    Route::get('/companies/{id_customer}', [DeveloperCustomerController::class, 'show'])

        ->name('developer.companies.show');



    Route::get('/developer/companies/add', [DeveloperCustomerController::class, 'create'])

        ->name('developer.companies.add');



    Route::post('/developer/companies/store', [DeveloperCustomerController::class, 'store'])

        ->name('developer.companies.store');



    Route::get('/companies/{id_customer}/edit', [DeveloperCustomerController::class, 'edit'])

        ->name('developer.companies.edit');



    Route::post('/companies/{id_customer}', [DeveloperCustomerController::class, 'update'])

        ->name('developer.companies.update');





    // Logout (inside auth)

    Route::post('/logout', function () {

        Auth::logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully.');

    })->name('logout');



});
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // 1. Manage Subscribers
    Route::get('/subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');
    Route::post('/subscribers/update/{id}', [SubscriberController::class, 'update'])->name('subscribers.update');
    Route::get('/subscribers/{id}/impersonate', [SubscriberController::class, 'impersonate'])->name('subscribers.impersonate');
    Route::post('/subscribers/check-expired', [SubscriberController::class, 'manualCheckExpired'])->name('subscribers.check_expired');

    // 2. Activation Logic
    Route::get('/subscribers/{id}/activate', [SubscriberController::class, 'activationForm'])->name('subscribers.activation_form');
    Route::post('/subscribers/{id}/activate', [SubscriberController::class, 'activateSubscriber'])->name('subscribers.activate_submit');

    // 3. NEW: Soft Delete Route
    Route::delete('/subscribers/{id}', [SubscriberController::class, 'destroy'])->name('subscribers.destroy');


    // 2. Manage Developers
    // URL becomes: /admin/developers
    Route::get('/developers', [ManageDeveloperController::class, 'index'])
        ->name('developers.index');
    Route::delete('/developers/{id}/delete', [ManageDeveloperController::class, 'destroy'])
        ->name('developers.delete');

    Route::post('/developers/{id}/resend', [ManageDeveloperController::class, 'resendVerification'])
        ->name('developers.resend');

    Route::post('/developers/{id}/reset', [ManageDeveloperController::class, 'sendPasswordReset'])
        ->name('developers.reset');

    Route::get('/developers/{id}/impersonate', [ManageDeveloperController::class, 'impersonate'])
        ->name('developers.impersonate');

    Route::post('/developers/{id}/update', [ManageDeveloperController::class, 'update'])
        ->name('developers.update');

    Route::post('/developers/store', [ManageDeveloperController::class, 'store'])
        ->name('developers.store');

});



//INVOICE

Route::any('/generateFromTemplate/{invoiceId}', [InvoiceController::class, 'generateFromTemplate']);

Route::any('/resubmit/{unique_id}', [InvoiceController::class, 'resubmit']);

Route::post('/bulk-resubmit', [InvoiceController::class, 'bulkResubmit'])->name('invoices.bulkResubmit');

Route::any('/syncFromNlbh', [InvoiceController::class, 'syncFromNlbh']);

Route::any('/syncFromPOS', [InvoiceController::class, 'syncFromPOS']);

Route::any('/presubmit/{id}', [InvoiceController::class, 'presubmit']);

Route::any('/show/{id}', [InvoiceController::class, 'show']);

Route::post('/submit-invoice', [InvoiceController::class, 'submitInvoiceAsIntermediary']);

Route::any('/submit/{id_customer}', [InvoiceController::class, 'submit']);

Route::any('/submitInvoiceAsIntermediary', [InvoiceController::class, 'submitInvoiceAsIntermediary']);

Route::any('/qr', [InvoiceController::class, 'qr']);

Route::any('/getsubmission', [InvoiceController::class, 'getsubmission']);

// ✅ ADD THIS LINE (Outside of Auth/Developer groups)
Route::get('/invoice/view/{unique_id}', [App\Http\Controllers\InvoiceSubmissionController::class, 'showInvoice'])
    ->name('invoice.view.public');


// Grouping them under a prefix is usually cleaner
Route::prefix('manage-customer')->name('manage_customer.')->group(function () {
    
    // --- 1. Custom Actions (Must be defined first to avoid conflict with {id}) ---
    
    // Bulk Delete
    Route::delete('/bulk-delete', [ManageCustomerController::class, 'bulkDelete'])->name('bulk_delete');
    
    Route::post('/import', [ManageCustomerController::class, 'import'])->name('import');
    Route::post('/export', [ManageCustomerController::class, 'export'])->name('export');
    Route::get('/download-template', [ManageCustomerController::class, 'downloadTemplate'])->name('download_template');

    // --- 2. Standard CRUD Actions ---
    
    // List Page (manage_customer.index)
    Route::get('/', [ManageCustomerController::class, 'index'])->name('index');

    // Create Page (manage_customer.create)
    Route::get('/create', [ManageCustomerController::class, 'create'])->name('create');

    // Store Action (manage_customer.store)
    Route::post('/', [ManageCustomerController::class, 'store'])->name('store');

    // Edit Page (manage_customer.edit)
    Route::get('/{id}/edit', [ManageCustomerController::class, 'edit'])->name('edit');

    // Update Action (manage_customer.update)
    Route::put('/{id}', [ManageCustomerController::class, 'update'])->name('update');

    // Delete Single Action (manage_customer.destroy)
    Route::delete('/{id}', [ManageCustomerController::class, 'destroy'])->name('destroy');
});
Route::group(['prefix' => 'self_bill', 'as' => 'self_invoice.', 'middleware' => ['auth']], function () {
    
    // 1. Redirect index to the unified listing (Optional, but cleaner)
    Route::get('/listing', function() {
        return redirect()->route('invoice.listing_submission', ['type' => 'self_bill']);
    })->name('index');

    // 2. Import / Export / Template (MUST match the names used in submission.blade.php)



// Self-Bill Routes (Already exist, but double check names)
Route::get('/export', [SelfInvoiceController::class, 'export'])->name('export');
    Route::post('/import', [SelfInvoiceController::class, 'import'])->name('import');
    Route::get('/download-template', [SelfInvoiceController::class, 'downloadTemplate'])->name('download_template');

    // 3. Management
    Route::put('/update/{id}', [SelfInvoiceController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [SelfInvoiceController::class, 'destroy'])->name('destroy');
    
    // 4. Creation Logic
    Route::get('/create', [SelfInvoiceController::class, 'create'])->name('create');
    Route::post('/store', [SelfInvoiceController::class, 'store'])->name('store');
});
Route::middleware(['auth'])->group(function () {
    // These allow the Blade to find route('invoice.export'), etc.
    Route::get('/invoice/export', [SelfInvoiceController::class, 'export'])->name('invoice.export');
    Route::post('/invoice/import', [SelfInvoiceController::class, 'import'])->name('invoice.import');
    Route::get('/invoice/download-template', [SelfInvoiceController::class, 'downloadTemplate'])->name('invoice.download_template');
});

// URL: https://www.mysynctax.com/dev/cron/check-expired/synctax-secure-2026
Route::get('/cron/check-expired/{secret}', [App\Http\Controllers\SubscriberController::class, 'autoCheckExpired']);

// ==============================================================================
// 🚨 SYSTEM SETUP & QUEUE MANAGEMENT 🚨
// ==============================================================================
Route::middleware(['auth'])->group(function () {

    // 1. Core Worker & Batch Routes
    Route::post('/api/trigger-worker', [App\Http\Controllers\InvoiceSubmissionController::class, 'triggerWorker']);
    Route::post('/api/check-batch', [App\Http\Controllers\InvoiceSubmissionController::class, 'checkBatchProgress']);

    
    // 2. Database Schema Builders (Run these once in the browser, then ignore)
    Route::get('/setup-job-batches', function () {
        // Creates the required table for Laravel Bus::batch()
        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
            return 'job_batches table created perfectly!';
        }
        return 'job_batches table already exists!';
    });

    Route::get('/setup-failed-jobs', function () {
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
            return 'failed_jobs table created perfectly!';
        }
        return 'failed_jobs table already exists!';
    });

});

Route::get('/clear-everything', function () {
    try {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        return "SUCCESS: Config and Cache cleared! You can close this tab.";
    } catch (\Exception $e) {
        return "ERROR: " . $e->getMessage();
    }

});
Route::get('/queue-restart', function () {
    Artisan::call('queue:restart');
    return "Queue workers have been signaled to restart. New code will be loaded on the next job.";
});