<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use App\Models\Company\PerformanceAttendanceScore;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    /**
     * Show performance summary for the authenticated employee.
     *
     * One row per month (only months with working days / attendance),
     * including the final score percentage and basic breakdown.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403, 'User does not belong to any company.');
        }

        /** @var Employee|null $employee */
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        if (! $employee) {
            abort(403, 'Employee record not found.');
        }

        $scores = PerformanceAttendanceScore::where('company_id', $company->id)
            ->where('employee_id', $employee->id)
            ->where('working_days', '>', 0) // Only months where the employee actually had attendance
            ->orderBy('month', 'desc')
            ->get();

        $monthly = $scores->map(function (PerformanceAttendanceScore $score) {
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

        return Inertia::render('Employee/Performance/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'employee' => [
                'id' => $employee->id,
                'name' => $user->name,
                'employee_code' => $employee->employee_code,
            ],
            'performance' => [
                'monthly' => $monthly,
                'average_score' => $averageScore,
                'months_count' => $monthly->count(),
            ],
        ]);
    }
}


