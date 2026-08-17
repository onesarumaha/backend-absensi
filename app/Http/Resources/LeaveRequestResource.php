<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
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

            'type' => $this->type,

            'start_date' => $this->start_date?->format('Y-m-d'),

            'end_date' => $this->end_date?->format('Y-m-d'),

            'reason' => $this->reason,

            'status' => $this->status,

            'approved_at' => $this->approved_at,

            'employee' => $this->whenLoaded(
                'employee',
                fn () => [
                    'id' => $this->employee->id,
                    'employee_number' =>
                        $this->employee->employee_number,
                    'full_name' =>
                        $this->employee->full_name,
                ]
            ),

            'approved_by' => $this->whenLoaded(
                'approvedBy',
                fn () => $this->approvedBy
                    ? [
                        'id' => $this->approvedBy->id,
                        'name' => $this->approvedBy->name,
                    ]
                    : null
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
