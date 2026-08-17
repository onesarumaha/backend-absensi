<?php

namespace Database\Seeders;

use App\Models\WorkSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $schedules = [
            [
                'name' => 'Regular',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
            ],
        ];

        foreach ($schedules as $schedule) {
            WorkSchedule::updateOrCreate(
                [
                    'name' => $schedule['name'],
                ],
                $schedule
            );
        }
    }
}
