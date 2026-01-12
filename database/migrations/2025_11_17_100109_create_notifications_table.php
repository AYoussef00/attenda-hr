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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('general'); // general, document, leave, attendance
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->boolean('read')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // Who created the notification
            $table->timestamps();

            $table->index(['employee_id', 'read']);
            $table->index(['company_id', 'read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
