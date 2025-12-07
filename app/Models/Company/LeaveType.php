<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'yearly_balance',
    ];

    protected $casts = [
        'yearly_balance' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company this leave type belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }

    /**
     * Get all leave requests for this leave type
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get approved leave requests for this leave type
     */
    public function approvedLeaveRequests(): HasMany
    {
        return $this->leaveRequests()->where('status', 'approved');
    }
}
