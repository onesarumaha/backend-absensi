<?php

namespace App\Http\Requests\LeaveRequest;

use App\Models\Attendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProcessLeaveRequestRequest extends FormRequest
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
            //
        ];
    }

     public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $leaveRequest = $this->route('leaveRequest');

            if (! $leaveRequest) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Cek status pengajuan
            |--------------------------------------------------------------------------
            */

            if ($leaveRequest->status !== 'pending') {
                $validator->errors()->add(
                    'status',
                    'Pengajuan ini sudah diproses.'
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Cek apakah sudah ada check-in
            |--------------------------------------------------------------------------
            */

            $hasCheckIn = Attendance::where(
                'employee_id',
                $leaveRequest->employee_id
            )
                ->whereBetween('date', [
                    $leaveRequest->start_date,
                    $leaveRequest->end_date,
                ])
                ->whereNotNull('check_in')
                ->exists();

            if ($hasCheckIn) {
                $validator->errors()->add(
                    'leave_request',
                    'Pengajuan tidak dapat disetujui karena pegawai sudah melakukan check-in pada salah satu tanggal pengajuan.'
                );
            }
        });
    }
}
