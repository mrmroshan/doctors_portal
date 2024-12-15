<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\OdooApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PrescriptionService;  // Add this import

class PrescriptionController extends Controller
{
    protected $prescriptionService;
    protected $odooApi;

    public function __construct(PrescriptionService $prescriptionService, OdooApi $odooApi)
    {
        $this->prescriptionService = $prescriptionService;
        $this->odooApi = $odooApi;
    }

    public function index(Request $request)
{
    try {
        $query = Prescription::with(['patient', 'doctor', 'medications'])
            ->when(!auth()->user()->isAdmin(), function($query) {
                $query->where('created_by', auth()->id());
            });

        // Search by patient name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Filter by sync status
        if ($request->filled('sync_status')) {
            $query->where('sync_status', $request->sync_status);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $prescriptions = $query->latest()
            ->paginate(15)
            ->withQueryString();

        // Fetch medications from Odoo with caching
        $odooMedications = Cache::remember('odoo_medications', 600, function () {
            return $this->odooApi->getMedicationList();
        });
        
        // Convert to a lookup array with product ID as key
        $medicationsLookup = collect($odooMedications)->keyBy('id')->all();

        // Enhance each prescription's medications with Odoo data
        foreach ($prescriptions as $prescription) {
            $prescription->medications = collect($prescription->medications)->map(function ($medication) use ($medicationsLookup) {
                if (isset($medicationsLookup[$medication->product])) {
                    $odooMed = $medicationsLookup[$medication->product];
                    $medication->product_name = $odooMed['name'];
                    $medication->product_code = $odooMed['default_code'];
                }
                return $medication;
            });
        }

        return view('prescriptions.index', compact('prescriptions'));

    } catch (\Exception $e) {
        Log::error('Error fetching Odoo medications for index: ' . $e->getMessage());
        return view('prescriptions.index', compact('prescriptions'))
            ->with('warning', 'Unable to fetch medication details from Odoo.');
    }
}

    public function create()
    {
        $user = auth()->user();
        
        try {
            // Get medications from Odoo with 10-minute cache
            $medications = Cache::remember('odoo_medications', 600, function () {
                return $this->odooApi->getMedicationList();
            });

            // Get patients based on user role
            $patients = $user->isAdmin() 
                ? Patient::orderBy('first_name')->get()
                : $user->patients()->orderBy('first_name')->get();

            return view('prescriptions.create', compact('patients', 'medications'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch medications:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('prescriptions.create', [
                'patients' => $patients ?? [],
                'medications' => []
            ])->with('warning', 'Unable to load medication list. Please try again later.');
        }
    }


       /**
     * Validate prescription data
     */
    private function validatePrescriptionData(Request $request)
    {
        return $request->validate([
            'prescription_date' => 'required|date',
            'patient_id' => 'required|exists:patients,id',
            'medications' => 'required|array|min:1',
            'medications.*.type' => 'required|in:odoo,custom',
            'medications.*.product_id' => 'required_if:medications.*.type,odoo',
            'medications.*.custom_name' => 'required_if:medications.*.type,custom',
            'medications.*.quantity' => 'required|integer|min:1',
            'medications.*.dosage' => 'required|string',
            'medications.*.every' => 'nullable|integer|min:1',
            'medications.*.period' => 'nullable|in:hours,days,weeks,months',
            'medications.*.directions' => 'required|string',
            'medications.*.as_needed' => 'nullable|boolean'
        ]);
    }


    public function store(Request $request)
    {
        try {
            $validated = $this->validatePrescriptionData($request);
            
            $prescription = $this->prescriptionService->create($validated);

            return redirect()
                ->route('prescriptions.show', $prescription)
                ->with('success', 'Prescription created successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create prescription: ' . $e->getMessage());
        }
    }










    

    /**
     * Format medications for Odoo sales order (only Odoo products)
     */
    private function formatMedicationsForOdoo($medications)
    {
        return $medications
            ->where('type', 'odoo')  // Only include Odoo products
            ->map(function ($medication) {
                return [0, 0, [
                    'product_id' => (int)$medication->product,
                    'product_uom_qty' => $medication->quantity,
                    'name' => $medication->directions
                ]];
            })
            ->toArray();
    }






        public function show(Prescription $prescription)
        {
            try {
                // Fetch medications from Odoo with caching
                $odooMedications = Cache::remember('odoo_medications', 600, function () {
                    return $this->odooApi->getMedicationList();
                });
                
                // Convert to a lookup array with product ID as key
                $medicationsLookup = collect($odooMedications)->keyBy('id')->all();

                // Enhance each prescription's medications with Odoo data
                $prescription->medications = collect($prescription->medications)->map(function ($medication) use ($medicationsLookup) {
                    if (isset($medicationsLookup[$medication->product])) {
                        $odooMed = $medicationsLookup[$medication->product];
                        $medication->product_name = $odooMed['name'];
                        $medication->product_code = $odooMed['default_code'];
                    }
                    return $medication;
                });

                return view('prescriptions.show', compact('prescription'));

            } catch (\Exception $e) {
                Log::error('Error fetching Odoo medications: ' . $e->getMessage());
                return view('prescriptions.show', compact('prescription'))
                    ->with('warning', 'Unable to fetch medication details from Odoo.');
            }
        }




    public function edit(Prescription $prescription)
    {
        // Ensure the authenticated doctor has access to this patient
        if (auth()->user()->isDoctor() && !$prescription->patient->hasDoctor(auth()->user())) {
            abort(403, 'Unauthorized access to this patient.');
        }

        try {
            // Get medications from Odoo with caching
            $odooMedications = Cache::remember('odoo_medications', 600, function () {
                return $this->odooApi->getMedicationList();
            });

            // Convert medications to array format for select2
            $medications = collect($odooMedications)->map(function ($med) {
                return [
                    'id' => $med['id'],
                    'text' => $med['name'] . ' (' . $med['default_code'] . ')',
                    'name' => $med['name'],
                    'default_code' => $med['default_code']
                ];
            })->values()->all();

            $patients = auth()->user()->isAdmin() 
                ? Patient::orderBy('first_name')->get()
                : auth()->user()->patients()->orderBy('first_name')->get();

            // Eager load medications
            $prescription->load('medications');

            return view('prescriptions.edit', compact('prescription', 'patients', 'medications'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch medications for edit:', [
                'error' => $e->getMessage(),
                'prescription_id' => $prescription->id
            ]);

            return view('prescriptions.edit', [
                'prescription' => $prescription,
                'patients' => $patients,
                'medications' => []
            ])->with('warning', 'Unable to load medication list. Please try again later.');
        }
    }






    public function update(Request $request, Prescription $prescription)
    {
        try {
            // Prevent updates if prescription is already synced
            if ($prescription->sync_status === 'synced') {
                return back()->with('error', 'Cannot edit a prescription that has already been synced.');
            }

            // Validate request
            $request->validate([
                'prescription_date' => 'required|date',
                'patient_id' => 'required|exists:patients,id',
                'medications' => 'required|array|min:1',
                'medications.*.type' => 'required|in:odoo,custom',
                'medications.*.product_id' => 'required_if:medications.*.type,odoo',
                'medications.*.custom_name' => 'required_if:medications.*.type,custom',
                'medications.*.quantity' => 'required|integer|min:1',
                'medications.*.dosage' => 'required|string',
                'medications.*.every' => 'nullable|integer|min:1',
                'medications.*.period' => 'nullable|in:hours,days,weeks,months',
                'medications.*.directions' => 'required|string'
            ]);

            DB::beginTransaction();

            try {
                $prescription->update([
                    'patient_id' => $request->patient_id,
                    'prescription_date' => $request->prescription_date
                ]);

                // Delete existing medications
                $prescription->medications()->delete();

                // Process medications
                foreach ($request->medications as $medicationData) {
                    // Determine the product value based on type
                    $product = $medicationData['type'] === 'odoo' 
                        ? $medicationData['product_id']
                        : $medicationData['custom_name'];

                    $prescription->medications()->create([
                        'type' => $medicationData['type'],
                        'product' => $product,
                        'quantity' => $medicationData['quantity'],
                        'dosage' => $medicationData['dosage'],
                        'every' => $medicationData['every'] ?? null,
                        'period' => $medicationData['period'] ?? null,
                        'as_needed' => isset($medicationData['as_needed']),
                        'directions' => $medicationData['directions']
                    ]);
                }

                // Reset sync status if prescription was in error state
                if ($prescription->sync_status === 'error') {
                    $prescription->update([
                        'sync_status' => 'pending',
                        'sync_error' => null
                    ]);
                }

                DB::commit();

                return redirect()
                    ->route('prescriptions.show', $prescription)
                    ->with('success', 'Prescription updated successfully.');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Failed to update prescription:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'prescription_id' => $prescription->id
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update prescription: ' . $e->getMessage());
        }
    }




    public function destroy(Prescription $prescription)
    {
        $this->authorize('delete', $prescription);
        
        if ($prescription->sync_status === 'synced') {
            return back()->with('error', 'Cannot delete a synced prescription.');
        }

        $prescription->delete();
        
        return redirect()
            ->route('prescriptions.index')
            ->with('success', 'Prescription deleted successfully.');
    }

    public function resync(Prescription $prescription)
    {
        try {
            // Implement your resync logic here
            // This should call your Odoo service to resync the prescription
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to resync prescription:', [
                'error' => $e->getMessage(),
                'prescription_id' => $prescription->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to resync prescription: ' . $e->getMessage()
            ], 500);
        }
    }
}