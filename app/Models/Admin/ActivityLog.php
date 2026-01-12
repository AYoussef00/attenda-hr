<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'action',
        'model',
        'model_id',
        'before_data',
        'after_data',
        'ip',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the user who performed this action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company this log belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($log) {
            $log->created_at = now();
        });
    }

    /**
     * Get the model instance related to this log
     */
    public function getModelInstance()
    {
        if (!$this->model || !$this->model_id) {
            return null;
        }

        $modelClass = $this->model;
        if (!class_exists($modelClass)) {
            return null;
        }

        return $modelClass::find($this->model_id);
    }
}
