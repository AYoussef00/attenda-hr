<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'assign_date',
        'return_date',
        'condition_on_assign',
        'condition_on_return',
    ];

    protected $casts = [
        'assign_date' => 'date',
        'return_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the asset for this assignment.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the employee for this assignment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check if assignment is active (not returned).
     */
    public function isActive(): bool
    {
        return $this->return_date === null;
    }
}
