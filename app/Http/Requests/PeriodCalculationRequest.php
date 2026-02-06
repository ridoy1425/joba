<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class PeriodCalculationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $twoYearsAgo = now()->subYears(2)->format('Y-m-d');

        return [
            'cycle_length' => ['required', 'integer', 'min:21', 'max:45'],
            'period_duration' => ['required', 'integer', 'min:2', 'max:10'],
            'last_period_date' => ['required', 'date_format:Y-m-d', "before_or_equal:today", "after:$twoYearsAgo"],
            'age' => ['required', 'integer', 'min:10', 'max:60'],
        ];
    }

    /**
     * Custom messages for validation
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cycle_length.required' => 'Cycle length is required',
            'cycle_length.min' => 'Cycle length must be at least 21 days',
            'cycle_length.max' => 'Cycle length cannot exceed 45 days',
            'period_duration.required' => 'Period duration is required',
            'period_duration.min' => 'Period duration must be at least 2 days',
            'period_duration.max' => 'Period duration cannot exceed 10 days',
            'last_period_date.required' => 'Last period date is required',
            'last_period_date.date_format' => 'Last period date must be in Y-m-d format',
            'last_period_date.before_or_equal' => 'Last period date cannot be in the future',
            'last_period_date.after' => 'Last period date cannot be older than 2 years',
            'age.required' => 'Age is required',
            'age.min' => 'Age must be at least 10 years',
            'age.max' => 'Age cannot exceed 60 years',
        ];
    }
}