<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'month',
        'status',
        'generated_at',
        'paid_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company this payroll cycle belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }

    /**
     * Get all payroll entries for this cycle
     */
    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class, 'cycle_id');
    }
}
