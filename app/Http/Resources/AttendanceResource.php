<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'id' => $this->id,

            'date' => $this->date?->format('Y-m-d'),

            'check_in' => $this->check_in,
            'check_out' => $this->check_out,

            'status' => $this->status,

            'late_minutes' => $this->late_minutes,

            'check_in_location' => [
                'latitude' => $this->check_in_latitude,
                'longitude' => $this->check_in_longitude,
            ],

            'check_out_location' => [
                'latitude' => $this->check_out_latitude,
                'longitude' => $this->check_out_longitude,
            ],
            'check_in_photo' => $this->check_in_photo
                ? asset('storage/' . $this->check_in_photo)
                : null,

            'check_out_latitude' => $this->check_out_latitude,
            'check_out_longitude' => $this->check_out_longitude,
            'check_out_photo' => $this->check_out_photo
                ? asset('storage/' . $this->check_out_photo)
                : null,

            'notes' => $this->notes,

            'employee' => $this->whenLoaded(
                'employee',
                fn () => [
                    'id' => $this->employee->id,
                    'employee_number' => $this->employee->employee_number,
                    'full_name' => $this->employee->full_name,
                ]
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
