<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\SyncPrescriptionWithOdoo;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\OdooApi;
use App\Services\PrescriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB; // Add this import
use Illuminate\Support\Facades\Log;

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
                ->when(!auth()->user()->isAdmin(), function ($query) {
                    $query->where('created_by', auth()->id());
                });

            // Search by patient name
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('patient', function ($q) use ($search) {
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

    public function odoo_index(Request $request)
    {
        try {
            $doctorId = auth()->user()->odoo_doctor_id;

            $query = Prescription::with(['patient', 'doctor', 'medications'])
                ->where('created_by', auth()->id());

            // Search by patient name
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('patient', function ($q) use ($search) {
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

            // Fetch sales orders from Odoo based on the doctor's ID
            $odooSalesOrders = $this->odooApi->getSalesOrdersByDoctor($doctorId);

            // Map the Odoo sales orders to a format compatible with your Prescription model
            $odooSalesOrders = collect($odooSalesOrders)->map(function ($order) {
                // ... (map the order data to a format compatible with your Prescription model)
                return $mappedOrder;
            });

            // Merge the local prescriptions with the Odoo sales orders
            $prescriptions = $prescriptions->merge($odooSalesOrders);

            return view('prescriptions.index', compact('prescriptions'));

        } catch (\Exception $e) {
            Log::error('Error fetching Odoo sales orders: ' . $e->getMessage());
            return view('prescriptions.index', compact('prescriptions'))
                ->with('warning', 'Unable to fetch sales orders from Odoo.');
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
                'trace' => $e->getTraceAsString(),
            ]);

            return view('prescriptions.create', [
                'patients' => $patients ?? [],
                'medications' => [],
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
            'medications.*.as_needed' => 'nullable|boolean',
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
            ->where('type', 'odoo') // Only include Odoo products
            ->map(function ($medication) {
                return [0, 0, [
                    'product_id' => (int) $medication->product,
                    'product_uom_qty' => $medication->quantity,
                    'name' => $medication->directions,
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
                    'default_code' => $med['default_code'],
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
                'prescription_id' => $prescription->id,
            ]);

            return view('prescriptions.edit', [
                'prescription' => $prescription,
                'patients' => $patients,
                'medications' => [],
            ])->with('warning', 'Unable to load medication list. Please try again later.');
        }
    }

    public function update(Request $request, Prescription $prescription)
    {
        try {
            // Check if prescription is already synced
            if ($prescription->sync_status === 'synced') {
                throw new \Exception('Cannot update a synced prescription.');
            }

            $validated = $request->validate([
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
                'medications.*.as_needed' => 'nullable|boolean',
                'medications.*.directions' => 'nullable|string',
            ]);

            DB::transaction(function () use ($validated, $prescription) {
                dd($validated);
                
                // Update prescription
                $prescription->update([
                    'prescription_date' => $validated['prescription_date'],
                    'patient_id' => $validated['patient_id'],
                    'sync_status' => 'pending'
                ]);

                // Delete existing medications
                $prescription->medications()->delete();

                // Create new medications
                foreach ($validated['medications'] as $medicationData) {
                    $prescription->medications()->create([
                        'type' => $medicationData['type'],
                        'product' => $medicationData['type'] === 'odoo' 
                            ? $medicationData['product_id'] 
                            : $medicationData['custom_name'],
                        'product_name' => $medicationData['type'] === 'odoo' 
                            ? $medicationData['custom_name']  // Using the name from the form
                            : $medicationData['custom_name'],
                        'quantity' => $medicationData['quantity'],
                        'dosage' => $medicationData['dosage'],
                        'every' => $medicationData['every'] ?? null,
                        'period' => $medicationData['period'] ?? null,
                        'as_needed' => $medicationData['as_needed'] ?? false,
                        'directions' => $medicationData['directions'] ?? null,
                    ]);
                }
            });

            return redirect()
                ->route('prescriptions.show', $prescription)
                ->with('success', 'Prescription updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update prescription', [
                'prescription_id' => $prescription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
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
          

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to resync prescription:', [
                'error' => $e->getMessage(),
                'prescription_id' => $prescription->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resync prescription: ' . $e->getMessage(),
            ], 500);
        }
    }
}
