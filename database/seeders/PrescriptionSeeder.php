<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use App\Models\Prescription;
use Carbon\Carbon;

class PrescriptionSeeder extends Seeder
{
    public function run()
    {
        // Sample medications
        $medications = [
            'Amoxicillin 500mg',
            'Lisinopril 10mg',
            'Metformin 850mg',
            'Omeprazole 20mg',
            'Sertraline 50mg',
            'Ibuprofen 400mg',
            'Amlodipine 5mg',
            'Metoprolol 25mg'
        ];

        // Sample directions
        $directions = [
            'Take with food',
            'Take on an empty stomach',
            'Take before bedtime',
            'Take in the morning',
            'Take with plenty of water',
            'Do not take with dairy products'
        ];

        // Get some doctors and patients
        $doctors = User::where('role', 'doctor')->take(3)->get();
        if ($doctors->isEmpty()) {
            // Create a doctor if none exists
            $doctors = collect([User::factory()->create(['role' => 'doctor'])]);
        }

        $patients = Patient::take(5)->get();
        if ($patients->isEmpty()) {
            // Create some patients if none exist
            $patients = Patient::factory(5)->create();
        }

        // Create prescriptions with different sync statuses
        foreach ($doctors as $doctor) {
            foreach ($patients->random(3) as $patient) {
                // Create 2-3 prescriptions per patient
                $count = rand(2, 3);
                for ($i = 0; $i < $count; $i++) {
                    $status = ['pending', 'synced', 'error'][rand(0, 2)];
                    
                    $prescription = Prescription::create([
                        'patient_id' => $patient->id,
                        'created_by' => $doctor->id,
                        'product' => $medications[array_rand($medications)],
                        'quantity' => rand(1, 4) * 10, // 10, 20, 30, 40
                        'dosage' => rand(1, 2) . ' tablet',
                        'every' => rand(1, 3),
                        'period' => ['day', 'days', 'hours'][rand(0, 2)],
                        'as_needed' => (bool)rand(0, 1),
                        'directions' => $directions[array_rand($directions)],
                        'sync_status' => $status,
                        'created_at' => Carbon::now()->subDays(rand(0, 30)),
                    ]);

                    // Add sync details for synced/error prescriptions
                    if ($status === 'synced') {
                        $prescription->update([
                            'odoo_order_id' => 'SO' . str_pad(rand(1, 999), 4, '0', STR_PAD_LEFT),
                            'sync_attempted_at' => Carbon::now()->subHours(rand(1, 24))
                        ]);
                    } elseif ($status === 'error') {
                        $prescription->update([
                            'sync_error' => 'Failed to connect to Odoo server',
                            'sync_attempted_at' => Carbon::now()->subHours(rand(1, 24))
                        ]);
                    }
                }
            }
        }
    }
}