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

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/subscriberLogin/{uuid}', [AuthController::class, 'subscriberLogin'])
    ->name('subscriber.login');


// Developer Documentation Route
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


    // Invoices
    Route::get('/invoices', [InvoiceSubmissionController::class, 'index'])
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
