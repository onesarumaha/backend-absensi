<?php

namespace App\Http\Requests\Attendance;

use App\Models\LeaveRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attendance;

class CheckInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $user = $this->user();

            if (! $user) {
                return;
            }

            $employee = $user->employee;

            if (! $employee) {
                $validator->errors()->add(
                    'employee',
                    'User belum terhubung dengan data pegawai.'
                );

                return;
            }

            $today = now()->toDateString();

            /*
            |--------------------------------------------------------------------------
            | Cek approved leave
            |--------------------------------------------------------------------------
            */

            $approvedLeave = LeaveRequest::where(
                'employee_id',
                $employee->id
            )
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first();

            if ($approvedLeave) {

                $type = match ($approvedLeave->type) {
                    'cuti' => 'cuti',
                    'izin' => 'izin',
                    'sakit' => 'sakit',
                    default => $approvedLeave->type,
                };

                $validator->errors()->add(
                    'attendance',
                    "Anda tidak dapat melakukan check-in karena hari ini Anda sedang {$type}."
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Cek attendance hari ini
            |--------------------------------------------------------------------------
            */

            $attendance = Attendance::where(
                'employee_id',
                $employee->id
            )
                ->whereDate('date', $today)
                ->first();

            if (! $attendance) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Sudah berstatus izin / sakit / cuti
            |--------------------------------------------------------------------------
            */

            if (in_array($attendance->status, [
                'izin',
                'sakit',
                'cuti',
            ])) {

                $validator->errors()->add(
                    'attendance',
                    "Anda tidak dapat melakukan check-in karena status attendance hari ini adalah {$attendance->status}."
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Sudah check-in
            |--------------------------------------------------------------------------
            */

            if ($attendance->check_in) {

                $validator->errors()->add(
                    'attendance',
                    'Anda sudah melakukan check-in hari ini.'
                );
            }
        });
    }
}
