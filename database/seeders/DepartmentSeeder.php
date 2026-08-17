<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'IT',
            ],
            [
                'name' => 'Human Resources',
            ],
            [
                'name' => 'Finance',
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                [
                    'name' => $department['name'],
                ],
                $department
            );
        }
    }
}
