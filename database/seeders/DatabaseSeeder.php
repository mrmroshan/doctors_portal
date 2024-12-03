<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;    // Add this import
use App\Models\Patient; // Add this import since you're using Patient model too


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        //   // Create admin user
        //   User::create([
        //     'name' => 'Admin User',
        //     'email' => 'admin@example.com',
        //     'password' => bcrypt('password'),
        //     'role' => 'admin',
        // ]);

        // // Create doctor user
        // User::create([
        //     'name' => 'Doctor User',
        //     'email' => 'doctor@example.com',
        //     'password' => bcrypt('password'),
        //     'role' => 'doctor',
        // ]);

        // // Create some test patients
        // Patient::create([
        //     'first_name' => 'John',
        //     'last_name' => 'Doe',
        //     'date_of_birth' => '1990-01-01',
        //     'email' => 'john@example.com',
        //     'phone' => '1234567890',
        //     'address' => '123 Main St',
        // ]);

         // Create additional admin users using factory
        User::factory()->count(5)->create([
            'role' => 'admin'
        ]);

        // Create additional doctor users using factory
        User::factory()->count(10)->create([
            'role' => 'doctor'
        ]);

        $doctors = User::where('role', 'doctor')->get();


        //Patient::factory()->count(20)->create();

         // Create patients and assign random doctors to each patient
    Patient::factory()
    ->count(20)
    ->create()
    ->each(function ($patient) use ($doctors) {
        // Randomly assign 1-3 doctors to each patient
        $randomDoctors = $doctors->random(rand(1, 3));
        $patient->doctors()->attach($randomDoctors);
    });


        // \App\Models\User::factory(10)->create();
        // $this->call([
        //     PatientSeeder::class,
        //     PrescriptionSeeder::class
        // ]);
    }
}
