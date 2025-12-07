<?php

namespace App\Observers;

use App\Models\Company\AttendanceRecord;
use App\Services\PerformanceService;

class AttendanceRecordObserver
{
    protected $performanceService;

    public function __construct(PerformanceService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Handle the AttendanceRecord "created" event.
     */
    public function created(AttendanceRecord $attendanceRecord): void
    {
        // Update performance for the current month when a new attendance record is created
        $this->performanceService->updateCurrentMonthPerformance($attendanceRecord);
    }

    /**
     * Handle the AttendanceRecord "updated" event.
     */
    public function updated(AttendanceRecord $attendanceRecord): void
    {
        // Update performance if datetime changed
        if ($attendanceRecord->isDirty('datetime')) {
            $this->performanceService->updateCurrentMonthPerformance($attendanceRecord);
        }
    }

    /**
     * Handle the AttendanceRecord "deleted" event.
     */
    public function deleted(AttendanceRecord $attendanceRecord): void
    {
        // Update performance when a record is deleted
        if ($attendanceRecord->employee) {
            $month = $attendanceRecord->datetime->format('Y-m');
            $this->performanceService->calculateEmployeePerformance(
                $attendanceRecord->employee,
                $month
            );
        }
    }
}

