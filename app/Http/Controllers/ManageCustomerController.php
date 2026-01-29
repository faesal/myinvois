<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class ManageCustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = DB::table('customer');

        // 1. Role-Based Account List (Dropdown Logic)
        $accountQuery = DB::table('connection_integrate')
            ->join('customer', 'connection_integrate.code', '=', 'customer.connection_integrate')
            ->where('customer.customer_type', '=', 'SUPPLIER')
            ->select(
                'customer.id_customer', 
                'connection_integrate.code', 
                'customer.registration_name as supplier_name', 
                'connection_integrate.name as connection_name'
            );

        if ($user->role === 'admin') {
            $lhdnAccounts = $accountQuery->get();
        } elseif ($user->role === 'developer') {
            $lhdnAccounts = $accountQuery->where('connection_integrate.id_developer', $user->id)->get();
        } else {
            $lhdnAccounts = [];
        }

        // 2. Base Data Filtering Logic
        if ($user->role !== 'admin' && $user->role !== 'developer') {
            $targetConnectionId = $request->get('connection_id', session('active_connection_id', 1));
            $connection = DB::table('connection_integrate')->where('id_connection', $targetConnectionId)->first();

            if ($connection) {
                $query->where('connection_integrate', $connection->code);
                session(['active_connection_id' => $targetConnectionId]);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // 3. Dropdown Filter
        if ($request->filled('lhdn_cust_id')) {
            $selectedAccount = DB::table('customer')
                ->where('id_customer', $request->lhdn_cust_id)
                ->first();
            
            if ($selectedAccount) {
                $query->where('connection_integrate', $selectedAccount->connection_integrate);
            }
        }

        // 4. Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('registration_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('tin_no', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->where('customer_type', 'CUSTOMER')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $layout = $this->getLayout($user->role);

        return view('customers.manage_customer', compact('customers', 'layout', 'lhdnAccounts'));
    }

    /**
     * Show create form.
     */
    public function create(Request $request) 
    { 
        $user = Auth::user();
        $layout = $this->getLayout($user->role);
        
        $selectedLhdnCode = null;
        $lhdnCustId = $request->get('lhdn_cust_id');

        if ($lhdnCustId) {
            $selectedLhdnCode = DB::table('customer')
                ->where('id_customer', $lhdnCustId)
                ->value('connection_integrate');
        }
        
        return view('customers.manage_create', compact('layout', 'selectedLhdnCode', 'lhdnCustId')); 
    }

    /**
     * Store customer.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'registration_name'   => 'required|string|max:255',
            'tin_no'              => 'required|string|max:50',
            'email'               => 'required|email|max:100',
            'identification_no'   => 'required|string',
            'identification_type' => 'required|string',
        ]);

        $connectionCode = $request->input('connection_integrate') ?? 
                          DB::table('connection_integrate')->where('id_connection', session('active_connection_id', 1))->value('code');

        if (!$connectionCode) {
            return redirect()->back()->with('error', 'No valid LHDN Account found.');
        }

        try {
            DB::table('customer')->insert([
                'registration_name'      => $request->registration_name,
                'tin_no'                 => $request->tin_no,
                'email'                  => $request->email,
                'phone'                  => $request->phone,
                'identification_no'      => $request->identification_no,
                'identification_type'    => $request->identification_type,
                'business_description'   => $request->business_description,
                'is_selfbill_supplier'   => $request->has('is_selfbill_supplier') ? 1 : 0,
                'sst_registration'       => $request->has('sst_registration') ? 1 : 0,
                'address_line_1'         => $request->address_line_1,
                'address_line_2'         => $request->address_line_2,
                'address_line_3'         => $request->address_line_3,
                'city_name'              => $request->city_name,
                'postal_zone'            => $request->postal_zone,
                'country_subentity_code' => $request->country_subentity_code,
                'country_code'           => 'MYS',
                'customer_type'          => 'CUSTOMER',
                'connection_integrate'   => $connectionCode, 
                'user_id'                => $user->id,
                'id_developer'           => ($user->role === 'developer') ? $user->id : null,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
            
            return redirect()->route('manage_customer.index', ['lhdn_cust_id' => $request->input('lhdn_cust_id')])
                             ->with('success', 'Customer saved successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $customer = DB::table('customer')->where('id_customer', $id)->first();
        $layout = $this->getLayout(Auth::user()->role);
        return view('customers.manage_edit', compact('customer', 'layout'));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::table('customer')->where('id_customer', $id)->update([
                'registration_name'    => $request->registration_name,
                'tin_no'               => $request->tin_no,
                'email'                => $request->email,
                'phone'                => $request->phone,
                'identification_no'    => $request->identification_no,
                'identification_type'  => $request->identification_type,
                'business_description' => $request->business_description,
                'is_selfbill_supplier' => $request->has('is_selfbill_supplier') ? 1 : 0,
                'sst_registration'     => $request->has('sst_registration') ? 1 : 0,
                'address_line_1'       => $request->address_line_1,
                'city_name'            => $request->city_name,
                'updated_at'           => now(),
            ]);
            return redirect()->route('manage_customer.index')->with('success', 'Customer updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Update Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::table('customer')->where('id_customer', $id)->delete();
        return redirect()->route('manage_customer.index')->with('success', 'Customer deleted successfully.');
    }

    public function export()
    {
        $customers = DB::table('customer')->where('customer_type', 'CUSTOMER')->get();
        $filename = "customers_export_" . now()->format('YmdHi') . ".csv";
        $handle = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        fputcsv($handle, ['Registration Name', 'TIN No', 'Email', 'Phone', 'ID No']);
        foreach ($customers as $row) { 
            fputcsv($handle, [$row->registration_name, $row->tin_no, $row->email, $row->phone, $row->identification_no]); 
        }
        fclose($handle);
        exit;
    }

    /**
     * IMPROVED IMPORT FUNCTION
     * Fixes "Silent Failure" by auto-detecting line endings
     * Fixes "Undefined Array Key" by stripping Excel BOM characters
     */
    public function import(Request $request)
    {
        ini_set('auto_detect_line_endings', true);

        $file = $request->file('csv_file');
        if (!$file) return back()->with('error', 'Please upload a CSV file.');

        // 1. Validate File Extension
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'csv' && $extension !== 'txt') {
            return back()->with('error', 'Invalid file type. Please save your Excel file as "CSV (Comma delimited)".');
        }

        // 2. Determine Connection Code
        $connectionCode = null;
        if ($request->filled('lhdn_cust_id')) {
            $connectionCode = DB::table('customer')->where('id_customer', $request->lhdn_cust_id)->value('connection_integrate');
        }
        if (!$connectionCode) {
            $connectionCode = $request->get('connection_integrate') ?? 
                              DB::table('connection_integrate')->where('id_connection', session('active_connection_id', 1))->value('code');
        }
        if (!$connectionCode) {
            return back()->with('error', 'Please select a Supplier Account from the dropdown first.');
        }

        // 3. Process CSV
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            
            // Check for "PK" (Excel/Zip signature)
            $firstLine = fgets($handle);
            if (substr($firstLine, 0, 2) === 'PK') {
                fclose($handle);
                return back()->with('error', 'This appears to be an Excel (.xlsx) file. Please Open Excel -> Save As -> CSV (Comma delimited).');
            }
            rewind($handle); // Go back to start

            // Detect Delimiter (Comma vs Semicolon)
            $delimiter = ',';
            if (strpos($firstLine, ';') !== false) {
                $delimiter = ';';
            }

            // Get Header
            $header = fgetcsv($handle, 0, $delimiter);

            // Clean Header
            if (isset($header[0])) {
                $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]); // Remove BOM
            }
            $header = array_map(function($h) { return strtolower(trim($h)); }, $header);

            if (!in_array('registration_name', $header)) {
                fclose($handle);
                return back()->with('error', "Invalid CSV Format. Header 'registration_name' not found. Found: " . implode(', ', $header));
            }

            $successCount = 0;

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                
                if (count($row) !== count($header)) continue;

                $rowData = array_combine($header, $row);
                $regName = trim($rowData['registration_name'] ?? '');
                $tinNo = trim($rowData['tin_no'] ?? '');

                if (empty($regName) || empty($tinNo)) continue;

                DB::table('customer')->insert([
                    'registration_name'      => $regName,
                    'tin_no'                 => $tinNo,
                    'email'                  => $rowData['email'] ?? null,
                    'phone'                  => $rowData['phone'] ?? null,
                    'identification_no'      => $rowData['identification_no'] ?? null,
                    'identification_type'    => $rowData['identification_type'] ?? 'NRIC',
                    'business_description'   => $rowData['business_description'] ?? null,
                    'is_selfbill_supplier'   => isset($rowData['is_selfbill_supplier']) && $rowData['is_selfbill_supplier'] != '' ? (int)$rowData['is_selfbill_supplier'] : 0,
                    'sst_registration'       => isset($rowData['sst_registration']) ? (string)$rowData['sst_registration'] : '0',
                    'address_line_1'         => $rowData['address_line_1'] ?? null,
                    'address_line_2'         => $rowData['address_line_2'] ?? null,
                    'address_line_3'         => $rowData['address_line_3'] ?? null,
                    'city_name'              => $rowData['city_name'] ?? null,
                    'postal_zone'            => $rowData['postal_zone'] ?? null,
                    'country_subentity_code' => $rowData['country_subentity_code'] ?? null,
                    'country_code'           => $rowData['country_code'] ?? 'MYS',
                    'customer_type'          => 'CUSTOMER',
                    'connection_integrate'   => $connectionCode,
                    'user_id'                => Auth::id(),
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
                $successCount++;
            }
            fclose($handle);
            return back()->with('success', "Success! Imported $successCount customers.");
        }
        
        return back()->with('error', 'Could not open file.');
    }

    private function getLayout($role) {
        if ($role === 'admin') return 'layouts.adminLayout';
        if ($role === 'developer') return 'layouts.developerLayout';
        return 'layouts.app';
    }

    /**
     * GENERATE TEMPLATE
     * Creates a .csv file with BOM for Excel compatibility
     */
    public function downloadTemplate()
    {
        $headers = [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=customer_import_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'registration_name', 
            'tin_no', 
            'identification_type', 
            'identification_no', 
            'email', 
            'phone', 
            'business_description', 
            'sst_registration',
            'is_selfbill_supplier',
            'address_line_1',
            'address_line_2',
            'address_line_3',
            'city_name',
            'postal_zone',
            'country_subentity_code',
            'country_code'
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 Compatibility
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, $columns);
            
            fputcsv($file, [
                'ABC Sdn Bhd', 
                'C1234567890', 
                'BRN', 
                '202401000001', 
                'email@example.com', 
                '0123456789', 
                'General Goods', 
                '1', 
                '0',
                'No 123 Jalan Test',
                'Taman Test',
                '',
                'Kuala Lumpur',
                '50000',
                '14',
                'MYS'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}