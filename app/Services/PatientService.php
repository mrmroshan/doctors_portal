<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\Log;

class PatientService
{
    protected $odooApi;

    public function __construct(OdooApi $odooApi)
    {
        $this->odooApi = $odooApi;
    }

    public function syncWithOdoo(Patient $patient)
{
    try {
        if ($patient->odoo_partner_id) {
            return (int)$patient->odoo_partner_id;
        }

        $partnerData = [
            'name' => trim($patient->first_name . ' ' . $patient->last_name),
            'phone' => $patient->phone ?? false,
            'mobile' => $patient->mobile ?? false,
            'email' => $patient->email ?? false,
            'customer_rank' => 1,
            'company_type' => 'person'
        ];

        $partnerId = $this->odooApi->createPartner($partnerData);

        if (!$partnerId) {
            throw new \Exception('Failed to get partner ID from Odoo');
        }

        $patient->update([
            'odoo_partner_id' => $partnerId,
            'sync_status' => Patient::SYNC_STATUS_SYNCED,
            'last_synced_at' => now()
        ]);

        return $partnerId;

    } catch (\Exception $e) {
        $patient->update([
            'sync_status' => Patient::SYNC_STATUS_ERROR,
            'sync_error' => $e->getMessage()
        ]);

        throw $e;
    }
}
}