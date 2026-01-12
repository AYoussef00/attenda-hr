<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Models\Company\Employee;
use App\Models\Company\PerformanceAttendanceScore;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PerformanceController extends ApiController
{
    /**
     * Resolve the authenticated employee for API requests.
     */
    protected function resolveEmployee(Request $request): ?Employee
    {
        $user = $request->user();

        if (! $user || ! $user->company || ! $user->isEmployee() || ! $user->isActive()) {
            return null;
        }

        return Employee::where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->first();
    }

    /**
     * Performance history for the authenticated employee.
     *
     * Returns one row per month with:
     * - month (Y-m)
     * - month_formatted (e.g. "November 2025")
     * - working_days, perfect_days, late_count, early_leave_count, absence_days
     * - score (0–100) and status (excellent / good / fair / poor)
     */
    public function history(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $company = $employee->company;

        $limit = (int) $request->input('limit', 12);
        if ($limit <= 0 || $limit > 24) {
            $limit = 12;
        }

        $scores = PerformanceAttendanceScore::where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('working_days', '>', 0)
            ->orderBy('month', 'desc')
            ->limit($limit)
            ->get();

        $items = $scores->map(function (PerformanceAttendanceScore $score) {
            $month = Carbon::createFromFormat('Y-m', $score->month);

            return [
                'month' => $score->month,
                'month_formatted' => $month->format('F Y'),
                'working_days' => $score->working_days,
                'late_count' => $score->late_count,
                'early_leave_count' => $score->early_leave_count,
                'absence_days' => $score->absence_days,
                'perfect_days' => $score->perfect_days,
                'score' => $score->score,
                'status' => $score->status,
            ];
        })->values();

        $averageScore = $scores->avg('score');

        return $this->success([
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
            ],
            'months' => $items,
            'average_score' => $averageScore,
        ]);
    }
}


