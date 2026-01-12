<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('title'); // اسم المستند
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete(); // نوع المستند
            $table->string('file_path'); // مسار الملف في التخزين
            $table->enum('file_type', ['pdf', 'image', 'docx'])->nullable(); // نوع الملف
            $table->date('issued_date')->nullable(); // تاريخ الإصدار
            $table->date('expiry_date')->nullable(); // تاريخ الانتهاء (اختياري)
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete(); // ID الشخص اللي رفع الملف
            $table->text('note')->nullable(); // ملاحظات
            $table->enum('status', ['active', 'expired'])->default('active'); // حالة المستند
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
