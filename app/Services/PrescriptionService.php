<?php

namespace App\Services;

use App\Models\Prescription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrescriptionService
{
    protected $odooApi;
    protected $patientService;


    public function __construct(OdooApi $odooApi,PatientService $patientService)
    {
        $this->odooApi = $odooApi;
        $this->patientService = $patientService;

    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            // Create prescription
            $prescription = Prescription::create([
                'prescription_date' => $data['prescription_date'],
                'patient_id' => $data['patient_id'],
                'created_by' => auth()->id(),
                'sync_status' => Prescription::STATUS_PENDING
            ]);

            // Create medications
            $this->createMedications($prescription, $data['medications']);

            // Attempt Odoo sync if needed
            $this->syncWithOdoo($prescription);

            DB::commit();
            return $prescription;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Prescription creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Create medications for a prescription
     */
    protected function createMedications(Prescription $prescription, array $medications)
    {
        foreach ($medications as $medicationData) {
            $prescription->medications()->create([
                'type' => $medicationData['type'],
                'product' => $medicationData['type'] === 'odoo' 
                    ? $medicationData['product_id'] 
                    : $medicationData['custom_name'],
                'quantity' => $medicationData['quantity'],
                'dosage' => $medicationData['dosage'],
                'every' => $medicationData['every'] ?? null,
                'period' => $medicationData['period'] ?? null,
                'as_needed' => isset($medicationData['as_needed']),
                'directions' => $medicationData['directions']
            ]);
        }
    }





    protected function syncWithOdoo(Prescription $prescription)
    {
        
        $odooMedications = $prescription->medications->where('type', 'odoo');
    
        if ($odooMedications->isEmpty()) {
            $prescription->update(['sync_status' => Prescription::STATUS_NOT_REQUIRED]);
            return;
        }
    
        try {
            // Sync patient with Odoo first
            $partnerId = $this->patientService->syncWithOdoo($prescription->patient);
    
            // Create sales order
            $orderId = $this->odooApi->createSalesOrder([
                'partner_id' => (int)auth()->user()->odoo_doctor_id,
                'prescription_reference' => $prescription->id,
                'doctor_id' => (int)auth()->user()->odoo_doctor_id,
                'patient_phone' => $prescription->patient->phone,
                'patient' => $prescription->patient->first_name ." ".$prescription->patient->last_name,
            ]);
    
            // Create order lines
            $orderLines = $odooMedications->map(function ($medication) use ($orderId) {
                $product = $this->odooApi->getProductData($medication->product);
    
                return [
                    'order_id' => $orderId,
                    'product_id' => (int)$medication->product,
                    'product_uom_qty' => (int)$medication->quantity,
                    'name' => $product['name'] ?? '', // Use product name
                    'price_unit' => $product['list_price'] ?? 0, // Use product list price
                    'directions' => $medication->directions, // Add directions field
                ];
            })->toArray();
    
            Log::info('$orderLines data', [$orderLines]);
    
            // Add order lines to the sales order
            foreach ($orderLines as $line) {
                $this->odooApi->addOrderLine($orderId, $line);
            }
    
            $prescription->update([
                'odoo_order_id' => $orderId,
                'sync_status' => Prescription::STATUS_SYNCED
            ]);
    
        } catch (\Exception $e) {
            $prescription->update([
                'sync_status' => Prescription::STATUS_ERROR,
                'sync_error' => $e->getMessage()
            ]);
    
            throw $e;
        }
    }


}