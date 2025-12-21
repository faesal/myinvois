<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeveloperCustomerController extends Controller
{
    /**
     * SHOW ALL COMPANIES FOR THIS DEVELOPER
     */
    public function index()
{
    $developerId = Auth::id();

    // Fetch companies + UUID + invoice_count + last_sync
    $companies = DB::table('customer')
        ->leftJoin('users', 'users.email', '=', 'customer.email') // JOIN for UUID
        ->leftJoin('invoice', 'invoice.id_customer', '=', 'customer.id_customer')
        ->select(
            'customer.*',
            'users.uuid as user_uuid',  // UUID for Login URL
            DB::raw('COUNT(invoice.id_invoice) AS invoice_count'),
            DB::raw('MAX(invoice.updated_at) AS last_sync')
        )
        ->where('customer.id_developer', $developerId)
        ->where('customer.customer_type', 'SUPPLIER')  // Only suppliers
        ->whereNull('customer.deleted')
        ->groupBy('customer.id_customer')
        ->orderBy('customer.registration_name')
        ->get();

    // Prepare export data (same structure as before)
    $exportCompanies = $companies->map(function ($c) {
        $keysCount = collect([$c->secret_key1, $c->secret_key2, $c->secret_key3])
            ->filter()
            ->count();

        $end = \Carbon\Carbon::parse($c->end_subscribe);
        $now = \Carbon\Carbon::now();
        $daysLeft = $now->diffInDays($end, false);

        if ($daysLeft < 0) {
            $expiresIn = 'Expired ' . abs($daysLeft) . ' days ago';
        } elseif ($daysLeft == 0) {
            $expiresIn = 'Ends today';
        } else {
            $expiresIn = $daysLeft . ' days left';
        }

        return [
            'registration_name' => $c->registration_name,
            'tin_no'            => $c->tin_no,
            'unique_id'         => $c->unique_id,
            'keys_count'        => $keysCount,
            'start_subscribe'   => $c->start_subscribe ?? '',
            'end_subscribe'     => $c->end_subscribe ?? '',
            'expires_in'        => $expiresIn,
        ];
    });

    return view('developer.companies.index', compact('companies', 'exportCompanies'));
}



    /**
     * SHOW ADD COMPANY FORM
     */
    public function create()
    {
        // Fetch developer's connection credentials (MySyncTax API)
        $connection = DB::table('connection_integrate')
            ->where('code', session('connection_integrate'))
            ->first();
    
        return view('developer.companies.add_company', compact('connection'));
    }


    /**
     * STORE NEW COMPANY
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
    
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
                'connection_integrate'=>$generatedCode,
                'registration_name' => $request->registration_name,
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
                ->route('developer.companies.index')
                ->with('success', 'Company & Integration created successfully!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }
    


    /**
     * SHOW INDIVIDUAL COMPANY PAGE
     */
    public function show($id_customer)
{
    $developerId = Auth::id();

    $company = DB::table('customer')
        ->where('id_customer', $id_customer)
        ->where('id_developer', $developerId)
        ->first();

    if (!$company) {
        return redirect()
            ->route('developer.companies.index')
            ->with('error', 'Unauthorized company access.');
    }

    // FETCH correct connection based on CODE
    $connection = DB::table('connection_integrate')
        ->where('code', $company->connection_integrate)
        ->first();

    return view('developer.companies.show', compact('company', 'connection'));
}


    /**
 * SHOW EDIT COMPANY FORM
 */
public function edit($id_customer)
{
    $developerId = Auth::id();

    $company = DB::table('customer')
        ->where('id_customer', $id_customer)
        ->where('id_developer', $developerId)
        ->whereNull('deleted')
        ->first();

    if (!$company) {
        return redirect()
            ->route('developer.companies.index')
            ->with('error', 'Company not found or unauthorized access.');
    }

    $connection = DB::table('connection_integrate')
        ->where('code', $company->connection_integrate)
        ->first();

    return view('developer.companies.edit_company', compact('company', 'connection'));
}


/**
 * UPDATE COMPANY
 */
public function update(Request $request, $id_customer)
{
    try {
        $developerId = Auth::id();

        // Check if company exists and belongs to this developer
        $company = DB::table('customer')
            ->where('id_customer', $id_customer)
            ->where('id_developer', $developerId)
            ->whereNull('deleted')
            ->first();

        if (!$company) {
            return redirect()
                ->route('developer.companies.index')
                ->with('error', 'Company not found or unauthorized access.');
        }

        // VALIDATION
        $request->validate([
            'registration_name' => 'required|string|max:255',
            'tin_no' => 'required|string|max:50',
            'identification_no' => 'required|string|max:50',
            'phone' => 'required|digits_between:9,15',
            'email' => 'required|email|max:100',
            'city_name' => 'required|string|max:100',
            'postal_zone' => 'required|string|max:20',
            'country_subentity_code' => 'required|string|max:10',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'required|string|max:255',
        ]);

        // UPDATE DATABASE
        DB::table('customer')
            ->where('id_customer', $id_customer)
            ->where('id_developer', $developerId)
            ->update([
                'registration_name' => $request->registration_name,
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

                // LHDN KEYS (allow updating)
                'secret_key1' => $request->secret_key1,
                'secret_key2' => $request->secret_key2,
                'secret_key3' => $request->secret_key3,

                'updated_at' => now()
            ]);

        return redirect()
            ->route('developer.companies.index')
            ->with('success', 'Company updated successfully!');

    } catch (\Exception $e) {
        return back()
            ->withInput()
            ->with('error', 'Update failed: ' . $e->getMessage());
    }
}
}
