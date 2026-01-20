<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // Added Auth Facade
use App\Models\User;

class SubscriberController extends Controller
{
    /**
     * Display the list of subscribers with filtering and pagination.
     */
    public function index(Request $request)
    {
        // 1. Fetch Developers for the Filter Dropdown
        $developers = User::where('role', 'developer')->select('id', 'name')->get();

        // 2. Build the Query
        $query = DB::table('customer')
            ->join('users as subscriber_user', 'customer.user_id', '=', 'subscriber_user.id')
            ->leftJoin('users as dev_user', 'customer.id_developer', '=', 'dev_user.id')
            ->leftJoin('connection_integrate', 'customer.user_id', '=', 'connection_integrate.user_id')
            ->where('subscriber_user.role', '=', 'subscriber')
            ->whereNull('customer.deleted')
            ->select(
                'customer.id_customer',
                'customer.registration_name as lhdn_account_name',
                'customer.email',
                'customer.is_activation',       
                'customer.start_subscribe',     
                'customer.end_subscribe',       
                'customer.created_at as registered_date',
                'dev_user.name as developer_name', 
                'customer.id_developer',
                'connection_integrate.mysynctax_key',    
                'connection_integrate.mysynctax_secret'  
            );

        // --- FILTER: Search ---
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('customer.registration_name', 'LIKE', "%{$term}%")
                  ->orWhere('customer.email', 'LIKE', "%{$term}%");
            });
        }

        // --- FILTER: Developer ---
        if ($request->filled('developer_name')) {
            $query->where('customer.id_developer', $request->developer_name);
        }

        // 3. Paginate
        $subscribers = $query->orderBy('customer.id_customer', 'desc')->paginate(10);

        // 4. Transform
        $subscribers->getCollection()->transform(function ($item) {
            return (object) [
                'id' => $item->id_customer,
                'developer_name' => $item->developer_name ?? 'N/A',
                'lhdn_account_name' => $item->lhdn_account_name ?? 'Unnamed Account',
                'email' => $item->email,
                'status' => (int)$item->is_activation === 1, 
                'date_start' => $item->start_subscribe,
                'date_end' => $item->end_subscribe,
                'registered_date' => $item->registered_date,
                'mysynctax_key' => $item->mysynctax_key ?? 'N/A',
                'mysynctax_secret' => $item->mysynctax_secret ?? 'N/A',
            ];
        });

        return view('admin.subscriber', compact('subscribers', 'developers'));
    }

    /**
     * Update Date or Status
     */
    public function update(Request $request, $id)
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $updateData = [];

                if ($request->has('date_start')) {
                    $updateData['start_subscribe'] = $request->date_start;
                }
                if ($request->has('date_end')) {
                    $updateData['end_subscribe'] = $request->date_end;
                }

                if ($request->has('status')) {
                    $statusValue = ($request->status == 'true') ? '1' : '0';
                    $updateData['is_activation'] = $statusValue;

                    $customerId = DB::table('customer')
                        ->where('id_customer', $id)
                        ->value('user_id');

                    if ($customerId) {
                        DB::table('users')
                            ->where('id', $customerId)
                            ->update([
                                'is_active' => (int)$statusValue,
                                'updated_at' => now()
                            ]);
                    }
                }

                if (!empty($updateData)) {
                    DB::table('customer')
                        ->where('id_customer', $id)
                        ->update($updateData);
                    
                    return response()->json(['success' => true, 'message' => 'Subscriber status updated!']);
                }

                return response()->json(['success' => false, 'message' => 'No data to update.']);
            });

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * NEW: Impersonate Subscriber
     * Logic: Finds the User ID linked to the Customer ID and logs them in.
     */
   /**
     * Impersonate Subscriber
     * Logic: Finds the User ID linked to the Customer ID, logs them in, and redirects to /main.
     */
    public function impersonate($id)
    {
        // 1. Get the customer record to find the linked user_id
        $customer = DB::table('customer')->where('id_customer', $id)->first();

        if (!$customer) {
            return back()->with('error', 'Subscriber record not found.');
        }

        // 2. Find the User
        $user = DB::table('users')->where('id', $customer->user_id)->first();

        // 3. Validation
        if (!$user || $user->role !== 'subscriber') {
             return back()->with('error', 'User is not a valid subscriber account.');
        }

        // Check soft delete if column exists
        if (isset($user->is_deleted) && $user->is_deleted == 1) {
            return back()->with('error', 'User account has been deleted.');
        }

        // 4. Log in
        Auth::loginUsingId($user->id);

        // 5. Redirect to '/main'
        return redirect('/main')->with('success', "Logged in as " . $user->name);
    }
    }
