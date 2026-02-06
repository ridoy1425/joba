<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodRecord extends Model
{
    protected $fillable = [
        'user_id',
        'cycle_length',
        'period_duration',
        'last_period_date',
        'age',
        'calculated_ovulation_date',
        'calculated_next_period_date',
        'calculation_data',
    ];

    protected $casts = [
        'last_period_date' => 'date',
        'calculated_ovulation_date' => 'date',
        'calculated_next_period_date' => 'date',
        'calculation_data' => 'array',
    ];
}
