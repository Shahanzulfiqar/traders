<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{

    /**
     * Show profile edit page
     */
    public function edit()
    {
        $user = Auth::user();

        return view('profile.edit', ['user' => $user]);
    }

    /**
     * Update profile
     */
  public function update(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
    ]);

    $user = Auth::user();

    if (!$user) {
        abort(403);
    }

    // direct query update (no save error)
    \App\Models\User::where('id', $user->id)->update([
        'name' => $validated['name'],
        'email' => $validated['email'],
    ]);

    return back()->with('success', 'Profile updated successfully');
}
}
