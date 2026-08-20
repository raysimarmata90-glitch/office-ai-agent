<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $aiDepartment = Department::where('code', 'ai')->first();

        // Create admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@office.com',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRole->id,
            'department_id' => $aiDepartment->id,
            'phone' => '081234567890',
            'bio' => 'System Administrator',
            'is_active' => true,
        ]);
    }
}
