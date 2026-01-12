<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Company\Employee;
use App\Models\Company\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DocumentController extends Controller
{
    /**
     * Display the employee documents page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->with(['user'])
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        // Get employee documents
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
            ->values()
            ->toArray();

        // Get all available document types for this company (default + company specific)
        $availableDocumentTypes = \App\Models\Company\DocumentType::forCompany($company->id)
            ->orderBy('order')
            ->get()
            ->map(function ($type) {
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

        // Get document type IDs that the employee already has
        $employeeDocumentTypeIds = collect($documents)
            ->pluck('document_type_id')
            ->filter()
            ->unique()
            ->toArray();

        // Get missing document types (types that employee doesn't have)
        $missingDocumentTypes = collect($availableDocumentTypes)
            ->filter(function ($type) use ($employeeDocumentTypeIds) {
                return !in_array($type['id'], $employeeDocumentTypeIds);
            })
            ->values()
            ->toArray();

        return Inertia::render('Employee/Documents/Index', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name ?? 'N/A',
                'employee_code' => $employee->employee_code,
            ],
            'documents' => $documents,
            'missingDocumentTypes' => $missingDocumentTypes,
            'availableDocumentTypes' => $availableDocumentTypes,
        ]);
    }

    /**
     * Store a newly uploaded document.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to any company.');
        }

        // Get employee record
        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        if (!$employee) {
            abort(403, 'Employee record not found.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_type_id' => ['nullable', 'exists:document_types,id'],
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
        if ($validated['expiry_date']) {
            $expiryDate = \Carbon\Carbon::parse($validated['expiry_date']);
            if ($expiryDate->isPast()) {
                $status = 'expired';
            }
        }

        // Create document record
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'title' => $validated['title'],
            'document_type_id' => $validated['document_type_id'] ?? null,
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'file_type' => $fileType,
            'file_size' => $file->getSize(),
            'issued_date' => $validated['issued_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'uploaded_by' => $user->id,
            'note' => $validated['note'] ?? null,
            'status' => $status,
        ]);

        return redirect()->route('employee.documents.index')
            ->with('success', 'Document uploaded successfully.');
    }
}
