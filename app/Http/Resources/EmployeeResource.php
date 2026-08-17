<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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

            'employee_number' => $this->employee_number,

            'full_name' => $this->full_name,

            'phone' => $this->phone,

            'address' => $this->address,

            'join_date' => $this->join_date?->format('Y-m-d'),

            'status' => $this->status,

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                    'email' => $this->user?->email,
                    'role' => $this->user?->role,
                ];
            }),

            'department' => $this->whenLoaded('department', function () {
                return $this->department
                    ? [
                        'id' => $this->department->id,
                        'name' => $this->department->name,
                    ]
                    : null;
            }),

            'position' => $this->whenLoaded('position', function () {
                return $this->position
                    ? [
                        'id' => $this->position->id,
                        'name' => $this->position->name,
                    ]
                    : null;
            }),

            'work_schedule' => $this->whenLoaded('workSchedule', function () {
                return $this->workSchedule
                    ? [
                        'id' => $this->workSchedule->id,
                        'name' => $this->workSchedule->name,
                        'start_time' => $this->workSchedule->start_time,
                        'end_time' => $this->workSchedule->end_time,
                    ]
                    : null;
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}
