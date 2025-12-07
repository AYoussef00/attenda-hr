<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        // General Settings
        'cycle_type',
        'salary_calculation_method',
        'overtime_multiplier',
        'currency',
        'default_salary_release_day',
        // Attendance & Deduction Settings
        'late_deduction_enabled',
        'late_grace_minutes',
        'late_calculation_unit',
        'absence_deduction_type',
        'absence_deduction_percentage',
        'absence_termination_days',
        'early_leave_deduction_enabled',
        'missing_punch_handling',
        // Attendance Bonus Settings
        'attendance_bonus_enabled',
        'attendance_bonus_type',
        'attendance_bonus_amount',
        'attendance_bonus_condition',
        'attendance_bonus_min_days',
        // Overtime Settings
        'overtime_enabled',
        'overtime_requires_approval',
        'overtime_normal_rate',
        'overtime_weekend_rate',
        'overtime_holiday_rate',
        'overtime_max_per_day',
        'overtime_max_per_month',
    ];

    protected $casts = [
        'late_deduction_enabled' => 'boolean',
        'early_leave_deduction_enabled' => 'boolean',
        'overtime_enabled' => 'boolean',
        'overtime_requires_approval' => 'boolean',
        'overtime_multiplier' => 'decimal:2',
        'overtime_normal_rate' => 'decimal:2',
        'overtime_weekend_rate' => 'decimal:2',
        'overtime_holiday_rate' => 'decimal:2',
        'absence_deduction_percentage' => 'decimal:2',
        'attendance_bonus_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company this setting belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }
}
