<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(10);
        return view('admin.users.index', compact('users'));
    }





    public function create()
    {
        return view('admin.users.create');
    }





 // app/Http/Controllers/UserController.php

public function store(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'role' => 'required|in:doctor,admin',
    ]);

    // Get the odoo_doctor_id from the request
    $odooDoctorId = $request->input('odoo_doctor_id');

    // Check if the role is 'doctor'
    $isDoctor = $request->input('role') === 'doctor';

    // Log the values for debugging
    \Log::info('Role: ' . $request->input('role'));
    \Log::info('Odoo Doctor ID: ' . $odooDoctorId);
    \Log::info('Is Doctor: ' . ($isDoctor ? 'true' : 'false'));

    // Create the user with the validated data
    $user = User::create([
        'name' => $validatedData['name'],
        'email' => $validatedData['email'],
        'password' => Hash::make($validatedData['password']),
        'role' => $validatedData['role'],
        // Assign the odoo_doctor_id if the role is 'doctor'
        'odoo_doctor_id' => $isDoctor ? $odooDoctorId : null,
    ]);

    // Redirect or return a response
    return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
}





    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }





    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }





    // app/Http/Controllers/UserController.php

public function update(Request $request, User $user)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        'role' => 'required|in:doctor,admin',
        'odoo_doctor_id' => $request->role === 'doctor' ? 'required|string' : '', // Add validation for odoo_doctor_id
    ]);

    if ($request->filled('password')) {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);
        $validated['password'] = Hash::make($request->password);
    }

    // Update the odoo_doctor_id based on the role
    $validated['odoo_doctor_id'] = $request->role === 'doctor' ? $request->odoo_doctor_id : null;

    $user->update($validated);

    return redirect()->route('admin.users.index')
                    ->with('status', 'User updated successfully!');
}



    
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')
                        ->with('status', 'User deleted successfully!');
    }
}