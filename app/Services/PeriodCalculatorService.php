<?php

namespace App\Services;

use Carbon\Carbon;

class PeriodCalculatorService
{
    private const LUTEAL_PHASE_DAYS = 14; // Standard luteal phase duration
    private const FERTILE_WINDOW_BEFORE_OVULATION = 5; // Fertile window starts 5 days before ovulation
    private const HIGH_CHANCE_DAYS_BEFORE_OVULATION = 2; // High chance period

    /**
     * Calculate all period-related predictions
     *
     * @param int $cycleLength
     * @param int $periodDuration
     * @param string $lastPeriodDate
     * @param int $age
     * @return array
     */
    public function calculate(int $cycleLength, int $periodDuration, string $lastPeriodDate, int $age)
    {
        $today = Carbon::today();
        $lastPeriod = Carbon::parse($lastPeriodDate);
        
      

        // Calculate key dates
        $ovulationDate = $this->calculateOvulationDate($lastPeriod, $cycleLength);
        $nextPeriodDate = $this->calculateNextPeriodDate($lastPeriod, $cycleLength);
        $fertileWindow = $this->calculateFertileWindow($ovulationDate);
        $highChanceWindow = $this->calculateHighChanceWindow($ovulationDate);


        // Current cycle day
        $currentCycleDay = $lastPeriod->diffInDays($today) + 1;

        // Today's status
        $todayStatus = $this->getTodayStatus(
            $today,
            $lastPeriod,
            $periodDuration,
            $ovulationDate,
            $fertileWindow,
            $currentCycleDay
        );

        // Predictions
        $predictions = $this->generatePredictions(
            $today,
            $nextPeriodDate,
            $ovulationDate,
            $fertileWindow,
            $highChanceWindow
        );

        // Cycle info
        $cycleInfo = [
            'current_cycle_started' => $lastPeriod->format('Y-m-d'),
            'current_cycle_day' => $currentCycleDay,
            'cycle_length' => $cycleLength,
            'period_duration' => $periodDuration,
        ];

        return [
            'today' => $todayStatus,
            'predictions' => $predictions,
            'cycle_info' => $cycleInfo,
        ];
    }

    /**
     * Calculate ovulation date
     * Formula: Last Period Date + (Cycle Length - Luteal Phase)
     */
    private function calculateOvulationDate(Carbon $lastPeriod, int $cycleLength): Carbon
    {
        $daysToOvulation = $cycleLength - self::LUTEAL_PHASE_DAYS;
        return $lastPeriod->copy()->addDays($daysToOvulation);
    }

    /**
     * Calculate next period date
     * Formula: Last Period Date + Cycle Length
     */
    private function calculateNextPeriodDate(Carbon $lastPeriod, int $cycleLength): Carbon
    {
        return $lastPeriod->copy()->addDays($cycleLength);
    }

    /**
     * Calculate fertile window
     * Starts 5 days before ovulation, ends on ovulation day
     */
    private function calculateFertileWindow(Carbon $ovulationDate): array
    {
        return [
            'start' => $ovulationDate->copy()->subDays(self::FERTILE_WINDOW_BEFORE_OVULATION),
            'end' => $ovulationDate->copy(),
        ];
    }

    /**
     * Calculate high chance window
     * 2 days before ovulation to ovulation day
     */
    private function calculateHighChanceWindow(Carbon $ovulationDate): array
    {
        return [
            'start' => $ovulationDate->copy()->subDays(self::HIGH_CHANCE_DAYS_BEFORE_OVULATION),
            'end' => $ovulationDate->copy(),
        ];
    }

    /**
     * Get today's status
     */
    private function getTodayStatus(
        Carbon $today,
        Carbon $lastPeriod,
        int $periodDuration,
        Carbon $ovulationDate,
        array $fertileWindow,
        int $currentCycleDay
    ): array {
        $isPeriodDay = $this->isPeriodDay($today, $lastPeriod, $periodDuration);
        $isFertileDay = $this->isFertileDay($today, $fertileWindow);
        $isOvulationDay = $today->isSameDay($ovulationDate);
        $isSafeDay = !$isPeriodDay && !$isFertileDay;

        return [
            'date' => $today->format('Y-m-d'),
            'is_period_day' => $isPeriodDay,
            'is_fertile_day' => $isFertileDay,
            'is_ovulation_day' => $isOvulationDay,
            'is_safe_day' => $isSafeDay,
            'cycle_day' => $currentCycleDay,
        ];
    }

    /**
     * Check if today is a period day
     */
    private function isPeriodDay(Carbon $today, Carbon $lastPeriod, int $periodDuration): bool
    {
        $periodEnd = $lastPeriod->copy()->addDays($periodDuration - 1);
        return $today->between($lastPeriod, $periodEnd);
    }

    /**
     * Check if today is a fertile day
     */
    private function isFertileDay(Carbon $today, array $fertileWindow): bool
    {
        return $today->between($fertileWindow['start'], $fertileWindow['end']);
    }

    /**
     * Generate predictions
     */
    private function generatePredictions(
        Carbon $today,
        Carbon $nextPeriodDate,
        Carbon $ovulationDate,
        array $fertileWindow,
        array $highChanceWindow
    ): array {
        // Next period prediction
        $daysUntilPeriod = $today->diffInDays($nextPeriodDate, false);
        $nextPeriod = [
            'date' => $nextPeriodDate->format('Y-m-d'),
            'days_until' => $daysUntilPeriod,
            'confidence' => $this->calculateConfidence(),
        ];

        // Ovulation prediction
        $daysUntilOvulation = $today->diffInDays($ovulationDate, false);
        $ovulationStatus = $this->getOvulationStatus($today, $ovulationDate, $highChanceWindow);
        $ovulation = [
            'date' => $ovulationDate->format('Y-m-d'),
            'days_until' => $daysUntilOvulation,
            'status' => $ovulationStatus['status'],
        ];

        if (isset($ovulationStatus['fertility_status'])) {
            $ovulation['fertility_status'] = $ovulationStatus['fertility_status'];
        }

        // Fertile window prediction
        $fertileWindowStatus = $this->getFertileWindowStatus($today, $fertileWindow, $highChanceWindow);

        return [
            'next_period' => $nextPeriod,
            'ovulation' => $ovulation,
            'fertile_window' => $fertileWindowStatus,
        ];
    }

    /**
     * Get ovulation status
     */
    private function getOvulationStatus(Carbon $today, Carbon $ovulationDate, array $highChanceWindow): array
    {
        if ($today->isSameDay($ovulationDate)) {
            return [
                'status' => 'today',
                'fertility_status' => 'peak_fertility',
            ];
        } elseif ($today->lt($ovulationDate)) {
            $status = [
                'status' => 'upcoming',
            ];

            // Check if in high chance window
            if ($today->between($highChanceWindow['start'], $highChanceWindow['end'])) {
                $status['fertility_status'] = 'high_chance_active';
            } elseif ($today->lt($highChanceWindow['start'])) {
                $status['fertility_status'] = 'high_chance_approaching';
            }

            return $status;
        } else {
            return [
                'status' => 'passed',
            ];
        }
    }

    /**
     * Get fertile window status
     */
    private function getFertileWindowStatus(Carbon $today, array $fertileWindow, array $highChanceWindow): array
    {
        $start = $fertileWindow['start'];
        $end = $fertileWindow['end'];

        $status = [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];

        if ($today->lt($start)) {
            $status['status'] = 'upcoming';
            $status['days_until_start'] = $today->diffInDays($start);
        } elseif ($today->between($start, $end)) {
            $status['status'] = 'active';
            $status['days_remaining'] = $today->diffInDays($end);

            // Determine fertility level
            if ($today->between($highChanceWindow['start'], $highChanceWindow['end'])) {
                $status['fertility_level'] = 'high';
            } else {
                $status['fertility_level'] = 'medium';
            }
        } else {
            $status['status'] = 'passed';
        }

        return $status;
    }

    /**
     * Calculate confidence level
     * This can be improved with historical data
     */
    private function calculateConfidence(): float
    {
        // For now, return a static confidence
        // In a real application, this would be calculated based on:
        // - Cycle regularity
        // - Historical accuracy
        // - Number of recorded cycles
        return 0.85;
    }

    /**
     * Generate calendar data for a date range (optional feature)
     */
    public function generateCalendar(Carbon $startDate, Carbon $endDate, array $calculationData): array
    {
        $calendar = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $calendar[] = [
                'date' => $current->format('Y-m-d'),
                'day_of_week' => $current->format('l'),
                'is_period_day' => $this->isPeriodDay(
                    $current,
                    Carbon::parse($calculationData['cycle_info']['current_cycle_started']),
                    $calculationData['cycle_info']['period_duration']
                ),
                'is_fertile_day' => $current->between(
                    Carbon::parse($calculationData['predictions']['fertile_window']['start']),
                    Carbon::parse($calculationData['predictions']['fertile_window']['end'])
                ),
                'is_ovulation_day' => $current->isSameDay(
                    Carbon::parse($calculationData['predictions']['ovulation']['date'])
                ),
            ];

            $current->addDay();
        }

        return $calendar;
    }
}