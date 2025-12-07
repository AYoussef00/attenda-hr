<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'employee_id',
        'basic_salary',
        'total_allowances',
        'total_overtime_amount',
        'total_deductions',
        'attendance_deductions',
        'leave_deductions',
        'manual_deductions',
        'fixed_deductions',
        'net_salary',
        'notes',
        'status',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_overtime_amount' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'attendance_deductions' => 'decimal:2',
        'leave_deductions' => 'decimal:2',
        'manual_deductions' => 'decimal:2',
        'fixed_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the payroll cycle this entry belongs to
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PayrollCycle::class, 'cycle_id');
    }

    /**
     * Get the employee this entry belongs to
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
