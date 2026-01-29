<?php



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

        Route::any('/listing_submission', [InvoiceController::class, 'listing_submission']);

        Route::get('/invoice/create', [InvoiceController::class, 'create'])->name('invoice.create');
        
        Route::post('/invoice/store_create', [InvoiceController::class, 'store_create'])->name('invoice.store_create');

       // Route::any('/invoice/resubmit/{id_invoice}', [InvoiceController::class, 'cancelDocument']);

      //  Route::any('/invoice/cancelDocument/{uuid}', [InvoiceController::class, 'cancelDocument']);

        Route::any('submit_items', [InvoiceController::class, 'submitSelected'])->name('consolidate.submit');

        Route::get('/show_invoice/{unique_id}', [InvoiceController::class, 'show_invoice'])->name('invoice.show');

        Route::any('select_items', [InvoiceController::class, 'selectItems'])->name('consolidate.select');

        Route::delete('/consolidate/item/delete/{id}', [InvoiceController::class, 'deleteConsolidateItem'])->name('consolidate.item.delete');

        Route::post('/invoice/submit-selected-lhdn', [InvoiceController::class, 'submitSelectedLHDN'])->name('invoice.submit_selected_lhdn');

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





// Protected Developer Section (login required)

Route::middleware(['auth'])->group(function () {



    Route::any('/developer/ConsolidateSelected', [InvoiceSubmissionController::class, 'ConsolidateSelected']);

    Route::any('/developer/consolidate', [InvoiceSubmissionController::class, 'consolidate']);

    Route::delete('/developer/consolidate/delete/{id}', [InvoiceSubmissionController::class, 'destroyConsolidateItem']);

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



    Route::post('/developer/client/update/{id_customer}', [ClientController::class, 'update'])

        ->name('developer.client.update');
    
    Route::post('/client/settings/ip-whitelist-toggle/{id}', [ClientController::class, 'updateIpWhitelistToggle'])
    ->name('client.settings.ip_toggle');

    // 1. Consolidation Settings (Frequency & Toggle)
    Route::post('/client/settings/consolidate/{id}', [ClientController::class, 'saveConsolidation'])
        ->name('client.settings.consolidate');

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
   


    // Invoices

    Route::any('/invoices', [InvoiceSubmissionController::class, 'index'])

        ->name('developer.invoices.index');



    Route::get('/invoices/{id_invoice}/view', [InvoiceSubmissionController::class, 'view'])

        ->name('developer.invoices.view');




    Route::post('/developer/invoices/submit-selected', [InvoiceSubmissionController::class, 'submitSelectedInvoices'])

        ->name('developer.invoices.submitSelected');


    Route::get('/developer/invoice/delete/{id}', [App\Http\Controllers\InvoiceSubmissionController::class, 'deleteInvoice'])
    
        ->name('developer.invoices.delete');




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

Route::any('/resubmit/{id}', [InvoiceController::class, 'resubmit']);

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
// Grouping them under a prefix is usually cleaner
Route::prefix('manage-customer')->name('manage_customer.')->group(function () {
    
    // --- 1. Custom Actions (Must be defined first to avoid conflict with {id}) ---
    Route::post('/import', [ManageCustomerController::class, 'import'])->name('import');
    Route::get('/export', [ManageCustomerController::class, 'export'])->name('export');
    
    // FIX: Simplified here because the group already adds "manage-customer" prefix and "manage_customer." name
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

    // Delete Action (manage_customer.destroy)
    Route::delete('/{id}', [ManageCustomerController::class, 'destroy'])->name('destroy');
});
Route::group(['prefix' => 'self_bill', 'as' => 'self_invoice.', 'middleware' => ['auth']], function () {
    
    // 1. Listing & Management
    Route::get('/listing', [SelfInvoiceController::class, 'index'])->name('index');
    Route::put('/update/{id}', [SelfInvoiceController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [SelfInvoiceController::class, 'destroy'])->name('destroy');
    
    // 2. Creation Process (Standard Invoice Type 11)
    Route::get('/create', [SelfInvoiceController::class, 'create'])->name('create');
    Route::post('/store', [SelfInvoiceController::class, 'store'])->name('store');

    // 3. Note Creation (Credit/Debit/Refund)
    // IMPORTANT: You must add the 'createNote' method to SelfInvoiceController (code below)
    Route::get('/create-note/{note_type}', [SelfInvoiceController::class, 'createNote'])->name('create_note');
    
    // 4. Import / Export / Template
    Route::get('/download-template', [SelfInvoiceController::class, 'downloadTemplate'])->name('download_template');
    Route::get('/export', [SelfInvoiceController::class, 'export'])->name('export');
    Route::post('/import', [SelfInvoiceController::class, 'import'])->name('import');

    // 5. AJAX Helpers
    // Only needed if you have dynamic item loading; otherwise safe to keep or remove
    Route::get('/fetch-items/{id}', [SelfInvoiceController::class, 'fetchItems'])->name('fetch_items');
});

// URL: https://www.mysynctax.com/dev/cron/check-expired/synctax-secure-2026
Route::get('/cron/check-expired/{secret}', [App\Http\Controllers\SubscriberController::class, 'autoCheckExpired']);