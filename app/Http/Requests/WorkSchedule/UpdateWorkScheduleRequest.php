<?php

namespace App\Http\Requests\WorkSchedule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkScheduleRequest extends FormRequest
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
        $schedule = $this->route('work_schedule');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('work_schedules', 'name')
                    ->ignore($schedule->id),
            ],

            'start_time' => [
                'sometimes',
                'date_format:H:i',
            ],

            'end_time' => [
                'sometimes',
                'date_format:H:i',
                'after:start_time',
            ],
            'late_tolerance' => [
                'sometimes',
                'integer',
                'min:0',
                'max:120',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'radius_meters' => [
                'sometimes',
                'integer',
                'min:10',
                'max:10000',
            ],

            'location_name' => [
                'nullable',
                'string',
                'max:255',
            ],

        ];
    }
}
