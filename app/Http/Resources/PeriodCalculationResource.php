<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodCalculationResource extends JsonResource
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
            'cycle_length' => $this->cycle_length,
            'period_duration' => $this->period_duration,
            'last_period_date' => $this->last_period_date->format('Y-m-d'),
            'age' => $this->age,
            'calculated_ovulation_date' => $this->calculated_ovulation_date?->format('Y-m-d'),
            'calculated_next_period_date' => $this->calculated_next_period_date?->format('Y-m-d'),
            'calculation_data' => $this->calculation_data,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
