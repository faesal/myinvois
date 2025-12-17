<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckApiCredentials
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Extract credentials from headers
        $clientId = $request->header('X-Client-Id');
        $mysynctaxKey = $request->header('X-MySynctax-Key');
        $mysynctaxSecret = $request->header('X-MySynctax-Secret');

        // Validate presence of all required headers
        if (!$clientId || !$mysynctaxKey || !$mysynctaxSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Missing authentication credentials. Required headers: X-Client-Id, X-MySynctax-Key, X-MySynctax-Secret'
            ], 401);
        }

        // Verify credentials against database
        $connection = DB::table('connection_integrate')
            ->where('code', $clientId)
            ->where('mysynctax_key', $mysynctaxKey)
            ->where('mysynctax_secret', $mysynctaxSecret)
            ->first();

        if (!$connection) {
            Log::warning('API Authentication Failed', [
                'client_id' => $clientId,
                'ip' => $request->ip(),
                'endpoint' => $request->path()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication credentials'
            ], 401);
        }

        // Store connection info in request for later use
        $request->merge([
            'authenticated_connection' => $connection->code,
            'id_connection' => $connection->id_connection
        ]);

        Log::info('API Request Authenticated', [
            'client_id' => $clientId,
            'connection' => $connection->name,
            'endpoint' => $request->path()
        ]);

        return $next($request);
    }
}