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
        $query = Prescription::with(['patient', 'doctor'])
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
        // Log the incoming request data
        \Log::info('Prescription creation attempt:', [
            'request_data' => $request->all(),
            'user_id' => auth()->id()
        ]);

        // Validate the request
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'prescription_date' => 'required|date',
            'medications' => 'required|array|min:1',
            'medications.*.product' => 'required|string|max:255',
            'medications.*.quantity' => 'required|numeric|min:1', // Changed to numeric
            'medications.*.dosage' => 'required|string|max:255',
            'medications.*.every' => 'nullable|numeric|min:1',    // Changed to numeric
            'medications.*.period' => 'nullable|string|in:hour,hours,day,days,week,weeks',
            'medications.*.as_needed' => 'boolean',
            'medications.*.directions' => 'required|string'
        ]);

        \Log::info('Validation passed:', ['validated_data' => $validated]);

        // Begin transaction
        \DB::beginTransaction();

        try {
            // Create prescriptions for each medication
            foreach ($validated['medications'] as $medication) {
                $prescription = new Prescription();
                $prescription->patient_id = $validated['patient_id'];
                $prescription->prescription_date = $validated['prescription_date'];
                $prescription->created_by = auth()->id();
                $prescription->sync_status = 'pending';
                
                // Set medication details
                $prescription->product = $medication['product'];
                $prescription->quantity = $medication['quantity'];
                $prescription->dosage = $medication['dosage'];
                $prescription->every = $medication['every'] ?? null;
                $prescription->period = $medication['period'] ?? null;
                $prescription->as_needed = isset($medication['as_needed']);
                $prescription->directions = $medication['directions'];
                
                $prescription->save();
                
                \Log::info('Prescription created:', [
                    'prescription_id' => $prescription->id,
                    'patient_id' => $prescription->patient_id
                ]);
            }

            \DB::commit();

            return redirect()
                ->route('prescriptions.index')
                ->with('success', 'Prescriptions created successfully.');

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






    public function edit(Prescription $prescription)
    {
        // Ensure the authenticated doctor has access to this patient
        if (auth()->user()->isDoctor() && !$prescription->patient->hasDoctor(auth()->user())) {
            abort(403, 'Unauthorized access to this patient.');
        }

        $patients = auth()->user()->isAdmin() 
            ? Patient::orderBy('first_name')->get()
            : auth()->user()->patients()->orderBy('first_name')->get();

        return view('prescriptions.edit', compact('prescription', 'patients'));
    }







    public function update(Request $request, Prescription $prescription)
    {
        $this->authorize('update', $prescription);

        // Prevent updates if prescription is already synced with Odoo
        if ($prescription->sync_status === 'synced') {
            return back()->with('error', 'Cannot edit a prescription that has already been synced.');
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'product' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'dosage' => 'required|string',
            'every' => 'nullable|integer',
            'period' => 'nullable|in:hour,hours,day,days,week,weeks',
            'as_needed' => 'boolean',
            'directions' => 'required|string'
        ]);

        $prescription->update($validated);

        // Reset sync status if prescription was in error state
        if ($prescription->sync_status === 'error') {
            $prescription->update([
                'sync_status' => 'pending',
                'sync_error' => null
            ]);
        }

        return redirect()
            ->route('prescriptions.show', $prescription)
            ->with('success', 'Prescription updated successfully.');
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