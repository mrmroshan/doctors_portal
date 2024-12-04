<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PrescriptionController extends Controller
{
    public function index(Request $request)
{
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

    return view('prescriptions.index', compact('prescriptions'));
}






    public function create()
{
    $user = auth()->user();
    
    // Debug logging
    \Log::info('Prescription create page accessed', [
        'user_id' => $user->id,
        'user_role' => $user->role,
        'is_doctor' => $user->isDoctor(),
        'is_admin' => $user->isAdmin()
    ]);

    if ($user->isAdmin()) {
        $patients = Patient::orderBy('first_name')->get();
    } else {
        // Get patients with additional debug info
        $patients = $user->patients()
            ->orderBy('first_name')
            ->get();
        
        // Log the query for debugging
        \Log::info('Doctor patients query', [
            'doctor_id' => $user->id,
            'patient_count' => $patients->count(),
            'patient_ids' => $patients->pluck('id'),
            'sql' => $user->patients()->toSql()
        ]);
    }

    return view('prescriptions.create', compact('patients'));
}





public function store(Request $request)
{
    try {
        // Log the incoming request data (keep existing logging)
        \Log::info('Prescription creation attempt:', [
            'request_data' => $request->all(),
            'user_id' => auth()->id()
        ]);

        // Update validation rules to handle multiple medications
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'prescription_date' => 'required|date',
            'medications' => 'required|array|min:1',                    // Changed
            'medications.*.product' => 'required|string|max:255',       // Changed
            'medications.*.quantity' => 'required|numeric|min:1',       // Changed
            'medications.*.dosage' => 'required|string|max:255',        // Changed
            'medications.*.every' => 'nullable|numeric|min:1',          // Changed
            'medications.*.period' => 'nullable|string|in:hour,hours,day,days,week,weeks', // Changed
            'medications.*.as_needed' => 'boolean',                     // Changed
            'medications.*.directions' => 'required|string'             // Changed
        ]);

        \Log::info('Validation passed:', ['validated_data' => $validated]);

        // Begin transaction (keep existing transaction handling)
        \DB::beginTransaction();

        try {
            // Create single prescription (simplified)
            $prescription = new Prescription();
            $prescription->patient_id = $validated['patient_id'];
            $prescription->prescription_date = $validated['prescription_date'];
            $prescription->created_by = auth()->id();
            $prescription->sync_status = 'pending';
            $prescription->save();

            // Create medications for the prescription (new)
            foreach ($validated['medications'] as $medicationData) {
                $prescription->medications()->create([
                    'product' => $medicationData['product'],
                    'quantity' => $medicationData['quantity'],
                    'dosage' => $medicationData['dosage'],
                    'every' => $medicationData['every'] ?? null,
                    'period' => $medicationData['period'] ?? null,
                    'as_needed' => isset($medicationData['as_needed']),
                    'directions' => $medicationData['directions']
                ]);
            }

            \DB::commit();

            return redirect()
                ->route('prescriptions.index')
                ->with('success', 'Prescription created successfully.');

        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }

    } catch (\Exception $e) {
        \Log::error('Failed to create prescription:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return back()
            ->withInput()
            ->with('error', 'Failed to create prescription: ' . $e->getMessage());
    }
}





public function show(Prescription $prescription)
{
    // Ensure the authenticated doctor has access to this patient
    if (auth()->user()->isDoctor() && !$prescription->patient->hasDoctor(auth()->user())) {
        abort(403, 'Unauthorized access to this patient.');
    }

    // Eager load relationships
    $prescription->load(['medications', 'patient', 'doctor']);

    return view('prescriptions.show', compact('prescription'));
}







public function edit(Prescription $prescription)
{
    // Ensure the authenticated doctor has access to this patient
    if (auth()->user()->isDoctor() && !$prescription->patient->hasDoctor(auth()->user())) {
        abort(403, 'Unauthorized access to this patient.');
    }

    $patients = auth()->user()->isAdmin() 
        ? Patient::orderBy('first_name')->get()
        : auth()->user()->patients()->orderBy('first_name')->get();

    // Eager load medications
    $prescription->load('medications');

    return view('prescriptions.edit', compact('prescription', 'patients'));
}







public function update(Request $request, Prescription $prescription)
{
    try {
        // Prevent updates if prescription is already synced
        if ($prescription->sync_status === 'synced') {
            return back()->with('error', 'Cannot edit a prescription that has already been synced.');
        }

        // Validate the request
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'prescription_date' => 'required|date',
            'medications' => 'required|array|min:1',
            'medications.*.product' => 'required|string|max:255',
            'medications.*.quantity' => 'required|numeric|min:1',
            'medications.*.dosage' => 'required|string|max:255',
            'medications.*.every' => 'nullable|numeric|min:1',
            'medications.*.period' => 'nullable|string|in:hour,hours,day,days,week,weeks',
            'medications.*.as_needed' => 'boolean',
            'medications.*.directions' => 'required|string'
        ]);

        \DB::beginTransaction();

        try {
            // Update prescription basic info
            $prescription->update([
                'patient_id' => $validated['patient_id'],
                'prescription_date' => $validated['prescription_date']
            ]);

            // Delete existing medications
            $prescription->medications()->delete();

            // Create new medications
            foreach ($validated['medications'] as $medicationData) {
                $prescription->medications()->create([
                    'product' => $medicationData['product'],
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

            \DB::commit();

            return redirect()
                ->route('prescriptions.show', $prescription)
                ->with('success', 'Prescription updated successfully.');

        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }

    } catch (\Exception $e) {
        \Log::error('Failed to update prescription:', [
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
        return redirect()->route('prescriptions.index')
            ->with('success', 'Prescription deleted successfully.');
    }
}