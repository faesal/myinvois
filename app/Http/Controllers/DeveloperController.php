<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewDeveloperRegisteredAdmin;
use App\Mail\WelcomeDeveloper;

   // ===========================================
    // 2. Handle Developer Registration
    // ===========================================
    use Illuminate\Support\Str;
    
class DeveloperController extends Controller
{
    // ===========================================
    // 1. Show Registration Page
    // ===========================================
    public function showRegistrationForm()
    {
        return view('developer.register');
    }

 

public function register(Request $request)
{
    // Validate input
    $request->validate([
        'name'     => 'required|string|max:100',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|string|min:6|confirmed',
        'phone'    => 'nullable|string|max:50'
    ]);

    DB::beginTransaction();
    try {

        // -------------------------------------------
        // 1. INSERT USER (role = developer)
        // -------------------------------------------
        $developerId = DB::table('users')->insertGetId([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'phone'      => $request->phone,
            'role'       => 'developer',
            'created_at' => now(),
        ]);

        // -------------------------------------------
        // 2. GENERATE KEY & SECRET (16 CHAR)
        // -------------------------------------------
        $mysynctax_key    = Str::random(16);
        $mysynctax_secret = Str::random(16);

        // API token (optional, ikut DB awak)
        $api_token = Str::random(40);

        // -------------------------------------------
        // 3. INSERT connection_integrate
        // -------------------------------------------
        DB::table('connection_integrate')->insert([
            'user_id'          => $developerId,
            'code'             => 'DEV-' . strtoupper(Str::random(8)),
            'name'             => $request->name,
            'mysynctax_key'    => $mysynctax_key,
            'mysynctax_secret' => $mysynctax_secret,
            'api_token'        => $api_token,
            'created_at'       => now(),
        ]);

        DB::commit();

        // -------------------------------------------
        // 4. SEND EMAILS
        // -------------------------------------------
        $adminEmails = array('faesal09@gmail.com');

        foreach ($adminEmails as $admin) {
            Mail::to($admin)->send(new NewDeveloperRegisteredAdmin($request->name, $request->email));
        }

        Mail::to($request->email)->send(new WelcomeDeveloper($request->name, $request->email,$request->password));

        return redirect()->route('login')
                ->with('success_popup', 'Your developer account has been created! Please check your email.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Registration failed: ' . $e->getMessage());
    }
}

}
