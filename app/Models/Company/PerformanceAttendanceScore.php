<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceAttendanceScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'month',
        'working_days',
        'late_count',
        'early_leave_count',
        'absence_days',
        'perfect_days',
        'score',
        'status',
    ];

    protected $casts = [
        'working_days' => 'integer',
        'late_count' => 'integer',
        'early_leave_count' => 'integer',
        'absence_days' => 'integer',
        'perfect_days' => 'integer',
        'score' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\Company::class);
    }
}


