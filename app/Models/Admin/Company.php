<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'email',
        'phone',
        'address',
        'attendance_methods',
        'ip_whitelist',
        'settings',
        'status',
    ];

    protected $casts = [
        'attendance_methods' => 'array',
        'ip_whitelist' => 'array',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all users belonging to this company
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all subscriptions for this company
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class);
    }

    /**
     * Get the active subscription for this company
     */
    public function activeSubscription(): ?CompanySubscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latest()
            ->first();
    }

    /**
     * Get all activity logs for this company
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
