<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company this department belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }

    /**
     * Get all employees in this department
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
