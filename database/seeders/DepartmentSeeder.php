<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'AI',
                'code' => 'ai',
                'description' => 'Artificial Intelligence Department',
                'color' => '#8B5CF6', // purple
            ],
            [
                'name' => 'Platform',
                'code' => 'platform',
                'description' => 'Platform Engineering Department',
                'color' => '#3B82F6', // blue
            ],
            [
                'name' => 'Business Analyst',
                'code' => 'ba',
                'description' => 'Business Analyst Department',
                'color' => '#10B981', // green
            ],
            [
                'name' => 'Tech Delivery',
                'code' => 'td',
                'description' => 'Tech Delivery Department',
                'color' => '#F59E0B', // amber
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                $department
            );
        }
    }
}
