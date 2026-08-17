<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => 'Software Engineer',
            ],
            [
                'name' => 'HR Staff',
            ],
            [
                'name' => 'Accountant',
            ],
        ];

        foreach ($positions as $position) {
            \App\Models\Position::updateOrCreate(
                [
                    'name' => $position['name'],
                ],
                $position
            );
        }
    }
}
