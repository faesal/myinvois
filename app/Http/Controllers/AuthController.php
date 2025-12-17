<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Make sure to import DB facade
class AuthController extends Controller
{
    public function showLoginForm()
    {

       
        return view('auth.login');
    }

    public function profile()
    {
        $customer = null;

        $customer = DB::table('customer')->where('id_customer', session('id_supplier'))->first();
        
    
       
        return view('auth.profile', ['customer' => $customer]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);



        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // ===== STORE CUSTOMER RELATED SESSION FOR NON-DEVELOPERS =====
            if ($user->role !== 'developer') {
                $customer = \DB::table('customer')
                    ->where('user_id', $user->id)
                    ->whereNull('deleted')
                    ->first();

                if ($customer) {
                    session([
                        'user_id' => $user->id,
                        'connection_integrate' => $customer->connection_integrate,
                        'id_supplier' => $customer->id_customer
                    ]);
                }
            }

            // ===== ROLE-BASED REDIRECT =====
            if ($user->role === 'developer') {

                $conn = \DB::table('connection_integrate')
                ->where('user_id', $user->id)
                ->first();

            if ($conn) {
                session([
                    'user_id' => $user->id,
                    'connection_integrate' => $conn->code
                ]);
            }
            

                return redirect()->route('developer.dashboard');
            }

            return redirect()->intended('/main');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ]);
    }


    public function subscriberLogin($uuid)
{
    // 1. Make sure currently logged-in user is a DEVELOPER
    $developer = Auth::user();
    if (!$developer || $developer->role !== 'developer') {
        abort(403, 'Only developers can access subscriber accounts.');
    }

    // 2. Find the subscriber user by UUID
    $subscriber = \App\Models\User::where('uuid', $uuid)->first();
    if (!$subscriber) {
        abort(404, 'Subscriber not found.');
    }

    // 3. Ensure the target user is a SUBSCRIBER
    if ($subscriber->role !== 'subscriber') {
        abort(403, 'This UUID does not belong to a subscriber.');
    }

    // 4. Ensure that this subscriber belongs to THIS DEVELOPER
    $customer = DB::table('customer')
        ->where('email', $subscriber->email)
        ->where('id_developer', $developer->id)
        ->whereNull('deleted')
        ->first();

    if (!$customer) {
        abort(403, 'This subscriber is not under your developer account.');
    }

    // 5. Switch login: developer → subscriber
    Auth::logout();
    Auth::login($subscriber);

    // 6. Set subscriber session (same logic used during normal subscriber login)
    session([
        'user_id' => $subscriber->id,
        'connection_integrate' => $customer->connection_integrate,
        'id_supplier' => $customer->id_customer
    ]);

    // 7. Redirect subscriber to their dashboard
    return redirect('/main');
}



    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}

?>