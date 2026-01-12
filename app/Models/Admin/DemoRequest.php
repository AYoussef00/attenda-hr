<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'business_email',
        'company_name',
        'phone_number',
        'number_of_employees',
        'company_headquarters',
        'choose_time_slot',
        'status',
        'notes',
        'handled_by',
        'contacted_at',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin user who handled this request
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
