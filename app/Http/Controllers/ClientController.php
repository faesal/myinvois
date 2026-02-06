<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Auth;

class ClientController extends Controller
{
    // -------------------------------------------
    // HELPER: Get Correct Linking Key
    // -------------------------------------------
    private function getConnectionKey($customerId)
    {
        $customer = DB::table('customer')->where('id_customer', $customerId)->first();

        if (!$customer) {
            return null;
        }

        // Priority 1: Use connection_integrate string
        if (!empty($customer->connection_integrate)) {
            return $customer->connection_integrate;
        }

        // Priority 2: Use user_id
        if (!empty($customer->user_id)) {
            return $customer->user_id;
        }

        return null;
    }

    // -------------------------------------------
    // SHOW ADD CLIENT FORM
    // -------------------------------------------
    public function create()
    {
        return view('developer.add_client');
    }

    // -------------------------------------------
    // STORE NEW CLIENT
    // -------------------------------------------
    public function store(Request $request)
    {
        try {
            $developerId = Auth::id();

            // VALIDATION
            $request->validate([
                'registration_name' => 'required|string',
                'tin_no' => 'required|string',
                'identification_type' => 'required|string',
                'identification_no' => 'required|string',
                'phone' => 'required|digits_between:9,15',
                'email' => 'required|string|email',
                'city_name' => 'required|string',
                'postal_zone' => 'required|string',
                'country_subentity_code' => 'required|string',
                'address_line_1' => 'required|string',
                'address_line_2' => 'required|string',
                'address_line_3' => 'nullable|string',
            ]);

            $email = $request->email;
            if (DB::table('users')->where('email', $email)->exists()) {
                throw new \Exception('Email already exists in users table.');
            }

            $randomPasswordPlain = Str::random(12);
            $randomPasswordHash = Hash::make($randomPasswordPlain);

            $userId = DB::table('users')->insertGetId([
                'name' => $request->registration_name,
                'email' => $email,
                'password' => $randomPasswordHash,
                'role' => 'subscriber',
                'phone' => $request->phone,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            do {
                $generatedCode = 'CUST-' . str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
            } while (DB::table('connection_integrate')->where('code', $generatedCode)->exists());

            do {
                $token = Str::random(40);
            } while (DB::table('connection_integrate')->where('api_token', $token)->exists());

            $mysynctaxKey = Str::random(16);
            $mysynctaxSecret = Str::random(16);

            DB::table('connection_integrate')->insert([
                'id_developer' => $developerId,
                'user_id' => $userId,
                'code' => $generatedCode,
                'mysynctax_key' => $mysynctaxKey,
                'mysynctax_secret' => $mysynctaxSecret,
                'api_token' => $token,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('customer')->insert([
                'id_developer' => $developerId,
                'user_id' => $userId,
                'customer_type' => 'SUPPLIER',
                'registration_name' => $request->registration_name,
                'connection_integrate' => $generatedCode,
                'tin_no' => $request->tin_no,
                'identification_type' => $request->identification_type,
                'identification_no' => $request->identification_no,
                'phone' => $request->phone,
                'email' => $request->email,
                'city_name' => $request->city_name,
                'postal_zone' => $request->postal_zone,
                'country_subentity_code' => $request->country_subentity_code,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'address_line_3' => $request->address_line_3,
                'secret_key1' => $request->secret_key1,
                'secret_key2' => $request->secret_key2,
                'secret_key3' => $request->secret_key3,
                'is_activation' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return redirect()
                ->route('developer.dashboard')
                ->with('success', 'Client added successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    // -------------------------------------------
    // EXPORT CLIENT SUMMARY (EXCEL)
    // -------------------------------------------
    public function export()
    {
        $developerId = Auth::user()->id;

        $clients = DB::table('customer')
            ->where('id_developer', $developerId)
            ->get();

        $filename = 'mysynctax_clients_' . date('Y-m-d') . '.xml';

        $headers = [
            "Content-Type"        => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $xml = '<?xml version="1.0"?>
            <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet">
            <Worksheet ss:Name="Clients">
            <Table>';

        $xml .= "
            <Row>
                <Cell><Data ss:Type=\"String\">Client Name</Data></Cell>
                <Cell><Data ss:Type=\"String\">TIN No</Data></Cell>
                <Cell><Data ss:Type=\"String\">Unique ID</Data></Cell>
                <Cell><Data ss:Type=\"String\">Keys Count</Data></Cell>
                <Cell><Data ss:Type=\"String\">Start Subscribe</Data></Cell>
                <Cell><Data ss:Type=\"String\">End Subscribe</Data></Cell>
                <Cell><Data ss:Type=\"String\">Expires In</Data></Cell>
            </Row>";

        foreach ($clients as $c) {
            $expires = '';
            if ($c->start_subscribe && $c->end_subscribe) {
                $endTs = strtotime($c->end_subscribe);
                $diff  = $endTs - time();
                $expires = ($diff > 0) ? floor($diff / 86400) . " days" : "Expired";
            }

            $keysCount = collect([$c->secret_key1, $c->secret_key2, $c->secret_key3])->filter()->count();

            $xml .= "
                <Row>
                    <Cell><Data ss:Type=\"String\">{$c->registration_name}</Data></Cell>
                    <Cell><Data ss:Type=\"String\">{$c->tin_no}</Data></Cell>
                    <Cell><Data ss:Type=\"String\">{$c->unique_id}</Data></Cell>
                    <Cell><Data ss:Type=\"Number\">{$keysCount}</Data></Cell>
                    <Cell><Data ss:Type=\"String\">{$c->start_subscribe}</Data></Cell>
                    <Cell><Data ss:Type=\"String\">{$c->end_subscribe}</Data></Cell>
                    <Cell><Data ss:Type=\"String\">{$expires}</Data></Cell>
                </Row>";
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return Response::make($xml, 200, $headers);
    }

    // -------------------------------------------
    // EDIT CLIENT
    // -------------------------------------------
    public function edit($id_customer)
    {
        $user = Auth::user();

        $query = DB::table('customer')->where('id_customer', $id_customer);
        
        if ($user->role !== 'admin') {
            $query->where('id_developer', $user->id);
        }

        $client = $query->first();

        if (!$client) {
            return redirect()->back()->with('error', 'Client not found or unauthorized.');
        }

        $connection = DB::table('connection_integrate')
            ->where('user_id', $client->user_id)
            ->first();

        if (!$connection) {
            $connection = (object)[
                'mysynctax_key' => 'N/A',
                'mysynctax_secret' => 'N/A'
            ];
        }

        $connectionKey = $this->getConnectionKey($id_customer);
        
        $consolidation = DB::table('consolidate_setting')
            ->where('connection_integrate', $connectionKey)
            ->first();

        $ip_list = DB::table('ip_management')
            ->where('connection_integrate', $connectionKey)
            ->get();

        return view('developer.edit_client', compact('client', 'connection', 'consolidation', 'ip_list'));
    }

    // -------------------------------------------
    // UPDATE CLIENT (MAIN FORM)
    // -------------------------------------------
    public function update(Request $request, $id_customer)
    {
        $user = Auth::user();

        $request->validate([
            'registration_name' => 'required|string|max:255',
            'tin_no'            => 'required|string|max:50',
            'identification_no' => 'required|string|max:50',
            'phone'             => 'required|digits_between:9,15',
            'email'             => 'required|email|max:100',
            'address_line_1'    => 'required|string|max:255',
            'address_line_2'    => 'required|string|max:255',
            'city_name'         => 'required|string|max:100',
            'postal_zone'       => 'required|string|max:20',
            'country_subentity_code' => 'required|string|max:10',
        ]);

        $query = DB::table('customer')->where('id_customer', $id_customer);

        if ($user->role !== 'admin') {
            $query->where('id_developer', $user->id);
        }

        $isIpWhitelistEnabled = $request->has('is_ip_whitelist_enabled') ? 1 : 0;

        $query->update([
            'registration_name'  => $request->registration_name,
            'tin_no'             => $request->tin_no,
            'identification_no'  => $request->identification_no,
            'identification_type'=> $request->identification_type,
            'phone'              => $request->phone,
            'email'              => $request->email,
            'address_line_1'     => $request->address_line_1,
            'address_line_2'     => $request->address_line_2,
            'address_line_3'     => $request->address_line_3,
            'city_name'          => $request->city_name,
            'postal_zone'        => $request->postal_zone,
            'country_subentity_code' => $request->country_subentity_code,
            'secret_key1'        => $request->secret_key1,
            'secret_key2'        => $request->secret_key2,
            'secret_key3'        => $request->secret_key3,
            'is_ip_whitelist_enabled' => $isIpWhitelistEnabled,
            'updated_at'         => now(),
        ]);

        $route = ($user->role === 'admin') ? 'admin.subscribers.index' : 'developer.dashboard';

        return redirect()
            ->route($route)
            ->with('success', 'Client updated successfully.');
    }

    // -------------------------------------------
    // AJAX: SAVE CONSOLIDATION SETTINGS
    // -------------------------------------------
 // -------------------------------------------
    // AJAX: SAVE CONSOLIDATION SETTINGS
    // -------------------------------------------
 // -------------------------------------------
    // AJAX: SAVE CONSOLIDATION SETTINGS
    // -------------------------------------------
 public function saveConsolidation(Request $request, $id)
    {
        try {
            $connectionKey = $this->getConnectionKey($id);
            if (!$connectionKey) {
                return response()->json(['success' => false, 'message' => 'Customer link not found.'], 404);
            }

            // 1. Basic Validation
            $validator = Validator::make($request->all(), [
                'freq' => 'required|in:daily,weekly,monthly,specific',
                'specific_date' => 'nullable|integer|min:1|max:31',
                'is_enabled' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            $freq = $request->freq;
            $specificDate = $request->specific_date;
            
            // ---------------------------------------------------------
            // ✅ EXCEPTION CHECK: Catch missing date for "Specific" option
            // ---------------------------------------------------------
            if ($freq === 'specific' && empty($specificDate)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Specific Date must be inserted first.'
                ], 422); // 422 Unprocessable Entity
            }
            // ---------------------------------------------------------

            // Handle Boolean conversions
            $sendEmail = filter_var($request->email_notif, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $isEnabled = filter_var($request->is_enabled, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

            // Calculate Next Consolidation Date
            $nextDate = null;
            if ($isEnabled) {
                $now = now();
                switch ($freq) {
                    case 'daily':
                        $nextDate = $now->copy()->addDay()->startOfDay();
                        break;
                    case 'weekly':
                        $nextDate = $now->copy()->next('Sunday')->startOfDay();
                        break;
                    case 'monthly':
                        $endOfMonth = $now->copy()->endOfMonth()->startOfDay();
                        if ($now->isSameDay($endOfMonth)) {
                            $nextDate = $now->copy()->addMonth()->endOfMonth()->startOfDay();
                        } else {
                            $nextDate = $endOfMonth;
                        }
                        break;
                    case 'specific':
                        // We already checked !empty($specificDate) above, so safe to proceed
                        $candidateDate = $now->copy()->day($specificDate)->startOfDay();
                        if ($now->greaterThan($candidateDate)) {
                            $nextDate = $now->copy()->addMonth()->day($specificDate)->startOfDay();
                        } else {
                            $nextDate = $candidateDate;
                        }
                        break;
                }
            }

            // Prepare Data
            $data = [
                'connection_integrate' => $connectionKey,
                'is_enabled'       => $isEnabled,
                'is_daily'         => ($freq === 'daily') ? 1 : 0,
                'is_weekly'        => ($freq === 'weekly') ? 1 : 0,
                'is_monthly'       => ($freq === 'monthly') ? 1 : 0,
                'is_spesific_date' => ($freq === 'specific') ? $specificDate : '', 
                'is_custom_daily_interval' => '', 
                'next_consolidate' => $nextDate ? $nextDate->format('Y-m-d H:i:s') : '',
                'is_send_email'    => $sendEmail,
            ];

            // Update or Insert
            $exists = DB::table('consolidate_setting')
                ->where('connection_integrate', $connectionKey)
                ->exists();

            if ($exists) {
                DB::table('consolidate_setting')
                    ->where('connection_integrate', $connectionKey)
                    ->update($data);
            } else {
                $data['created_date'] = now(); 
                DB::table('consolidate_setting')->insert($data);
            }

            return response()->json([
                'success' => true, 
                'message' => 'Consolidation settings saved.',
                'next_run' => $nextDate ? $nextDate->format('Y-m-d') : 'Paused'
            ]);

        } catch (\Exception $e) {
            Log::error("Consolidate Save Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    // -------------------------------------------
    // AJAX: STORE IP ADDRESS
    // -------------------------------------------
    public function storeIp(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'ip' => 'required|ip',
            'desc' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $connectionKey = $this->getConnectionKey($id);
            if (!$connectionKey) {
                return response()->json(['success' => false, 'message' => 'Customer link not found.'], 404);
            }

            $newId = DB::table('ip_management')->insertGetId([
                'connection_integrate' => $connectionKey,
                'whitelist_ip' => $request->ip,
                'ip_description' => $request->desc ?? '',
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'IP Added', 
                'id' => $newId
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // -------------------------------------------
    // AJAX: DELETE IP ADDRESS
    // -------------------------------------------
    public function destroyIp($id)
    {
        try {
            DB::table('ip_management')->where('id_ip_managment', $id)->delete();
            return response()->json(['success' => true, 'message' => 'IP Removed']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // -------------------------------------------
    // AJAX: UPDATE IP WHITELIST TOGGLE
    // -------------------------------------------
    public function updateIpWhitelistToggle(Request $request, $id)
    {
        try {
            $isEnabled = $request->is_enabled ? 1 : 0;
            
            DB::table('customer')
                ->where('id_customer', $id)
                ->update([
                    'is_ip_whitelist_enabled' => $isEnabled,
                    'updated_at' => now()
                ]);

            return response()->json(['success' => true, 'message' => 'IP Whitelist status updated!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}