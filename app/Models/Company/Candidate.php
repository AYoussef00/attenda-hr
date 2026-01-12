<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'name',
        'email',
        'phone',
        'title',
        'skills',
        'experience_years',
        'resume_path',
        'match_percentage',
        'status',
    ];

    protected $casts = [
        'skills' => 'array',
        'experience_years' => 'integer',
        'match_percentage' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the job this candidate belongs to
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
