<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;


class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        // Validate the form data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'address' => 'nullable|string',
            'profile_pic' => 'nullable|image|max:2048', // Validate the profile picture file
        ]);

        // Handle the profile picture upload
        if ($request->hasFile('profile_pic')) {
            $profilePicPath = $request->file('profile_pic')->store('public/profile_pics');
            $validatedData['profile_pic'] = basename($profilePicPath);
        }

        // Update the user's profile information
        $user->update($validatedData);

        // Redirect or return a response
        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }
    
}