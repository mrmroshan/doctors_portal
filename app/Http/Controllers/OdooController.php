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
        
        // Check if result contains uid
        if (isset($result['uid'])) {
            return response()->json([
                'status' => 'success',
                'message' => 'Authentication successful',
                'uid' => $result['uid']
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication failed - No UID returned'
            ], 401);
        }
    } catch (\Exception $e) {
        // Log the error for debugging
        \Log::error('Odoo Authentication Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        // Check if the exception is related to access denied
        if (strpos($e->getMessage(), 'Access Denied') !== false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials, please check your configuration.'
            ], 401);
        }

        // Handle other exceptions
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage(),
            'debug' => config('app.debug') ? $e->getTraceAsString() : null
        ], 500);
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