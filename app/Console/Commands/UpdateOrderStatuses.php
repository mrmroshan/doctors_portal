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
            
            $updatedCount = 0;
            
            // Update local prescription records with new statuses
            foreach ($prescriptions as $prescription) {
                $orderId = $prescription->odoo_order_id;
                
                if (isset($updatedOrders[$orderId])) {
                    $orderData = $updatedOrders[$orderId];
                    $newStatus = $orderData['state'];
                    
                    // Only update if status has changed
                    if ($prescription->order_status !== $newStatus) {
                        $prescription->update([
                            'order_status' => $newStatus
                        ]);
                        
                        $updatedCount++;
                        
                        Log::info("Updated order status", [
                            'prescription_id' => $prescription->id,
                            'odoo_order_id' => $orderId,
                            'old_status' => $prescription->order_status,
                            'new_status' => $newStatus
                        ]);
                    }
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