<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'asset_code',
        'type',
        'model',
        'serial_number',
        'purchase_date',
        'cost',
        'status',
        'warranty_end',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_end' => 'date',
        'cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns the asset.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }

    /**
     * Get all assignments for this asset.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    /**
     * Get the current active assignment.
     */
    public function currentAssignment()
    {
        return $this->assignments()
            ->whereNull('return_date')
            ->latest('assign_date')
            ->first();
    }

    /**
     * Get all maintenance records for this asset.
     */
    public function maintenance(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    /**
     * Check if asset is available for assignment.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'Available' && $this->currentAssignment() === null;
    }
}
