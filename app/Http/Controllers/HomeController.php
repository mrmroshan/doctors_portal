<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Prescription;

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
                  // Fetch doctor-specific data here
            // Fetch doctor-specific data here
        // Fetch doctor-specific data here
        $patientCount = auth()->user()->patients()->count();
        $todayPrescriptions = auth()->user()->prescriptions()->whereDate('created_at', today())->count();
        $recentPrescriptions = auth()->user()->prescriptions()->with('patient')->latest()->take(10)->get();
        $recentPatients = auth()->user()->patients()->with('prescriptions')->latest()->take(10)->get();

        return view('doctor.dashboard', [
            'patientCount' => $patientCount,
            'todayPrescriptions' => $todayPrescriptions,
            'recentPrescriptions' => $recentPrescriptions,
            'recentPatients' => $recentPatients,
        ]);
    }
}