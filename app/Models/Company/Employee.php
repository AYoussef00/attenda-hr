<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'employee_code',
        'national_id',
        'position',
        'department_id',
        'hire_date',
        'contract_type',
        'shift_id',
        'barcode',
        'qr_secret',
        'device_id',
        'status',
        'basic_salary',
        'hourly_rate',
        'overtime_rate',
        'allowances_fixed',
        'deductions_fixed',
        'working_hours_per_day',
        'working_days_per_month',
        'bank_name',
        'bank_account_number',
        'iban',
        'salary_release_day',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'allowances_fixed' => 'array',
        'deductions_fixed' => 'array',
        'working_hours_per_day' => 'integer',
        'working_days_per_month' => 'integer',
        'salary_release_day' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user associated with this employee
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company this employee belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }

    /**
     * Get the department this employee belongs to
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the shift assigned to this employee
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get all attendance records for this employee
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Get all leave requests for this employee
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get all documents for this employee
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * Get all notifications for this employee
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get all payroll entries for this employee
     */
    public function payrollEntries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    /**
     * Get all manual deductions for this employee
     */
    public function manualDeductions(): HasMany
    {
        return $this->hasMany(PayrollManualDeduction::class);
    }

    /**
     * Check if employee is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if employee is terminated
     */
    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }
}
