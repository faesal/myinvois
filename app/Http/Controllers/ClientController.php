<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Auth;

class ClientController extends Controller
{
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
            'phone' => 'required|string',
            'email' => 'required|string|email',
            'city_name' => 'required|string',
            'postal_zone' => 'required|string',
            'country_subentity_code' => 'required|string',
            'address_line_1' => 'required|string',
            'address_line_2' => 'required|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. CREATE USER
        |--------------------------------------------------------------------------
        */
        $email = $request->email;
        // Ensure email is unique
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

        DB::commit();
       
        /*
        |--------------------------------------------------------------------------
        | 3. CREATE CONNECTION_INTEGRATE
        |--------------------------------------------------------------------------
        */
        // Generate unique code
        do {
            $generatedCode = 'CUST-' . str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (DB::table('connection_integrate')->where('code', $generatedCode)->exists());

        // Generate unique api_token
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

        DB::commit();


         /*
        |--------------------------------------------------------------------------
        | 1. INSERT INTO CUSTOMER
        |--------------------------------------------------------------------------
        */
        $customerId = DB::table('customer')->insertGetId([
            'id_developer' => $developerId,
            'user_id'=>$userId,
            'customer_type'=>'SUPPLIER',
            'registration_name' => $request->registration_name,
            'connection_integrate'=>$generatedCode,
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

        DB::commit();

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

                $expires = ($diff > 0)
                    ? floor($diff / 86400) . " days"
                    : "Expired";
            }

            $keysCount = collect([$c->secret_key1, $c->secret_key2, $c->secret_key3])
                            ->filter()
                            ->count();

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

    public function edit($id_customer)
    {
        $developerId = Auth::user()->id;

        $client = DB::table('customer')
            ->where('id_customer', $id_customer)
            ->where('id_developer', $developerId)
            ->first();

        $connection = DB::table('connection_integrate')
            ->where('user_id', $client->user_id)
            ->first();

 
        if (!$client) {
            return redirect()->route('developer.dashboard')->with('error', 'Client not found.');
        }

        return view('developer.edit_client', compact('client', 'connection'));
    }


public function update(Request $request, $id_customer)
{
    $developerId = Auth::user()->id;

    $request->validate([
        'registration_name' => 'required|string|max:255',
        'tin_no'            => 'required|string|max:50',
        'identification_type' => 'required|string|max:50',
        'identification_no' => 'required|string|max:50',
        'phone'             => 'required|string|max:50',
        'email'             => 'required|email|max:100',
        'address_line_1'    => 'required|string|max:255',
        'address_line_2'    => 'required|string|max:255',
        'city_name'         => 'required|string|max:100',
        'postal_zone'       => 'required|string|max:20',
        'country_subentity_code' => 'required|string|max:10',
        // 'start_subscribe'   => 'required|date',
        // 'end_subscribe'     => 'required|date|after_or_equal:start_subscribe',
        // 'is_activation'     => 'required|in:0,1',
    ]);

    DB::table('customer')
        ->where('id_customer', $id_customer)
        ->where('id_developer', $developerId)
        ->update([
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
            // 'start_subscribe'    => $request->start_subscribe,
            // 'end_subscribe'      => $request->end_subscribe,
            // 'is_activation'      => $request->is_activation,
            'updated_at'         => now(),
        ]);

    return redirect()
        ->route('developer.dashboard')
        ->with('success', 'Client updated successfully.');
}

}
