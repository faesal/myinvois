<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Mail\MySyncTaxCredentialMail;

class MySyncTaxUserController extends Controller
{
    public function sendCredentialEmail($id)
    {
        // =====================================================
        // 1. Get MASTER user (admin DB)
        // =====================================================
        $user = DB::table('users')
            ->where('id', $id)
            ->select('id', 'name', 'email', 'phone')
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        // =====================================================
        // 2. Generate password (ONE TIME)
        // =====================================================
        $plainPassword = Str::random(10);
        $hashedPassword = Hash::make($plainPassword);

        // Update admin DB password
        DB::table('users')
            ->where('id', $id)
            ->update([
                'password'   => $hashedPassword,
                'updated_at' => now()
            ]);

        // =====================================================
        // 3. Subscription dates
        // =====================================================
        $startDate = Carbon::today()->format('Y-m-d');
        $endDate   = Carbon::today()->addYear()->format('Y-m-d');

        // =====================================================
        // 4. Determine environments from ENV
        // =====================================================
        $targets = [];

        if (config('mysynctax.enable_preprod')) {
            $targets[] = 'mysql_preprod';
        }

        if (config('mysynctax.enable_prod')) {
            $targets[] = 'mysql_prod';
        }

        // =====================================================
        // 5. Sync to PREPROD / PROD
        // =====================================================
        foreach ($targets as $conn) {

            // ---------- USERS ----------
            $envUser = DB::connection($conn)
                ->table('users')
                ->where('email', $user->email)
                ->first();

            if ($envUser) {
                DB::connection($conn)
                    ->table('users')
                    ->where('email', $user->email)
                    ->update([
                        'name'       => $user->name,
                        'password'   => $hashedPassword,
                        'phone'      => $user->phone,
                        'updated_at' => now()
                    ]);

                $envUserId = $envUser->id;
            } else {
                $envUserId = DB::connection($conn)
                    ->table('users')
                    ->insertGetId([
                        'name'       => $user->name,
                        'email'      => $user->email,
                        'password'   => $hashedPassword,
                        'phone'      => $user->phone,
                        'role'       => 'subscriber',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            
        }

        // =====================================================
        // 6. Send email (ONCE)
        // =====================================================
        $customer = (object) [
            'registration_name' => $user->name,
            'email'             => $user->email,
        ];

        Mail::to($user->email)->send(
            new MySyncTaxCredentialMail(
                $customer,
                $plainPassword,
                config('mysynctax.enable_prod'),
                config('mysynctax.enable_preprod')
            )
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'User synced based on ENV, subscribed 1 year & email sent'
        ]);
    }
}
