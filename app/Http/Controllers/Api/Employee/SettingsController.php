<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Models\Company\Employee;
use Illuminate\Http\Request;

class SettingsController extends ApiController
{
    /**
     * Resolve the authenticated employee.
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
     * Return allowed IP addresses for the employee's company.
     *
     * Reads from:
     * - company.ip_whitelist (array column)
     * - optionally company.settings['attendance_ip_whitelist'] if present.
     */
    public function allowedIps(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $company = $employee->company;

        $ipWhitelist = $company->ip_whitelist ?? [];

        // Also check in settings array for a nested whitelist if used
        $settings = $company->settings ?? [];
        $settingsWhitelist = [];
        if (is_array($settings) && isset($settings['attendance_ip_whitelist'])) {
            $settingsWhitelist = $settings['attendance_ip_whitelist'] ?? [];
        }

        // Merge and make unique, filter out empty values
        $merged = collect($ipWhitelist)
            ->merge($settingsWhitelist)
            ->filter(fn ($ip) => ! empty($ip))
            ->unique()
            ->values()
            ->all();

        return $this->success([
            'company_id' => $company->id,
            'allowed_ips' => $merged,
        ]);
    }
}


