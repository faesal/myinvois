<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable; 
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\eInvoisModel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter; // 🚀 Added Native RateLimiter

class SubmitInvoicesBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $microChunks;
    public $consolidateStatus;
    public $connectionIntegrate;

    // Timeout increased to 5 minutes to allow for parallel processing of large batches
    public $timeout = 300; 
    
    // 🚀 Increased to 5. If the worker has to wait in line, it counts as a "try".
    public $tries = 5;     

    public function __construct(array $microChunks, $consolidateStatus, $connectionIntegrate)
    {
        $this->microChunks = $microChunks;
        $this->consolidateStatus = $consolidateStatus;
        $this->connectionIntegrate = $connectionIntegrate;
    }

    public function handle()
    {
        // 🚨 CANCEL CHECK
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        // ==============================================================
        // 🚀 1. THE TRAFFIC COP: Max 30 requests per 60 seconds (No Redis!)
        // This stops the 10k batch from triggering LHDN's 400 Bad Request firewall.
        // ==============================================================
        $limitKey = 'lhdn_api_submissions';

        // If we have hit 30 attempts in the last minute...
        if (RateLimiter::tooManyAttempts($limitKey, 30)) {
            // Ask how many seconds are left until the minute resets
            $secondsToWait = RateLimiter::availableIn($limitKey);
            
            Log::info("LHDN Traffic control active. Delaying job for {$secondsToWait} seconds.");
            
            // Put the job back in the queue to sleep. It will auto-retry safely later!
            return $this->release($secondsToWait);
        }

        // We are under the limit! Record this attempt for the next 60 seconds.
        RateLimiter::hit($limitKey, 60);
        // ==============================================================

        // 2. Re-hydrate session variables for Model logic
        Session::put('consolidate_status', $this->consolidateStatus);
        Session::put('connection_integrate', $this->connectionIntegrate);

        $model = new eInvoisModel();
        $model->consolidate_status = $this->consolidateStatus; 
        
        try {
            // Flatten the nested micro-chunks into a single array of IDs
            $invoiceIds = collect($this->microChunks)->flatten()->toArray();

            if (empty($invoiceIds)) {
                return;
            }

            // 3. Call the concurrent submitBatch
            $model->submitBatch($invoiceIds);

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            $errorCode = (string)$e->getCode();
            
            // 4. Handle LHDN Rate Limits (429) gracefully
            if (str_contains($errorCode, '429') || str_contains($errorMsg, '429') || str_contains(strtolower($errorMsg), 'rate limit')) {
                preg_match('/in (\d+) seconds/', $errorMsg, $matches);
                $retryAfter = isset($matches[1]) ? (int)$matches[1] : 60; 
                
                Log::warning("LHDN Rate Limit hit. Retrying job in {$retryAfter} seconds.");
                return $this->release($retryAfter); 
            }

            // 5. FRONTEND ERROR REPORTING
            if ($this->batch()) {
                Cache::put('batch_error_' . $this->batch()->id, $errorMsg, 3600); // Store for 1 hour
            }

            Log::error("Job Failed: " . $errorMsg);
            throw $e; 
        }
    }
}