<?php

namespace App\Http\Controllers;

use App\Services\OdooApi;
use Illuminate\Http\Request;

class OdooController extends Controller
{
    public function welcome()
    {
        return view('template');
    }

    public function authenticateUser()
    {
        try {
            // Initialize Odoo API with pre-configured credentials
            $odoo = new OdooApi();
            
            // Attempt to authenticate
            $result = $odoo->authenticate();
            
            if ($result) {
                return response()->json(['message' => 'Authentication successful', 'uid' => $result->uid]);
            } else {
                return response()->json(['message' => 'Authentication failed'], 401);
            }
        } catch (\Exception $e) {
            // Check if the exception is related to access denied
            if (strpos($e->getMessage(), 'Access Denied') !== false) {
                return response()->json(['message' => 'Invalid credentials, please check your configuration.'], 401);
            }
            // Handle other exceptions
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function fetchUsers()
    {
        try {
            // Initialize Odoo API
            $odoo = new OdooApi();

            // Fetch users
            $users = $odoo->search_read('res.users', [], ['id', 'name', 'login']);
            dd($users);
            return view('users.index', compact('users'));
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}