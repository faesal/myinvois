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

        // 1. Role-Based Account List (For Dropdown)
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

        // 2. Base Data Filtering (SECURITY FIX: Restrict Developer visibility)
        if ($user->role === 'developer') {
            // Get all connection codes belonging to this developer
            $developerConnections = DB::table('connection_integrate')
                ->where('id_developer', $user->id)
                ->pluck('code');
            
            // Restrict query to only their own connections
            $query->whereIn('connection_integrate', $developerConnections);
            
        } elseif ($user->role !== 'admin') {
            // Subscriber or standard user
            $targetConnectionId = $request->get('connection_id', session('active_connection_id', 1));
            $connection = DB::table('connection_integrate')->where('id_connection', $targetConnectionId)->first();

            if ($connection) {
                $query->where('connection_integrate', $connection->code);
                session(['active_connection_id' => $targetConnectionId]);
            } else {
                $query->whereRaw('1 = 0'); // Block access
            }
        }

        // 3. Dropdown Filter (Specific LHDN Account)
        if ($request->filled('lhdn_cust_id')) {
            $selectedAccount = DB::table('customer')->where('id_customer', $request->lhdn_cust_id)->first();
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
     * EXPORT DATA
     * Handles specific selected IDs or all customers in the current filtered view.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $ids = $request->input('selected_ids');
        
        $query = DB::table('customer')->where('customer_type', 'CUSTOMER');

        // SECURITY FIX: Apply the same role-based restrictions to Export!
        if ($user->role === 'developer') {
            $developerConnections = DB::table('connection_integrate')->where('id_developer', $user->id)->pluck('code');
            $query->whereIn('connection_integrate', $developerConnections);
        } elseif ($user->role !== 'admin') {
            $targetConnectionId = session('active_connection_id', 1);
            $connection = DB::table('connection_integrate')->where('id_connection', $targetConnectionId)->first();
            if ($connection) {
                $query->where('connection_integrate', $connection->code);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // SCENARIO 1: User checked specific boxes (Export ONLY those)
        if (!empty($ids)) {
            $query->whereIn('id_customer', $ids);
        } 
        // SCENARIO 2: No boxes checked (Export ALL matching filters, no pagination)
        else {
            // Filter by LHDN Account dropdown
            if ($request->filled('lhdn_cust_id')) {
                $query->where('connection_integrate', $request->input('lhdn_cust_id'));
            }

            // Filter by Search bar
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('registration_name', 'like', "%{$search}%")
                      ->orWhere('tin_no', 'like', "%{$search}%")
                      ->orWhere('identification_no', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
        }

        // Execute the query to grab all matching records
        $customers = $query->get();

        $filename = "customers_export_" . now()->format('YmdHi') . ".csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Self Bill', 'Description', 'TIN No', 'Registration Name', 
            'ID No', 'SST Registration No', 'Complete Address', 'Phone Number', 'Email'
        ];

        // LHDN State Code Mapping
        $states = [
            '01' => 'Johor',
            '02' => 'Kedah',
            '03' => 'Kelantan',
            '04' => 'Melaka',
            '05' => 'Negeri Sembilan',
            '06' => 'Pahang',
            '07' => 'Pulau Pinang',
            '08' => 'Perak',
            '09' => 'Perlis',
            '10' => 'Selangor',
            '11' => 'Terengganu',
            '12' => 'Sabah',
            '13' => 'Sarawak',
            '14' => 'W.P. Kuala Lumpur',
            '15' => 'W.P. Labuan',
            '16' => 'W.P. Putrajaya',
            '17' => 'Not Applicable'
        ];

        $callback = function() use($customers, $columns, $states) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($file, $columns);

            foreach ($customers as $row) {
                // Determine State Name
                $stateCodeRaw = $row->country_subentity_code;
                $formattedStateCode = str_pad($stateCodeRaw, 2, '0', STR_PAD_LEFT);
                $stateName = $states[$formattedStateCode] ?? $stateCodeRaw;

                // Build complete address with State Name instead of code
                $addressParts = array_filter([
                    $row->address_line_1,
                    $row->address_line_2,
                    $row->address_line_3,
                    $row->city_name,
                    $row->postal_zone,
                    $stateName,
                    $row->country_code
                ]);
                $completeAddress = implode(', ', $addressParts);

                // Handle SST value (Fallback for older boolean data)
                $sstDisplay = '-';
                if (!empty($row->sst_registration) && $row->sst_registration !== '0') {
                    $sstDisplay = $row->sst_registration === '1' ? 'Yes' : $row->sst_registration;
                }

                fputcsv($file, [
                    $row->is_selfbill_supplier == 1 ? 'Yes' : 'No',
                    $row->business_description ?? '-',
                    $row->tin_no,
                    $row->registration_name,
                    $row->identification_no,
                    $sstDisplay,
                    $completeAddress,
                    $row->phone,
                    $row->email,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    public function create(Request $request) 
    { 
        $user = Auth::user();
        $layout = $this->getLayout($user->role);
        $selectedLhdnCode = null;
        $lhdnCustId = $request->get('lhdn_cust_id');

        if ($lhdnCustId) {
            $selectedLhdnCode = DB::table('customer')->where('id_customer', $lhdnCustId)->value('connection_integrate');
        }
        
        return view('customers.manage_create', compact('layout', 'selectedLhdnCode', 'lhdnCustId')); 
    }

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
                // Make sure to accept the string from standard form submissions as well if you updated that form too
                'sst_registration'       => $request->sst_registration ?? '0', 
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
                'sst_registration'     => $request->sst_registration ?? '0',
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

    // NEW: Bulk Delete Function
    public function bulkDelete(\Illuminate\Http\Request $request)
    {
        $ids = $request->input('ids');
        
        if (!empty($ids)) {
            DB::table('customer')->whereIn('id_customer', $ids)->delete();
            return redirect()->route('manage_customer.index')->with('success', count($ids) . ' Customers deleted successfully.');
        }

        return redirect()->route('manage_customer.index')->with('error', 'No customers selected for deletion.');
    }

    public function import(Request $request)
    {
        ini_set('auto_detect_line_endings', true);
        $file = $request->file('csv_file');
        if (!$file) return back()->with('error', 'Please upload a CSV file.');

        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'csv' && $extension !== 'txt') {
            return back()->with('error', 'Invalid file type.');
        }

        // Get from modal dropdown first, then fallback
        $connectionCode = $request->input('import_connection_code');
        
        if (!$connectionCode) {
            $connectionCode = $request->get('connection_integrate') ?? 
                              DB::table('connection_integrate')->where('id_connection', session('active_connection_id', 1))->value('code');
        }

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
            $header = fgetcsv($handle, 0, $delimiter);

            if (isset($header[0])) {
                $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
            }
            $header = array_map(function($h) { return strtolower(trim($h)); }, $header);

            $successCount = 0;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count($row) !== count($header)) continue;
                $rowData = array_combine($header, $row);

                DB::table('customer')->insert([
                    'registration_name'      => trim($rowData['registration_name'] ?? ''),
                    'tin_no'                 => trim($rowData['tin_no'] ?? ''),
                    'email'                  => $rowData['email'] ?? null,
                    'phone'                  => $rowData['phone'] ?? null,
                    'identification_no'      => $rowData['identification_no'] ?? null,
                    'identification_type'    => $rowData['identification_type'] ?? 'NRIC',
                    'business_description'   => $rowData['business_description'] ?? null,
                    'is_selfbill_supplier'   => (int)($rowData['is_selfbill_supplier'] ?? 0),
                    // Removed the int cast so it accepts the string SST number
                    'sst_registration'       => trim($rowData['sst_registration'] ?? '0'), 
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

    public function downloadTemplate()
    {
        $headers = [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=customer_import_template.csv",
        ];

        $columns = [
            'registration_name', 'tin_no', 'identification_type', 'identification_no', 
            'email', 'phone', 'business_description', 'sst_registration',
            'is_selfbill_supplier', 'address_line_1', 'address_line_2', 'address_line_3',
            'city_name', 'postal_zone', 'country_subentity_code', 'country_code'
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);
            // Modified sample data to show a string for SST instead of '1'
            fputcsv($file, [
                'ABC Sdn Bhd', 'C1234567890', 'BRN', '202401000001', 
                'email@example.com', '0123456789', 'General Goods', 'W10-2401-32000111', '0',
                'No 123 Jalan Test', 'Taman Test', '', 'Kuala Lumpur', '50000', '14', 'MYS'
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getLayout($role) {
        if ($role === 'admin') return 'layouts.adminLayout';
        if ($role === 'developer') return 'layouts.developerLayout';
        return 'layouts.app';
    }
}