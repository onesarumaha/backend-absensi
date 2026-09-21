<?php

namespace App\Http\Requests\Attendance;

use App\Http\Requests\Concerns\ValidatesRadius;
use App\Http\Requests\Concerns\VerifiesFace;
use App\Models\Attendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    use VerifiesFace, ValidatesRadius;

    public function authorize(): bool
    {
        return true;
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
                    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $value)) {
                        $fail('Format foto tidak valid. Harus berupa gambar.');
                    }

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
            if (!$user) return;

            $employee = $user->employee;

            if (!$employee) {
                $validator->errors()->add(
                    'employee',
                    'User belum terhubung dengan data pegawai.'
                );
                return;
            }

            // ✅ VALIDASI RADIUS
            $radiusError = $this->validateRadius($this->latitude, $this->longitude);
            if ($radiusError) {
                $validator->errors()->add('radius', $radiusError);
                return;
            }

            // ✅ VERIFIKASI WAJAH
            if ($this->filled('photo')) {
                $faceError = $this->verifyFace($this->photo);
                if ($faceError) {
                    $validator->errors()->add('face', $faceError);
                    return;
                }
            }

            // ===== ✅ VERIFIKASI WAJAH =====
            if ($this->filled('photo')) {
                $faceError = $this->verifyFace($this->photo);
                if ($faceError) {
                    $validator->errors()->add('face', $faceError);
                    return;
                }
            }

            $today = now()->toDateString();

            $approvedLeave = \App\Models\LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first();

            if ($approvedLeave) {
                $validator->errors()->add(
                    'attendance',
                    "Anda tidak dapat check-out karena hari ini Anda sedang {$approvedLeave->type}."
                );
                return;
            }

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->first();

            if (!$attendance || !$attendance->check_in) {
                $validator->errors()->add(
                    'attendance',
                    'Anda belum melakukan check-in hari ini.'
                );
                return;
            }

            if ($attendance->check_out) {
                $validator->errors()->add(
                    'attendance',
                    'Anda sudah melakukan check-out hari ini.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Foto wajah wajib diambil untuk check-out.',
            'latitude.required' => 'Lokasi latitude wajib diisi.',
            'longitude.required' => 'Lokasi longitude wajib diisi.',
        ];
    }
}
