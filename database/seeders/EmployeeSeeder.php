<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'admin@absensi.test')->first();

        if (! $user) {
            $this->command->warn('User admin@absensi.test tidak ditemukan. Skip EmployeeSeeder.');
            return;
        }

        Employee::updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_number' => 'EMP001',
                'full_name' => $user->name,
                'phone' => '08123456789',
                'address' => 'Jakarta',
                'department_id' => Department::first()?->id,
                'position_id' => Position::first()?->id,
                'work_schedule_id' => WorkSchedule::first()?->id,
                'join_date' => now()->toDateString(),
                'status' => 'active',
            ]
        );

        $this->command->info('EmployeeSeeder: user ' . $user->email . ' linked to employee.');
    }
}
