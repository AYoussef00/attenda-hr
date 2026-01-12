<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'skills',
        'auto_reject_min',
        'auto_reject_max',
        'auto_accept_min',
        'auto_accept_max',
    ];

    protected $casts = [
        'skills' => 'array',
        'auto_reject_min' => 'integer',
        'auto_reject_max' => 'integer',
        'auto_accept_min' => 'integer',
        'auto_accept_max' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all candidates for this job
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
