<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\WelcomeDeveloper; 
use Illuminate\Support\Str;

class ManageDeveloperController extends Controller
{
    /**
     * Display the list of developers with statistics and filtering.
     * Logic: Now filters by is_deleted = 0.
     */
    public function index(Request $request)
    {
        // 1. Base Query: Only show developers where is_deleted is 0
        $query = DB::table('users')
            ->where('role', 'developer')
            ->where('is_deleted', 0);

        // 2. Calculate Statistics (Based on non-deleted records)
        $totalDevelopers = (clone $query)->count();
        $activeDevelopers = (clone $query)->where('is_active', 1)->count();
        $inactiveDevelopers = $totalDevelopers - $activeDevelopers;

        // 3. Apply Search Filter
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%");
            });
        }

        // 4. Paginate Results
        $developers = $query->orderBy('created_at', 'desc')->paginate(10);

        // 5. Data Transformation
        $developers->getCollection()->transform(function($dev) {
            $dev->role_title = 'Developer'; 
            $dev->status = (int)$dev->is_active; 
            return $dev;
        });

        return view('admin.developer', compact(
            'developers', 
            'totalDevelopers', 
            'activeDevelopers', 
            'inactiveDevelopers'
        ));
    }

    /**
     * Requirement: Store New Developer + Confirm Password + Auto Email
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed', 
        ]);

        try {
            DB::beginTransaction();

            $userId = DB::table('users')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'developer',
                'is_active' => 1,
                'is_deleted' => 0, // Explicitly set to 0 for new records
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Auto send welcome email
            Mail::to($request->email)
                ->cc('faesal@xideasoft.com')
                ->send(new WelcomeDeveloper($request->name, $request->email, $request->password));

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Developer created successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store Developer Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Requirement: Update status logic (Direct users.is_active Update)
     */
    public function update(Request $request, $id)
    {
        try {
            if ($request->has('status')) {
                $statusValue = ($request->status === 'true' || $request->status === true) ? 1 : 0;

                DB::table('users')
                    ->where('id', $id)
                    ->update([
                        'is_active' => $statusValue,
                        'updated_at' => now()
                    ]);

                return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
            }
            return response()->json(['success' => false, 'message' => 'No status data received.'], 400);
        } catch (\Exception $e) {
            Log::error("Status Update Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Requirement: Soft Delete Function
     * Logic: Sets is_deleted = 1 and is_active = 0
     */
    public function destroy($id)
    {
        try {
            $user = DB::table('users')->where('id', $id)->first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Developer not found.'], 404);
            }

            // Perform Soft Delete
            DB::table('users')
                ->where('id', $id)
                ->update([
                    'is_deleted' => 1,
                    'is_active' => 0, // Deactivate access on delete
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true, 
                'message' => 'Developer "' . $user->name . '" deleted successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error("Soft Delete Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Action: Send Password Reset Link
     */
    public function sendPasswordReset($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) return back()->with('error', 'Developer not found.');

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        return $status == Password::RESET_LINK_SENT
            ? back()->with('success', 'Reset link sent to ' . $user->email)
            : back()->with('error', __($status));
    }

    /**
     * Action: Resend Credentials
     */
    public function resendVerification($id)
    {
        try {
            $user = DB::table('users')->where('id', $id)->first();
            if (!$user) return back()->with('error', 'User not found.');

            Mail::to($user->email)
                ->cc('faesal@xideasoft.com')
                ->send(new WelcomeDeveloper($user->name, $user->email, 'Please use Reset Password to set a new one.'));

            return back()->with('success', 'Credentials resent to ' . $user->email);
        } catch (\Exception $e) {
            Log::error("Resend Error: " . $e->getMessage());
            return back()->with('error', 'Failed to resend: ' . $e->getMessage());
        }
    }

    /**
     * Action: Impersonate (Login As Developer)
     */
    public function impersonate($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        
        // Prevent impersonating deleted users or admins
        if (!$user || $user->is_deleted == 1 || $user->role === 'admin') {
            return back()->with('error', 'Unauthorized or user no longer exists.');
        }
        
        Auth::loginUsingId($user->id);
        return redirect()->route('developer.dashboard')->with('success', "Logged in as " . $user->name);
    }
}