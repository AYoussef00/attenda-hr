<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'type',
        'datetime',
        'method',
        'ip_address',
        'wifi_ssid',
        'lat',
        'lon',
        'device_info',
        'meta',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'lat' => 'decimal:8',
        'lon' => 'decimal:8',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the employee this record belongs to
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the company this record belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }

    /**
     * Check if record is check-in
     */
    public function isCheckIn(): bool
    {
        return $this->type === 'in';
    }

    /**
     * Check if record is check-out
     */
    public function isCheckOut(): bool
    {
        return $this->type === 'out';
    }

    /**
     * Scope for check-in records
     */
    public function scopeCheckIns($query)
    {
        return $query->where('type', 'in');
    }

    /**
     * Scope for check-out records
     */
    public function scopeCheckOuts($query)
    {
        return $query->where('type', 'out');
    }

    /**
     * Scope for today's records
     */
    public function scopeToday($query)
    {
        return $query->whereDate('datetime', today());
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('datetime', [$startDate, $endDate]);
    }
}
