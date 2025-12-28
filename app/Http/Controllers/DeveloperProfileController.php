<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class DeveloperProfileController extends Controller
{
    public function edit()
    {
        return view('developer.profile.edit', [
            'user' => auth()->user()
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:100|unique:users,email,' . $user->id,
            'phone'    => 'required|digits_between:9,15',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'];

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        // ✅ Redirect to dashboard after update
        return redirect()
            ->route('developer.dashboard')
            ->with('success', 'Profile updated successfully.');
    }

}
