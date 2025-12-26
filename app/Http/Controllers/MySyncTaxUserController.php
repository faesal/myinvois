<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\MySyncTaxCredentialMail;
use App\Mail\MySyncTaxApproachMail;
use Illuminate\Support\Facades\Log;
class MySyncTaxUserController extends Controller
{

    
    public function sendApproachEmail()
    {
        $leads = DB::table('crm_leads')
            ->select('email')
            ->where('status', 'New')
            ->whereNotNull('email')
            ->groupBy('email')
            ->get();

        if ($leads->isEmpty()) {
            return 'No new POS leads found';
        }

        foreach ($leads as $lead) {

            try {

                Mail::to($lead->email)
                    ->cc('faesal@xideasoft.com')
                    ->send(new MySyncTaxApproachMail('Mr. Ahmad'));

                DB::table('crm_leads')
                    ->where('email', $lead->email)
                    ->update([
                        'status'     => 'Contacted',
                        'updated_at' => now()
                    ]);

            } catch (\Throwable $e) {

                // 🔴 INI YANG SEBELUM NI TAK DITANGKAP
                Log::warning('Email send failed', [
                    'email' => $lead->email,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                DB::table('crm_leads')
                    ->where('email', $lead->email)
                    ->update([
                        'status'     => 'Invalid Email',
                        'updated_at' => now()
                    ]);

                // TERUSKAN LOOP
                continue;
            }

            // OPTIONAL: slow down to avoid SMTP throttling
            usleep(300000); // 0.3s
        }

        return 'POS approach emails processed (errors skipped)';
    }


    public function sendApproachEmail4()
    {
        Mail::to('faesal09@gmail.com')
            ->send(new MySyncTaxApproachMail('Mr. Ahmad'));

        return 'Email sent successfully';
    }

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


        DB::table('connection_integrate')->insert([
            'user_id'          => @$developerId,
            'code'             => 'DEV-' . strtoupper(Str::random(8)),
            'name'             => $request->name,
            'mysynctax_key'    => $mysynctax_key,
            'mysynctax_secret' => $mysynctax_secret,
            'api_token'        => $api_token,
            'created_at'       => now(),
        ]);
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
