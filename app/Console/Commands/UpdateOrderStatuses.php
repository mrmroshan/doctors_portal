<?php

namespace App\Console\Commands;

use App\Models\Prescription;
use App\Services\OdooApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the status of orders from Odoo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting order status update...');
        
        try {
            // Get all prescriptions with Odoo order IDs that are not in final states
            $prescriptions = Prescription::whereNotNull('odoo_order_id')
                ->whereNotIn('order_status', ['Locked', 'Cancelled'])
                ->get();

            $this->info("Total prescriptions with odoo_order_id: " . Prescription::whereNotNull('odoo_order_id')->count());
            $this->info("Prescriptions to check: {$prescriptions->count()}");

            if ($prescriptions->isEmpty()) {
                $this->info('No orders to update.');
                return 0;
            }

            $this->info("Found {$prescriptions->count()} orders to check for updates.");
            
            // Get all order IDs to check
            $orderIds = $prescriptions->pluck('odoo_order_id')->toArray();
            
            // Get updated statuses from Odoo
            $odooApi = app(OdooApi::class);
            $updatedOrders = $odooApi->getSalesOrdersStatus($orderIds);

            $this->info("Order IDs to check: " . implode(', ', $orderIds));
            $this->info("Received " . count($updatedOrders) . " order updates from Odoo");

            $updatedCount = 0;
            
            // Update local prescription records with new statuses
            foreach ($prescriptions as $prescription) {

                $orderId = $prescription->odoo_order_id;

                $this->info("Checking prescription {$prescription->id} with order ID: {$orderId}");

                if (isset($updatedOrders[$orderId])) {
                    $orderData = $updatedOrders[$orderId];
                    $newStatus = $orderData['state'];
                    $currentStatus = $prescription->order_status;

                    $this->info("Current status: '{$currentStatus}', New status: '{$newStatus}'");

                    // Only update if status has changed
                    if ($currentStatus !== $newStatus) {
                        $this->info("Updating prescription {$prescription->id} from '{$currentStatus}' to '{$newStatus}'");

                        $result = $prescription->update([
                            'order_status' => $newStatus
                        ]);

                        if ($result) {
                            $this->info("✓ Successfully updated prescription {$prescription->id}");
                            $updatedCount++;
                        } else {
                            $this->error("✗ Failed to update prescription {$prescription->id}");
                        }

                        Log::info("Updated order status", [
                            'prescription_id' => $prescription->id,
                            'odoo_order_id' => $orderId,
                            'old_status' => $currentStatus,
                            'new_status' => $newStatus,
                            'update_result' => $result
                        ]);
                    } else {
                        $this->info("No change needed for prescription {$prescription->id}");
                    }
                } else {
                    $this->info("No data received from Odoo for order ID: {$orderId}");
                }
            }
            
            $this->info("Updated status for {$updatedCount} orders.");
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error updating order statuses: {$e->getMessage()}");
            Log::error('Failed to update order statuses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}