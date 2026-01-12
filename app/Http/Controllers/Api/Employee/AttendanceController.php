<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Models\Company\AttendanceRecord;
use App\Models\Company\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends ApiController
{
    /**
     * Helper to resolve the authenticated employee.
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
     * Check-in endpoint (login attendance).
     */
    public function checkIn(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $company = $employee->company;

        // Require explicit time from client (HH:mm:ss)
        $validated = $request->validate([
            'time' => ['required', 'date_format:H:i:s'],
        ]);

        $timeString = $validated['time'];

        // Build datetime from today's date + provided time in app timezone
        $today = Carbon::today(config('app.timezone'));
        $now = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $today->format('Y-m-d').' '.$timeString,
            config('app.timezone')
        );
        $date = $now->toDateString();

        // Prevent double check-in without check-out
        $existingCheckIn = AttendanceRecord::where('employee_id', $employee->id)
            ->where('type', 'in')
            ->whereDate('datetime', $date)
            ->latest('datetime')
            ->first();

        $existingCheckOut = AttendanceRecord::where('employee_id', $employee->id)
            ->where('type', 'out')
            ->whereDate('datetime', $date)
            ->latest('datetime')
            ->first();

        if ($existingCheckIn && ! $existingCheckOut) {
            return $this->error(
                'You have already checked in today. Please check out before checking in again.',
                409
            );
        }

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'type' => 'in',
            'datetime' => $now,
            'method' => 'manual',
            'ip_address' => $request->ip(),
            'lat' => null,
            'lon' => null,
            'device_info' => null,
            'meta' => [
                'source' => 'mobile_api',
            ],
        ]);

        return $this->success([
            'record' => [
                'id' => $record->id,
                'type' => $record->type,
                'datetime' => $record->datetime->toIso8601String(),
                'date' => $record->datetime->toDateString(),
                'time' => $record->datetime->format('H:i:s'),
                'method' => $record->method,
            ],
        ], 'Checked in successfully.');
    }

    /**
     * Check-out endpoint (logout attendance).
     */
    public function checkOut(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $company = $employee->company;

        $validated = $request->validate([
            'time' => ['required', 'date_format:H:i:s'],
        ]);

        $timeString = $validated['time'];

        $today = Carbon::today(config('app.timezone'));
        $now = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $today->format('Y-m-d').' '.$timeString,
            config('app.timezone')
        );
        $date = $now->toDateString();

        $todayCheckIn = AttendanceRecord::where('employee_id', $employee->id)
            ->where('type', 'in')
            ->whereDate('datetime', $date)
            ->latest('datetime')
            ->first();

        if (! $todayCheckIn) {
            return $this->error(
                'You must check in before checking out.',
                409
            );
        }

        $existingCheckOut = AttendanceRecord::where('employee_id', $employee->id)
            ->where('type', 'out')
            ->whereDate('datetime', $date)
            ->first();

        if ($existingCheckOut) {
            return $this->error(
                'You have already checked out today.',
                409
            );
        }

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'type' => 'out',
            'datetime' => $now,
            'method' => 'manual',
            'ip_address' => $request->ip(),
            'lat' => null,
            'lon' => null,
            'device_info' => null,
            'meta' => [
                'source' => 'mobile_api',
            ],
        ]);

        return $this->success([
            'record' => [
                'id' => $record->id,
                'type' => $record->type,
                'datetime' => $record->datetime->toIso8601String(),
                'date' => $record->datetime->toDateString(),
                'time' => $record->datetime->format('H:i:s'),
                'method' => $record->method,
            ],
        ], 'Checked out successfully.');
    }

    /**
     * Today's status endpoint.
     */
    public function today(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $today = Carbon::today();

        $checkIn = AttendanceRecord::where('employee_id', $employee->id)
            ->where('type', 'in')
            ->whereDate('datetime', $today)
            ->latest('datetime')
            ->first();

        $checkOut = AttendanceRecord::where('employee_id', $employee->id)
            ->where('type', 'out')
            ->whereDate('datetime', $today)
            ->latest('datetime')
            ->first();

        $status = 'not_checked_in';
        if ($checkIn && ! $checkOut) {
            $status = 'checked_in';
        } elseif ($checkIn && $checkOut) {
            $status = 'checked_out';
        }

        return $this->success([
            'status' => $status,
            'check_in' => $checkIn ? [
                'id' => $checkIn->id,
                'datetime' => $checkIn->datetime->toIso8601String(),
                'time' => $checkIn->datetime->format('H:i:s'),
            ] : null,
            'check_out' => $checkOut ? [
                'id' => $checkOut->id,
                'datetime' => $checkOut->datetime->toIso8601String(),
                'time' => $checkOut->datetime->format('H:i:s'),
            ] : null,
        ]);
    }

    /**
     * Recent attendance for this employee, grouped by date.
     *
     * Returns last N days (default 30) with:
     * - date
     * - check_in (time or null)
     * - check_out (time or null)
     * - duration_minutes
     * - duration_formatted (e.g. "8h 30m")
     */
    public function recent(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $days = (int) $request->input('days', 30);
        if ($days <= 0 || $days > 90) {
            $days = 30;
        }

        $fromDate = now()->subDays($days)->startOfDay();

        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->where('datetime', '>=', $fromDate)
            ->orderBy('datetime', 'desc')
            ->get()
            ->map(function (AttendanceRecord $record) {
                return [
                    'id' => $record->id,
                    'type' => $record->type,
                    'datetime' => $record->datetime,
                    'date' => $record->datetime->toDateString(),
                    'time' => $record->datetime->format('H:i:s'),
                ];
            });

        // Group by date and calculate duration per day
        $grouped = [];

        foreach ($records as $record) {
            $dateKey = $record['date'];

            if (! isset($grouped[$dateKey])) {
                $grouped[$dateKey] = [
                    'date' => $dateKey,
                    'check_in' => null,
                    'check_out' => null,
                    'duration_minutes' => null,
                    'duration_formatted' => null,
                ];
            }

            if ($record['type'] === 'in' && ! $grouped[$dateKey]['check_in']) {
                $grouped[$dateKey]['check_in'] = $record['time'];
            } elseif ($record['type'] === 'out') {
                // always take the last check-out seen in the loop
                $grouped[$dateKey]['check_out'] = $record['time'];
            }
        }

        // Compute duration
        foreach ($grouped as $dateKey => &$day) {
            if ($day['check_in'] && $day['check_out']) {
                $checkInTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey.' '.$day['check_in']);
                $checkOutTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey.' '.$day['check_out']);
                $duration = $checkInTime->diffInMinutes($checkOutTime);
                $day['duration_minutes'] = $duration;

                $hours = intdiv($duration, 60);
                $minutes = $duration % 60;

                if ($hours > 0) {
                    $day['duration_formatted'] = $hours.'h '.$minutes.'m';
                } else {
                    $day['duration_formatted'] = $minutes.'m';
                }
            }
        }
        unset($day);

        // Sort by date desc and reindex
        krsort($grouped);
        $items = array_values($grouped);

        return $this->success([
            'days' => $days,
            'items' => $items,
        ]);
    }
}


