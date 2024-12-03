<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Redirect to appropriate dashboard based on user role.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        
        return redirect()->route('doctor.dashboard');
    }

    /**
     * Display admin dashboard
     *
     * @return \Illuminate\View\View
     */
    public function adminDashboard(): View
    {
        // Add admin-specific data here
        return view('admin.dashboard');
    }

    /**
     * Display doctor dashboard
     *
     * @return \Illuminate\View\View
     */
    public function doctorDashboard(): View
    {
        // Add doctor-specific data here
        return view('doctor.dashboard');
    }
}