<?php

namespace App\Http\Requests\api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
       $employee = $this->route('employee');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($employee?->user_id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'employee_number' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('employees', 'employee_number')
                    ->ignore($employee?->id),
            ],

            'full_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'department_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:departments,id',
            ],

            'position_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:positions,id',
            ],

            'work_schedule_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:work_schedules,id',
            ],

            'join_date' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'status' => [
                'sometimes',
                'in:active,inactive',
            ],
        ];
    }
}
