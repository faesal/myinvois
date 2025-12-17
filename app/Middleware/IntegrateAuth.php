<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IntegrateAuth
{
    /**
     * Expected headers:
     *  - X-Client-Code
     *  - X-Api-Key
     *  - X-Timestamp (ISO8601)
     *  - X-Signature = HMAC-SHA256(secret, "{timestamp}\n{METHOD}\n{PATH}\n{sha256(body)}")
     */
    public function handle(Request $request, Closure $next)
    {
        $code      = $request->header('X-Client-Code');
        $apiKey    = $request->header('X-Api-Key');
        $timestamp = $request->header('X-Timestamp');
        $sig       = $request->header('X-Signature');

        if (!$code || !$apiKey || !$timestamp || !$sig) {
            return response()->json(['error' => 'Missing authentication headers'], 401);
        }

        try {
            $ts = Carbon::parse($timestamp);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid timestamp'], 401);
        }
        // ±300 seconds drift
        if (abs(now()->diffInSeconds($ts, false)) > 300) {
            return response()->json(['error' => 'Timestamp drift too large'], 401);
        }

        $conn = DB::table('connection_integrate')
            ->select('code','mysynctax_key','mysynctax_secret')
            ->where('code', $code)
            ->first();

        if (!$conn || !hash_equals((string)$conn->mysynctax_key, (string)$apiKey)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $method   = strtoupper($request->getMethod());
        $path     = $request->getPathInfo();
        $rawBody  = $request->getContent() ?? '';
        $bodyHash = hash('sha256', $rawBody);
        $canonical = $timestamp."\n".$method."\n".$path."\n".$bodyHash;

        $expected = hash_hmac('sha256', $canonical, (string)$conn->mysynctax_secret);
        if (!hash_equals($expected, (string)$sig)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Attach resolved connection for downstream use
        $request->attributes->set('connection_integrate_code', $conn->code);
        $request->attributes->set('connection_integrate_row', $conn);

        return $next($request);
    }
}
