<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
    // --- Cancel Document Routes ---
    'api/v1/external/cancel-document/*',
    'dev/api/v1/external/cancel-document/*',
    'api/myinvois/cancelDocument/*',
    'dev/api/myinvois/cancelDocument/*',

    // --- Resubmit & Bulk Routes ---
    'api/invoice/resubmit/*', 
    'dev/api/invoice/resubmit/*',
    'api/invoices/bulk-resubmit',
    'dev/api/invoices/bulk-resubmit',
    
    // --- Other MyInvois Integration Routes ---
    'api/myinvois/*',
    'dev/api/myinvois/*',
];
}
