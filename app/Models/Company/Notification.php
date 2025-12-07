<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'title',
        'message',
        'type',
        'document_type_id',
        'read',
        'created_by',
    ];

    protected $casts = [
        'read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the employee that owns this notification
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the company that owns this notification
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }

    /**
     * Get the user who created this notification
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the document type (if notification is about a document)
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
