<?php

namespace App\Services;

use App\Models\Prescription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Exceptions\OdooSyncException;

class OdooSyncService
{
    protected $baseUrl;
    protected $database;
    protected $username;
    protected $password;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('odoo.base_url');
        $this->database = config('odoo.database');
        $this->username = config('odoo.username');
        $this->password = config('odoo.password');
        $this->apiKey = config('odoo.api_key');
    }

    /**
     * Sync a prescription to Odoo
     *
     * @param Prescription $prescription
     * @return bool
     * @throws OdooSyncException
     */
    public function syncPrescription(Prescription $prescription)
    {
        try {
            // Get only non-custom medications
            $orderLines = $this->prepareOrderLines($prescription);

            // If no Odoo products, skip sync
            if (empty($orderLines)) {
                Log::info('Prescription contains only custom medications, skipping Odoo sync', [
                    'prescription_id' => $prescription->id
                ]);
                return true;
            }

            // Create sales order in Odoo
            $orderId = $this->createSalesOrder($prescription, $orderLines);

            // Mark prescription as synced
            $prescription->markAsSynced($orderId);

            Log::info('Prescription synced successfully', [
                'prescription_id' => $prescription->id,
                'odoo_order_id' => $orderId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to sync prescription with Odoo', [
                'prescription_id' => $prescription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $prescription->markAsFailed($e->getMessage());
            
            throw new OdooSyncException('Failed to sync prescription: ' . $e->getMessage());
        }
    }

    /**
     * Prepare order lines for Odoo sales order
     *
     * @param Prescription $prescription
     * @return array
     */
    protected function prepareOrderLines(Prescription $prescription): array
    {
        $orderLines = [];

        foreach ($prescription->medications as $medication) {
            // Skip custom medications
            if ($medication->is_custom) {
                continue;
            }

            $orderLines[] = [
                'product_id' => $medication->product,
                'product_uom_qty' => $medication->quantity,
                'name' => $this->buildProductDescription($medication),
                // Add any other required fields for Odoo order lines
            ];
        }

        return $orderLines;
    }

    /**
     * Create a sales order in Odoo
     *
     * @param Prescription $prescription
     * @param array $orderLines
     * @return string
     * @throws OdooSyncException
     */
    //@todo:remove 1 at the end
    protected function createSalesOrder1(Prescription $prescription, array $orderLines): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/api/sale.order", [
                'db' => $this->database,
                'login' => $this->username,
                'password' => $this->password,
                'data' => [
                    'partner_id' => $prescription->patient->odoo_partner_id,
                    'date_order' => $prescription->prescription_date->format('Y-m-d'),
                    'prescription_id' => $prescription->id,
                    'doctor_id' => $prescription->doctor->odoo_user_id,
                    'order_line' => $orderLines,
                    'note' => $this->buildOrderNotes($prescription),
                ]
            ]);

            if (!$response->successful()) {
                throw new OdooSyncException('Failed to create sales order: ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['id'])) {
                throw new OdooSyncException('Invalid response from Odoo: Order ID not found');
            }

            return (string) $data['id'];

        } catch (\Exception $e) {
            throw new OdooSyncException('Failed to create sales order: ' . $e->getMessage());
        }
    }

    /**
     * Build product description for Odoo order line
     *
     * @param \App\Models\PrescriptionMedication $medication
     * @return string
     */
    protected function buildProductDescription($medication): string
    {
        $description = $medication->dosage;

        if ($medication->every && $medication->period) {
            $description .= " every {$medication->every} {$medication->period}";
        }

        if ($medication->as_needed) {
            $description .= " (as needed)";
        }

        if ($medication->directions) {
            $description .= "\nDirections: {$medication->directions}";
        }

        return $description;
    }

    /**
     * Build order notes including custom medications
     *
     * @param Prescription $prescription
     * @return string
     */
    protected function buildOrderNotes(Prescription $prescription): string
    {
        $notes = [];

        // Add custom medications to notes
        $customMeds = $prescription->medications()->where('is_custom', true)->get();
        if ($customMeds->isNotEmpty()) {
            $notes[] = "Custom Medications:";
            foreach ($customMeds as $med) {
                $notes[] = sprintf(
                    "- %s (%s)\n  Quantity: %d\n  Dosage: %s\n  Directions: %s",
                    $med->custom_name,
                    $med->custom_strength,
                    $med->quantity,
                    $med->dosage,
                    $med->directions
                );
                if ($med->custom_notes) {
                    $notes[] = "  Notes: " . $med->custom_notes;
                }
            }
        }

        // Add any additional prescription notes here
        if ($prescription->notes) {
            $notes[] = "\nPrescription Notes:";
            $notes[] = $prescription->notes;
        }

        return implode("\n\n", $notes);
    }

    /**
     * Get medication list from Odoo
     *
     * @return array
     * @throws OdooSyncException
     */
    public function getMedicationList(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/api/product.product", [
                'db' => $this->database,
                'login' => $this->username,
                'password' => $this->password,
                'domain' => [['is_medication', '=', true]],
                'fields' => ['id', 'name', 'default_code', 'list_price', 'qty_available']
            ]);

            if (!$response->successful()) {
                throw new OdooSyncException('Failed to fetch medications: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new OdooSyncException('Failed to fetch medications: ' . $e->getMessage());
        }
    }

    /**
     * Retry failed sync
     *
     * @param Prescription $prescription
     * @return bool
     */
    public function retrySyncPrescription(Prescription $prescription): bool
    {
        if (!$prescription->canSync()) {
            throw new OdooSyncException('Prescription cannot be synced at this time');
        }

        return $this->syncPrescription($prescription);
    }
}