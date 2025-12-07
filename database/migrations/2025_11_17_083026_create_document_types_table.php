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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar'); // الاسم بالعربي
            $table->string('name_en'); // الاسم بالإنجليزي
            $table->string('slug')->unique(); // slug للاستخدام في الكود
            $table->boolean('is_default')->default(false); // هل هو نوع افتراضي
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete(); // null يعني global/default
            $table->integer('order')->default(0); // ترتيب العرض
            $table->boolean('is_active')->default(true); // نشط/غير نشط
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
