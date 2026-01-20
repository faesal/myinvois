<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str; // Required for Unique ID generation
use App\Models\User;
use App\Mail\SubscriberExpiredMail;

class SubscriberController extends Controller
{
    /**
     * Display the list of subscribers with filtering and pagination.
     * Filtered to show ONLY Suppliers (Primary LHDN Accounts).
     */
    public function index(Request $request)
    {
        // 1. Fetch Developers for the Filter Dropdown
        $developers = User::where('role', 'developer')->select('id', 'name')->get();

        // 2. Build the Query
        $query = DB::table('customer')
            ->leftJoin('users as subscriber_user', 'customer.user_id', '=', 'subscriber_user.id')
            ->leftJoin('users as dev_user', 'customer.id_developer', '=', 'dev_user.id')
            ->leftJoin('connection_integrate', 'customer.user_id', '=', 'connection_integrate.user_id')
            
            // --- CRITICAL FILTER: Only show LHDN Accounts (Suppliers) ---
            ->where('customer.customer_type', '=', 'SUPPLIER')
            
            ->where(function($q) {
                $q->where('subscriber_user.role', '=', 'subscriber')
                  ->orWhereNull('subscriber_user.id'); 
            })
            
            ->whereNull('customer.deleted')
            ->select(
                'customer.id_customer',
                'customer.registration_name as lhdn_account_name',
                'customer.email',
                'customer.is_activation',       
                'customer.start_subscribe',     
                'customer.end_subscribe',       
                'customer.created_at as registered_date',
                'customer.unique_id',
                
                DB::raw('COALESCE(dev_user.name, "No Developer") as developer_name'),
                'customer.id_developer',
                
                'connection_integrate.mysynctax_key',    
                'connection_integrate.mysynctax_secret'  
            );

        // --- FILTER: Search ---
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('customer.registration_name', 'LIKE', "%{$term}%")
                  ->orWhere('customer.email', 'LIKE', "%{$term}%")
                  ->orWhere('customer.unique_id', 'LIKE', "%{$term}%");
            });
        }

        // --- FILTER: Developer ---
        if ($request->filled('developer_name')) {
            $query->where('customer.id_developer', $request->developer_name);
        }

        // 3. Paginate
        $subscribers = $query->orderBy('customer.id_customer', 'desc')->paginate(10);

        // 4. Transform for the view
        $subscribers->getCollection()->transform(function ($item) {
            return (object) [
                'id' => $item->id_customer,
                'developer_name' => $item->developer_name,
                'lhdn_account_name' => $item->lhdn_account_name ?? 'Unnamed Account',
                'email' => $item->email,
                'status' => (int)$item->is_activation === 1, 
                'date_start' => $item->start_subscribe,
                'date_end' => $item->end_subscribe,
                'registered_date' => $item->registered_date,
                'unique_id' => $item->unique_id ?? 'PENDING',
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

                    $userId = DB::table('customer')
                        ->where('id_customer', $id)
                        ->value('user_id');

                    if ($userId) {
                        DB::table('users')
                            ->where('id', $userId)
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
     * Impersonate Subscriber
     */
    public function impersonate($id)
    {
        $customer = DB::table('customer')->where('id_customer', $id)->first();

        if (!$customer) {
            return back()->with('error', 'Subscriber record not found.');
        }

        $user = DB::table('users')->where('id', $customer->user_id)->first();

        if (!$user || $user->role !== 'subscriber') {
             return back()->with('error', 'User is not a valid subscriber account.');
        }

        if (isset($user->is_deleted) && $user->is_deleted == 1) {
            return back()->with('error', 'User account has been deleted.');
        }

        Auth::loginUsingId($user->id);

        return redirect('/main')->with('success', "Logged in as " . $user->name);
    }

    /**
     * Manual Expiry Check & Email Report
     */
    public function manualCheckExpired()
    {
        $today = now()->toDateString();
        $targetEmail = 'fjusrin@gmail.com'; 

        $expiredSubscribers = DB::table('customer')
            ->whereDate('end_subscribe', '<', $today)
            ->where('is_activation', '1') 
            ->whereNull('deleted')
            ->get();

        if ($expiredSubscribers->isEmpty()) {
            return back()->with('error', 'No expired active subscribers found today.');
        }

        try {
            Mail::to($targetEmail)->send(new SubscriberExpiredMail($expiredSubscribers));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }

        return back()->with('success', "Report sent! Found " . $expiredSubscribers->count() . " expired subscribers.");
    }

    /**
     * Show Activation Form
     */
    public function activationForm($id)
    {
        $subscriber = DB::table('customer')
            ->leftJoin('users as dev_user', 'customer.id_developer', '=', 'dev_user.id')
            ->leftJoin('users as sub_user', 'customer.user_id', '=', 'sub_user.id')
            ->where('customer.id_customer', $id)
            ->select(
                'customer.id_customer',
                'customer.start_subscribe',
                'customer.end_subscribe',
                'dev_user.name as developer_name',
                'sub_user.name as subscriber_name',
                'customer.registration_name'
            )
            ->first();

        if (!$subscriber) {
            return redirect()->route('admin.subscribers.index')->with('error', 'Subscriber not found.');
        }

        $startDate = now()->format('Y-m-d');
        $endDate = now()->addYear()->format('Y-m-d');

        return view('admin.activate_subscriber', compact('subscriber', 'startDate', 'endDate'));
    }

    /**
     * Process Activation Form Submission
     * NEW: Generates 12-char Unique ID if not already set.
     */
    public function activateSubscriber(Request $request, $id)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                
                $customer = DB::table('customer')->where('id_customer', $id)->first();
                if (!$customer) throw new \Exception("Customer not found.");

                // 1. Generate Unique ID if empty
                $uniqueId = $customer->unique_id;
                if (empty($uniqueId)) {
                    do {
                        $uniqueId = strtoupper(Str::random(12)); 
                    } while (DB::table('customer')->where('unique_id', $uniqueId)->exists());
                }

                // 2. Update Customer Table
                DB::table('customer')
                    ->where('id_customer', $id)
                    ->update([
                        'unique_id'       => $uniqueId,
                        'start_subscribe' => $request->start_date,
                        'end_subscribe'   => $request->end_date,
                        'is_activation'   => '1', 
                        'updated_at'      => now(),
                    ]);

                // 3. Update User Login Access
                if ($customer->user_id) {
                    DB::table('users')->where('id', $customer->user_id)
                        ->update([
                            'is_active' => 1, 
                            'updated_at' => now()
                        ]);
                }
            });

            return redirect()->route('admin.subscribers.index')
                ->with('success', 'Subscriber activated and Unique ID generated successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}