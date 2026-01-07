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

        Route::any('/invoice/resubmit/{id_invoice}', [InvoiceController::class, 'cancelDocument']);

        Route::any('/invoice/cancelDocument/{uuid}', [InvoiceController::class, 'cancelDocument']);

        Route::any('submit_items', [InvoiceController::class, 'submitSelected'])->name('consolidate.submit');

        Route::any('/show_invoice/{id_supplier}/{id_customer}/{id_invoice}', [InvoiceController::class, 'show_invoice']);

        Route::any('select_items', [InvoiceController::class, 'selectItems'])->name('consolidate.select');



        // ========================================================================

        // CONSOLIDATE BATCH IMPORT - ADD THESE ROUTES HERE

        // ========================================================================

        

       // Consolidate Import Routes
   // PLACE THE BLOCK HERE
    Route::prefix('consolidate')->name('consolidate.')->group(function () {
        Route::get('/import', [ConsolidateImportController::class, 'index'])->name('import');
        Route::get('/template', [ConsolidateImportController::class, 'downloadTemplate'])->name('template');
        Route::post('/import', [ConsolidateImportController::class, 'importBatch'])->name('import.process');
        Route::get('/view/{id}', [ConsolidateImportController::class, 'view'])->name('view');
        Route::get('/export-items/{id}', [ConsolidateImportController::class, 'exportItems'])->name('export.items');
        
        // Parent Batch Actions
        Route::post('/update/{id}', [ConsolidateImportController::class, 'update'])->name('update');
        Route::get('/delete/{id}', [ConsolidateImportController::class, 'destroy'])->name('delete');

        // Child Item Actions (AJAX) - THESE ARE FOR THE VIEW PAGE
        Route::post('/item/update/{id}', [ConsolidateImportController::class, 'updateItem'])->name('item.update');
        Route::post('/item/add/{invoice_id}', [ConsolidateImportController::class, 'addItem'])->name('item.add');
        Route::post('/item/delete-record/{id}', [ConsolidateImportController::class, 'deleteItem'])->name('item.delete');
    });
        // ========================================================================

        // END CONSOLIDATE BATCH IMPORT ROUTES

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



    Route::get('/developer/profile', [DeveloperProfileController::class, 'edit'])

        ->name('developer.profile.edit');



    Route::put('/developer/profile', [DeveloperProfileController::class, 'update'])

        ->name('developer.profile.update');





    // Invoices

    Route::any('/invoices', [InvoiceSubmissionController::class, 'index'])

        ->name('developer.invoices.index');



    Route::get('/invoices/{id_invoice}/view', [InvoiceSubmissionController::class, 'view'])

        ->name('developer.invoices.view');



    Route::get('/invoice/{id_customer}/{id_invoice}', [InvoiceSubmissionController::class, 'showInvoice'])

        ->name('developer.invoice.show');



    Route::post('/developer/invoices/submit-selected', [InvoiceSubmissionController::class, 'submitSelectedInvoices'])

        ->name('developer.invoices.submitSelected');





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



    // Consolidate Import Routes

    Route::prefix('consolidate')->name('consolidate.')->group(function () {

        

        // Main import page

        Route::get('/import', [ConsolidateImportController::class, 'index'])

            ->name('import');

        

        // Download Excel template

        Route::get('/template', [ConsolidateImportController::class, 'downloadTemplate'])

            ->name('template');

        

        // Upload and process Excel file

        Route::post('/import', [ConsolidateImportController::class, 'importBatch'])

            ->name('import.process');

        

        // View invoice details

        Route::get('/view/{id}', [ConsolidateImportController::class, 'view'])

            ->name('view');

        

        // Delete invoice

        Route::delete('/delete/{id}', [ConsolidateImportController::class, 'delete'])

            ->name('delete');

        

        // Export items to Excel (optional feature)

        Route::get('/export-items/{id}', [ConsolidateImportController::class, 'exportItems'])

            ->name('export.items');
            
            // Edit/Update Route
    Route::post('/update/{id}', [ConsolidateImportController::class, 'update'])->name('update');
    
    // Delete Route
    Route::get('/delete/{id}', [ConsolidateImportController::class, 'destroy'])->name('delete');

    });



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


