<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول دورات الرواتب
        Schema::create('payroll_cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('month', 7); // YYYY-MM
            $table->enum('status', ['draft','generated','approved','paid'])->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id','month']);
        });

        // جدول تفاصيل كل موظف في الدورة
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('basic_salary',10,2)->default(0);
            $table->decimal('total_allowances',10,2)->default(0);
            $table->decimal('total_overtime_amount',10,2)->default(0);
            $table->decimal('total_deductions',10,2)->default(0);
            $table->decimal('net_salary',10,2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status',['pending','approved','paid'])->default('pending');
            $table->timestamps();

            $table->foreign('cycle_id')->references('id')->on('payroll_cycles')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->unique(['cycle_id','employee_id']);
        });

        // جدول الخصومات اليدوية (اختياري)
        Schema::create('payroll_manual_deductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('company_id');
            $table->decimal('amount',10,2);
            $table->string('reason')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_manual_deductions');
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payroll_cycles');
    }
};
