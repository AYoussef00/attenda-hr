<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'start_time',
        'end_time',
        'break_minutes',
        'late_grace_minutes',
        'overtime_after',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
        'break_minutes' => 'integer',
        'late_grace_minutes' => 'integer',
        'overtime_after' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company this shift belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }

    /**
     * Get all employees assigned to this shift
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Calculate total working hours
     */
    public function getTotalWorkingHours(): float
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        $totalMinutes = $start->diffInMinutes($end);
        $totalHours = ($totalMinutes - $this->break_minutes) / 60;
        
        return round($totalHours, 2);
    }
}
