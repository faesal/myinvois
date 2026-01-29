<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\CustomerResource;
use App\Http\Requests\CustomerRequest;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $customers = DB::table('customer')->paginate($perPage);
        return CustomerResource::collection($customers);
    }
   
    public function public_create()
    {
        $customer = '';
        return view('customers.public_create', ['customer' => $customer]);
    }
 
    public function form_customer($id = null)
    {
        $customer = null;
        if ($id) {
            $customer = DB::table('customer')->where('id_customer', $id)->first();
        }
        
        return view('customers.create_customer', ['customer' => $customer]);
    }

    public function listing_customer(Request $request)
    {
        $user = auth()->user(); 
        $query = DB::table('customer');
    
        $query->where('customer_type', 'CUSTOMER');
    
        if ($user->role !== 'admin') {
            $query->where('connection_integrate', session('connection_integrate'));
        }
    
        if ($request->search) {
            $query->where('registration_name', 'like', '%' . $request->search . '%');
        }
    
        if ($request->country) {
            $query->where('country_code', $request->country);
        }
    
        $query->whereNull('deleted');
    
        $customers = $query->orderBy('id_customer', 'desc')->get();
    
        return view('customers.listing', ['customers' => $customers]);
    }
    
    /**
     * Display Step 1 - Fetching Supplier & Invoice Number via URL Hash
     */
    public function create($invoice_unique_id)
    {
        session(['invoice_unique_id' => $invoice_unique_id]);
        
        // 1. Find the invoice to see which business (connection) it belongs to
        $invoice = DB::table('invoice')
            ->where('unique_id', $invoice_unique_id)
            ->first();

        $supplier = null;

        if ($invoice) {
            // 2. Fetch the SUPPLIER record matching the invoice's connection
            $supplier = DB::table('customer')
                ->where('connection_integrate', $invoice->connection_integrate)
                ->where('customer_type', 'SUPPLIER')
                ->first();
        }

        return view('customers.create', [
            'supplier' => $supplier, 
            'invoice' => $invoice,
            'invoice_unique_id' => $invoice_unique_id
        ]);
    }

    public function show($id)
    {
        $customer = DB::table('customer')->where('id_customer', $id)->first();
        
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return new CustomerResource($customer);
    }

    public function generateUniqueId() {
        return strtoupper(
            substr(md5(mt_rand()), 0, 3).substr(md5(mt_rand()), 0, 3).substr(md5(mt_rand()), 0, 3).substr(md5(mt_rand()), 0, 3).substr(md5(mt_rand()), 0, 3)
        );
    }

    /**
     * Check TIN - Maintains the link to the supplier via the invoice context
     */
    public function checkTinNo(Request $request)
    {
        $findTin = $request->input('tin_no_check');
        $invHash = $request->input('invoice_unique_id');
        
        // 1. Find the buyer (Customer) by their TIN
        $customer = DB::table('customer')->where('tin_no', $findTin)->first();

        // 2. Re-fetch Invoice and Supplier info to keep the header context
        $invoice = DB::table('invoice')->where('unique_id', $invHash)->first();
        $supplier = null;

        if ($invoice) {
            $supplier = DB::table('customer')
                ->where('connection_integrate', $invoice->connection_integrate)
                ->where('customer_type', 'SUPPLIER')
                ->first();
        }

        return view('customers.create', [
            'customer' => $customer,
            'supplier' => $supplier, 
            'invoice' => $invoice,
            'tin_no' => $findTin,
            'invoice_unique_id' => $invHash
        ]);
    }

    public function add_customer(CustomerRequest $request)
    {
        $validated = $request->validate([
            'registration_name' => 'required',
            'tin_no' => 'required',
            'identification_no' => 'required',
            'identification_type' => 'required',
            'phone' => 'required',
            'email' => 'required|email',
            'address_line_1' => 'required',
            'city_name' => 'required',
            'postal_zone' => 'required',
            'country_subentity_code' => 'required',
        ]);

        $data = $request->only([
            'registration_name', 'tin_no', 'identification_no', 'identification_type',
            'sst_registration', 'phone', 'email', 'city_name', 'postal_zone', 'country_subentity_code',
            'country_code', 'address_line_1', 'address_line_2', 'address_line_3'
        ]);

        $data['connection_integrate'] = session('connection_integrate');
        $data['customer_type'] = 'CUSTOMER';
        $data['updated_at'] = now();

        try {
            if ($request->id_customer) {
                DB::table('customer')
                    ->where('id_customer', $request->id_customer)
                    ->update($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Customer updated successfully.',
                ]);
            } else {
                $data['created_at'] = now();
                DB::table('customer')->insert($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Customer added successfully.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(CustomerRequest $request)
    {
        try {
            $id = DB::table('customer')->insertGetId([
                'customer_type' => 'CUSTOMER',
                'tin_no' => $request->tin_no,
                'unique_id' => $this->generateUniqueId(),
                'registration_name' => $request->registration_name,
                'identification_no' => $request->identification_no,
                'identification_type' => $request->identification_type,
                'sst_registration' => $request->sst_registration,
                'phone' => $request->phone,
                'email' => $request->email,
                'city_name' => $request->city_name,
                'postal_zone' => $request->postal_zone,
                'country_subentity_code' => $request->country_subentity_code,
                'country_code' => 'MYS',
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'address_line_3' => $request->address_line_3,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return redirect()
                ->action([InvoiceController::class, 'presubmit'], ['id' => $id])
                ->with('success', 'Customer registered successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error creating customer: ' . $e->getMessage());
        }
    }

    public function public_store(CustomerRequest $request)
    {
        try {
            $id = DB::table('customer')->insertGetId([
                'customer_type' => 'SUPPLIER',
                'subscribe_for' => $request->subscribe_for,
                'tin_no' => $request->tin_no,
                'unique_id'=>$this->generateUniqueId(),
                'registration_name' => $request->registration_name,
                'identification_no' => $request->identification_no,
                'identification_type' => $request->identification_type,
                'sst_registration' => $request->sst_registration,
                'phone' => $request->phone,
                'email' => $request->email,
                'city_name' => $request->city_name,
                'postal_zone' => $request->postal_zone,
                'country_subentity_code' => $request->country_subentity_code,
                'country_code' => 'MYS',
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'address_line_3' => $request->address_line_3,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating customer',
                'error' => $e->getMessage()
            ], 500);
        }
  
        exit();
    }

    public function update(CustomerRequest $request, $id)
    {
        try {
            $customer = DB::table('customer')->where('id_customer', $id)->first();
            
            if (!$customer) {
                return response()->json(['message' => 'Customer not found'], 404);
            }

            DB::table('customer')
                ->where('id_customer', $id)
                ->update([
                    'customer_type' => $request->customer_type,
                    'tin_no' => $request->tin_no,
                    'registration_name' => $request->registration_name,
                    'identification_no' => $request->identification_no,
                    'identification_type' => $request->identification_type,
                    'sst_registration' => $request->sst_registration,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'city_name' => $request->city_name,
                    'postal_zone' => $request->postal_zone,
                    'country_subentity_code' => $request->country_subentity_code,
                    'country_code' => $request->country_code,
                    'address_line_1' => $request->address_line_1,
                    'address_line_2' => $request->address_line_2,
                    'address_line_3' => $request->address_line_3,
                    'updated_at' => now()
                ]);

            return response()->json(['message' => 'Customer updated successfully']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $customer = DB::table('customer')->where('id_customer', $id)->first();

            if (!$customer) {
                return redirect()->back()->with('error', 'Customer not found.');
            }

            DB::table('customer')->where('id_customer', $id)->update([
                'deleted' => 1,
                'updated_at' => now()
            ]);

            return redirect()->back()->with('success', 'Customer deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting customer: ' . $e->getMessage());
        }
    }
}