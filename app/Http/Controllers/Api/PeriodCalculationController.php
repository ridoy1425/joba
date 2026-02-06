<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PeriodCalculationRequest;
use App\Http\Resources\PeriodCalculationResource;
use App\Models\PeriodRecord;
use App\Services\PeriodCalculatorService;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeriodCalculationController extends Controller
{
    private PeriodCalculatorService $calculatorService;

    public function __construct(PeriodCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
    }

    /**
     * Calculate period predictions
     *
     * @param PeriodCalculationRequest $request
     * @return JsonResponse
     */
    public function calculate(PeriodCalculationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Perform calculation
            $calculationResult = $this->calculatorService->calculate(
                cycleLength: $validated['cycle_length'],
                periodDuration: $validated['period_duration'],
                lastPeriodDate: $validated['last_period_date'],
                age: $validated['age']
            );

            // Optionally save to database for history tracking
            $periodRecord = $this->savePeriodRecord($validated, $calculationResult);

            // Return formatted response
            return response()->json([
                'success' => true,
                'data' => $calculationResult,
                'record_id' => $periodRecord->id ?? null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Calculation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get calculation history (optional feature)
     *
     * @return JsonResponse
     */
    public function history(): JsonResponse
    {
        try {
            // If you have user authentication, filter by user
            // $records = PeriodRecord::where('user_id', auth()->id())

            $records = PeriodRecord::orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => PeriodCalculationResource::collection($records),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific calculation by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $record = PeriodRecord::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new PeriodCalculationResource($record),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Generate calendar view (optional feature)
     *
     * @param PeriodCalculationRequest $request
     * @return JsonResponse
     */
    public function calendar(PeriodCalculationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Calculate period data
            $calculationResult = $this->calculatorService->calculate(
                cycleLength: $validated['cycle_length'],
                periodDuration: $validated['period_duration'],
                lastPeriodDate: $validated['last_period_date'],
                age: $validated['age']
            );

            // Generate calendar for next 3 months
            $startDate = Carbon::parse($validated['last_period_date']);
            $endDate = $startDate->copy()->addMonths(3);

            $calendar = $this->calculatorService->generateCalendar(
                $startDate,
                $endDate,
                $calculationResult
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'calendar' => $calendar,
                    'summary' => $calculationResult,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar generation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save period record to database
     *
     * @param array $validated
     * @param array $calculationResult
     * @return PeriodRecord
     */
    private function savePeriodRecord(array $validated, array $calculationResult): PeriodRecord
    {
        return PeriodRecord::create([
            'user_id' => Auth::id() ?? null, // If you have user authentication
            'cycle_length' => $validated['cycle_length'],
            'period_duration' => $validated['period_duration'],
            'last_period_date' => $validated['last_period_date'],
            'age' => $validated['age'],
            'calculated_ovulation_date' => $calculationResult['predictions']['ovulation']['date'],
            'calculated_next_period_date' => $calculationResult['predictions']['next_period']['date'],
            'calculation_data' => $calculationResult,
        ]);
    }

    /**
     * Delete a calculation record
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $record = PeriodRecord::findOrFail($id);
            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
