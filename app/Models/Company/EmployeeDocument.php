<?php

namespace App\Models\Company;

use App\Models\Admin\Company as AdminCompany;
use App\Models\Admin\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'title',
        'document_type_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_type',
        'file_size',
        'issued_date',
        'expiry_date',
        'uploaded_by',
        'note',
        'status',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the employee that owns this document
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the company that owns this document
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(AdminCompany::class);
    }

    /**
     * Get the user who uploaded this document
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the document type
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
