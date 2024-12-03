<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\OdooApi;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    protected $odooApi;

    public function __construct(OdooApi $odooApi)
    {
        $this->middleware('auth');
        $this->odooApi = $odooApi;
    }

    public function index(Request $request)
    {
        $query = Patient::query();
        
        // Only show patients associated with the logged-in doctor
        if (auth()->user()->isDoctor()) {
            $query->whereHas('doctors', function($q) {
                $q->where('users.id', auth()->id());
            });
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'last_name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $patients = $query->paginate(25);
        return view('patients.index', compact('patients', 'sortField', 'sortDirection'));
    }

    // Add new API endpoint for patient creation
    public function apiStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'date_of_birth' => 'required|date',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email|unique:patients,email',
                'address' => 'nullable|string|max:500'
            ]);

            $patient = Patient::create($validated);
            $patient->doctors()->attach(auth()->id());

            // Load the fresh model with any relationships needed
            $patient = $patient->fresh();

            return response()->json([
                'success' => true,
                'patient' => [
                    'id' => $patient->id,
                    'full_name' => $patient->first_name . ' ' . $patient->last_name,
                    'phone' => $patient->phone,
                    'text' => $patient->first_name . ' ' . $patient->last_name . ' - ' . $patient->phone // Format exactly as in your blade template
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create patient:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create patient: ' . $e->getMessage()
            ], 500);
        }
    }
  
  
  
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'email' => 'required|email|unique:patients',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
        ]);

        $patient = Patient::create($validated);
        
        // Attach the creating doctor to the patient
        $patient->doctors()->attach(auth()->id());

        return redirect()->route('patients.index')
            ->with('success', 'Patient created successfully.');
    }

    public function edit(Patient $patient)
    {
        // Ensure the authenticated doctor has access to this patient
        if (auth()->user()->isDoctor() && !$patient->hasDoctor(auth()->user())) {
            abort(403, 'Unauthorized access to this patient.');
        }

        return view('patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        // First, ensure the authenticated doctor has access to this patient
        if (auth()->user()->isDoctor() && !$patient->hasDoctor(auth()->user())) {
            abort(403, 'Unauthorized access to this patient.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'email' => 'required|email|unique:patients,email,' . $patient->id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
        ]);

        $patient->update($validated);

        return redirect()->route('patients.index')
            ->with('success', 'Patient updated successfully.');
    }
}