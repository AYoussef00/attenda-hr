<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Models\Company\DocumentType;
use App\Models\Company\Employee;
use App\Models\Company\EmployeeDocument;
use Illuminate\Http\Request;

class DocumentController extends ApiController
{
    /**
     * Helper to resolve the authenticated employee.
     */
    protected function resolveEmployee(Request $request): ?Employee
    {
        $user = $request->user();

        if (! $user || ! $user->company || ! $user->isEmployee() || ! $user->isActive()) {
            return null;
        }

        return Employee::where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->with('user')
            ->first();
    }

    /**
     * Get uploaded documents for the authenticated employee.
     */
    public function uploaded(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $documents = $employee->documents()
            ->with(['documentType', 'uploadedBy'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'document_type_id' => $document->document_type_id,
                    'type' => $document->documentType ? [
                        'id' => $document->documentType->id,
                        'name_ar' => $document->documentType->name_ar,
                        'name_en' => $document->documentType->name_en,
                        'slug' => $document->documentType->slug,
                    ] : null,
                    'file_path' => $document->file_path,
                    'file_type' => $document->file_type,
                    'issued_date' => $document->issued_date?->format('Y-m-d'),
                    'expiry_date' => $document->expiry_date?->format('Y-m-d'),
                    'uploaded_by' => $document->uploaded_by,
                    'uploaded_by_name' => $document->uploadedBy->name ?? 'N/A',
                    'note' => $document->note,
                    'status' => $document->status,
                    'created_at' => $document->created_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->values();

        return $this->success([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name ?? 'N/A',
                'employee_code' => $employee->employee_code,
            ],
            'documents' => $documents,
        ]);
    }

    /**
     * Get required (missing) documents for the authenticated employee.
     *
     * Required documents = document types defined for the company
     * that the employee does NOT have yet.
     */
    public function required(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $company = $employee->company;

        if (! $company) {
            return $this->error('Employee does not belong to any company.', 403);
        }

        // All available document types for this company (default + company specific)
        $availableDocumentTypes = DocumentType::forCompany($company->id)
            ->orderBy('order')
            ->get()
            ->map(function (DocumentType $type) {
                return [
                    'id' => $type->id,
                    'name_ar' => $type->name_ar,
                    'name_en' => $type->name_en,
                    'slug' => $type->slug,
                    'is_default' => $type->is_default,
                ];
            })
            ->values()
            ->toArray();

        // Type IDs that the employee already has
        $employeeDocumentTypeIds = $employee->documents()
            ->pluck('document_type_id')
            ->filter()
            ->unique()
            ->toArray();

        // Missing types = required documents
        $missingDocumentTypes = collect($availableDocumentTypes)
            ->filter(function ($type) use ($employeeDocumentTypeIds) {
                return ! in_array($type['id'], $employeeDocumentTypeIds);
            })
            ->values()
            ->toArray();

        return $this->success([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name ?? 'N/A',
                'employee_code' => $employee->employee_code,
            ],
            'required_documents' => $missingDocumentTypes,
            'available_document_types' => $availableDocumentTypes,
        ]);
    }

    /**
     * Upload a document for a given document type (used for required documents).
     */
    public function upload(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        if (! $employee || ! $employee->isActive()) {
            return $this->error('Employee not found or inactive.', 403);
        }

        $company = $employee->company;

        if (! $company) {
            return $this->error('Employee does not belong to any company.', 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_type_id' => ['required', 'exists:document_types,id'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'], // 10MB max
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        // Handle file upload
        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('employee_documents', $fileName, 'public');

        // Determine file type
        $mimeType = $file->getMimeType();
        $fileType = null;
        if (str_starts_with($mimeType, 'image/')) {
            $fileType = 'image';
        } elseif ($mimeType === 'application/pdf') {
            $fileType = 'pdf';
        } elseif (in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            $fileType = 'docx';
        }

        // Determine status based on expiry date
        $status = 'active';
        if (! empty($validated['expiry_date'])) {
            $expiryDate = \Carbon\Carbon::parse($validated['expiry_date']);
            if ($expiryDate->isPast()) {
                $status = 'expired';
            }
        }

        // Create document record
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'title' => $validated['title'],
            'document_type_id' => $validated['document_type_id'],
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'file_type' => $fileType,
            'file_size' => $file->getSize(),
            'issued_date' => $validated['issued_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'uploaded_by' => $request->user()->id,
            'note' => $validated['note'] ?? null,
            'status' => $status,
        ]);

        $document->load(['documentType', 'uploadedBy']);

        return $this->success([
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'document_type_id' => $document->document_type_id,
                'type' => $document->documentType ? [
                    'id' => $document->documentType->id,
                    'name_ar' => $document->documentType->name_ar,
                    'name_en' => $document->documentType->name_en,
                    'slug' => $document->documentType->slug,
                ] : null,
                'file_path' => $document->file_path,
                'file_type' => $document->file_type,
                'issued_date' => $document->issued_date?->format('Y-m-d'),
                'expiry_date' => $document->expiry_date?->format('Y-m-d'),
                'uploaded_by' => $document->uploaded_by,
                'uploaded_by_name' => $document->uploadedBy->name ?? 'N/A',
                'note' => $document->note,
                'status' => $document->status,
                'created_at' => $document->created_at?->format('Y-m-d H:i:s'),
            ],
        ], 'Document uploaded successfully.');
    }
}


