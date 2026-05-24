<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ============ ADMIN ============
        $adminDepartmentId = Department::where('name', 'IT Department')->value('id');

        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Password@123'),
                'phone' => '09101966700',
                'role' => 'admin',
                'status' => 'active',
                'department_id' => $adminDepartmentId,
                'position' => 'System Administrator',
                'email_verified_at' => now(),
            ]
        );

        // ============ REGULAR USER (CUSTOMER) ============
        // Regular customers - NO department_id, NO position
        User::firstOrCreate(
            ['email' => 'user1@example.com'],
            [
                'name' => 'Customer One',
                'password' => Hash::make('Password@123'),
                'phone' => '09181234567',
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                // department_id and position removed
            ]
        );

        // Create additional regular customers
        $customers = [
            ['email' => 'customer2@example.com', 'name' => 'John Smith', 'phone' => '09181234568'],
            ['email' => 'customer3@example.com', 'name' => 'Maria Santos', 'phone' => '09181234569'],
            ['email' => 'customer4@example.com', 'name' => 'Robert Johnson', 'phone' => '09181234570'],
            ['email' => 'customer5@example.com', 'name' => 'Lisa Wong', 'phone' => '09181234571'],
        ];

        foreach ($customers as $customer) {
            User::firstOrCreate(
                ['email' => $customer['email']],
                [
                    'name' => $customer['name'],
                    'password' => Hash::make('Password@123'),
                    'phone' => $customer['phone'],
                    'role' => 'user',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    // department_id and position removed
                ]
            );
        }

        // ============ AGENTS ONLY ============
        $departments = Department::all();

        foreach ($departments as $department) {

            $slug = Str::slug($department->name, '-');

            for ($i = 1; $i <= 2; $i++) {

                User::firstOrCreate(
                    [
                        'email' => "agent-{$slug}-{$i}@example.com"
                    ],
                    [
                        'name' => $department->name . ' Agent ' . $i,
                        'employee_id' => $this->generateEmployeeId($department->name),
                        'password' => Hash::make('Password@123'),
                        'phone' => '0918' . rand(1000000, 9999999),
                        'role' => 'agent',
                        'status' => 'active',
                        'department_id' => $department->id,
                        'position' => 'Support Agent',
<<<<<<< HEAD
=======

                        // IMPORTANT (based on your blade fields)
                        'specialization' => null,
                        'skills' => null,

                        'schedule' => json_encode($schedule),
                        'day_off' => false,

>>>>>>> b0c0e2c971a5c46df7f3dd5eab2ca3a7e70516f3
                        'email_verified_at' => now(),
                    ]
                );
            }
        }

        // ============ SUMMARY ============
        $this->command->info('Users seeded successfully!');
        $this->command->info('Total Admins: ' . User::where('role', 'admin')->count());
        $this->command->info('Total Agents: ' . User::where('role', 'agent')->count());
        $this->command->info('Total Customers: ' . User::where('role', 'user')->count());
        $this->command->info('Total All: ' . User::count());

        $this->command->info("\n=== AGENT DISTRIBUTION BY DEPARTMENT ===");

        foreach (Department::all() as $department) {
            $count = User::where('role', 'agent')
                ->where('department_id', $department->id)
                ->count();

            $this->command->info($department->name . ': ' . $count . ' agent(s)');
        }
    }

    // Generate employee ID
    private function generateEmployeeId($departmentName)
    {
        $code = strtoupper(substr(
            preg_replace('/[^A-Za-z]/', '', $departmentName),
            0,
            3
        ));

        return 'AGT-' . $code . '-' . rand(1000, 9999);
    }
}