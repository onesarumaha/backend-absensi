<?php

namespace App\Http\Requests\Attendance;

use App\Http\Requests\Concerns\ValidatesRadius;
use App\Http\Requests\Concerns\VerifiesFace;
use App\Models\LeaveRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attendance;

class CheckInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
     use VerifiesFace, ValidatesRadius; 

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
            'photo' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Validasi format base64 image
                    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $value)) {
                        $fail('Format foto tidak valid. Harus berupa gambar (jpeg/png/webp).');
                    }

                    // Cek ukuran (max ~2MB)
                    $base64Part = preg_replace('/^data:image\/\w+;base64,/', '', $value);
                    $sizeInBytes = strlen(base64_decode($base64Part));
                    if ($sizeInBytes > 2 * 1024 * 1024) {
                        $fail('Ukuran foto terlalu besar. Maksimal 2MB.');
                    }
                },
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $user = $this->user();
            if (! $user) return;

            $employee = $user->employee;

            if (! $employee) {
                $validator->errors()->add(
                    'employee',
                    'User belum terhubung dengan data pegawai.'
                );
                return;
            }

            $radiusError = $this->validateRadius(
                $this->latitude,
                $this->longitude
            );
            if ($radiusError) {
                $validator->errors()->add('radius', $radiusError);
                return;
            }

            if ($this->filled('photo')) {
                $faceError = $this->verifyFace($this->photo);
                if ($faceError) {
                    $validator->errors()->add('face', $faceError);
                    return;
                }
            }

            $today = now()->toDateString();

            $approvedLeave = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first();

            if ($approvedLeave) {
                $type = $approvedLeave->type;
                $validator->errors()->add(
                    'attendance',
                    "Anda tidak dapat melakukan check-in karena hari ini Anda sedang {$type}."
                );
                return;
            }

            // Cek attendance hari ini
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->first();

            if (! $attendance) return;

            if (in_array($attendance->status, ['izin', 'sakit', 'cuti'])) {
                $validator->errors()->add(
                    'attendance',
                    "Anda tidak dapat check-in karena status attendance hari ini adalah {$attendance->status}."
                );
                return;
            }

            if ($attendance->check_in) {
                $validator->errors()->add(
                    'attendance',
                    'Anda sudah melakukan check-in hari ini.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Foto wajah wajib diambil untuk absen.',
            'latitude.required' => 'Lokasi latitude wajib diisi.',
            'longitude.required' => 'Lokasi longitude wajib diisi.',
        ];
    }
}
