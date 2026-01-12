<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'is_default',
        'company_id',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns this document type (null for global/default types)
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }

    /**
     * Get all documents of this type
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'document_type_id');
    }

    /**
     * Scope to get only default/global types
     */
    public function scopeDefault($query)
    {
        return $query->whereNull('company_id')->where('is_default', true);
    }

    /**
     * Scope to get types for a specific company (including defaults)
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->orWhere(function ($q2) {
                    $q2->whereNull('company_id')->where('is_default', true);
                });
        })->where('is_active', true);
    }
}
