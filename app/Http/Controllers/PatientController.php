<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\OdooApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
    
    // Only filter by doctor if the user is a doctor
    if (auth()->user()->isDoctor()) {
        $query->whereHas('doctors', function($q) {
            $q->where('users.id', auth()->id());
        });
    }

    // Search functionality
    if ($request->filled('search')) {
        $search = $request->get('search');
        $searchTerms = explode(' ', $search); // Split search into terms

        $query->where(function($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->orWhere('first_name', 'like', "%{$term}%")
                  ->orWhere('last_name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            }
        });

        // Add debug logging
        \Log::info('Search query:', [
            'terms' => $searchTerms,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);
    }

    // Sorting
    $sortField = $request->get('sort', 'last_name');
    $sortDirection = $request->get('direction', 'asc');
    $query->orderBy($sortField, $sortDirection);

    $patients = $query->paginate(25)->withQueryString();

    // Debug: Log the results
    \Log::info('Query results:', [
        'count' => $patients->count(),
        'total' => $patients->total(),
        'items' => $patients->items()
    ]);

    return view('patients.index', compact('patients', 'sortField', 'sortDirection'));
}






    public function create()
    {
        // Check if the user is authorized to create patients
        if (!auth()->user()->isDoctor() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        return view('patients.create');
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
        if (auth()->user()->isDoctor()) {
            $patient->doctors()->attach(auth()->id());
        }

        return redirect()->route('patients.index')
            ->with('success', 'Patient created successfully.');
    }






    public function show(Patient $patient)
    {
        try {
            // Fetch medications from Odoo with caching
            $odooMedications = Cache::remember('odoo_medications', 600, function () {
                return $this->odooApi->getMedicationList();
            });
            
            // Convert to a lookup array with product ID as key
            $medicationsLookup = collect($odooMedications)->keyBy('id')->all();

            return view('patients.show', compact('patient', 'medicationsLookup'));

        } catch (\Exception $e) {
            Log::error('Error fetching Odoo medications: ' . $e->getMessage());
            return view('patients.show', [
                'patient' => $patient,
                'medicationsLookup' => []
            ])->with('warning', 'Unable to fetch medication details from Odoo.');
        }
    }





    public function edit(Patient $patient)
    {
        // Only check doctor authorization if user is a doctor
        // Admins can access any patient
        if (auth()->user()->isDoctor() && !$patient->hasDoctor(auth()->user())) {
            abort(403, 'Unauthorized access to this patient.');
        }

        return view('patients.edit', compact('patient'));
    }






    public function update(Request $request, Patient $patient)
    {
        // Only check doctor authorization if user is a doctor
        // Admins can update any patient
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




    public function destroy(Patient $patient)
    {
        // Only check doctor authorization if user is a doctor
        // Admins can delete any patient
        if (auth()->user()->isDoctor() && !$patient->hasDoctor(auth()->user())) {
            abort(403, 'Unauthorized access to this patient.');
        }

        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', 'Patient deleted successfully.');
    }





    public function search(Request $request)
    {
        $query = Patient::query();
        
        // Only show patients associated with the logged-in doctor
        if (auth()->user()->isDoctor()) {
            $query->whereHas('doctors', function($q) {
                $q->where('users.id', auth()->id());
            });
        }

        $search = $request->get('q');
        $patients = $query->where(function($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        })
        ->limit(10)
        ->get()
        ->map(function($patient) {
            return [
                'id' => $patient->id,
                'text' => $patient->first_name . ' ' . $patient->last_name . ' - ' . $patient->phone
            ];
        });

        return response()->json($patients);
    }




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
            
            if (auth()->user()->isDoctor()) {
                $patient->doctors()->attach(auth()->id());
            }

            // Load the fresh model with any relationships needed
            $patient = $patient->fresh();

            return response()->json([
                'success' => true,
                'patient' => [
                    'id' => $patient->id,
                    'full_name' => $patient->first_name . ' ' . $patient->last_name,
                    'phone' => $patient->phone,
                    'text' => $patient->first_name . ' ' . $patient->last_name . ' - ' . $patient->phone
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
}