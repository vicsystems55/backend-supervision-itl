<?php
// database/seeders/FirstUserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FirstUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin User
        $superAdmin = User::firstOrCreate(
            [
                'email' => 'superadmin@itl.com'
            ],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('ITL@2025'),
                'email_verified_at' => now(),
            ]
        );

        // Assign existing Super Admin role to user
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        if ($superAdminRole) {
            $superAdmin->assignRole($superAdminRole);
            $this->command->info('Super Admin user created and role assigned successfully!');
        } else {
            $this->command->error('Super Admin role not found! Please make sure roles are seeded first.');
        }

        $this->command->info('Super Admin user details:');
        $this->command->info('Email: superadmin@itl.com');
        $this->command->info('Password: ITL@2025');
        $this->command->info('Role: Super Admin');
    }
}
