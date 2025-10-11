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
        // Check if user already exists
        if (User::where('email', 'superadmin@itl.com')->exists()) {
            $this->command->info('Super Admin user already exists!');
            return;
        }

        // Get the Super Admin role with api guard
        $superAdminRole = Role::where('name', 'Super Admin')
            ->where('guard_name', 'api')
            ->first();

        if (!$superAdminRole) {
            $this->command->error('Super Admin role not found with api guard! Please run your roles seeder first with the api guard.');
            return;
        }

        // Create Super Admin User
        $superAdmin = User::create([
            'name' => 'Eghosa',
            'email' => 'eghosa@intertradeltd.biz',
            'password' => Hash::make('ITL@2025'),
            'email_verified_at' => now(),
        ]);

        // Assign Super Admin role to user with api guard
        $superAdmin->assignRole($superAdminRole);

        $this->command->info('Super Admin user created successfully!');
        $this->command->info('Email: superadmin@itl.com');
        $this->command->info('Password: ITL@2025');
        $this->command->info('Role: Super Admin (api guard)');
    }
}
