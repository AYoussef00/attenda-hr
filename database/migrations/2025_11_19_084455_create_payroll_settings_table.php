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
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // General Settings
            $table->enum('cycle_type', ['monthly', 'bi-weekly', 'weekly'])->default('monthly');
            $table->enum('salary_calculation_method', ['fixed_salary', 'daily_rate', 'hourly_rate'])->default('fixed_salary');
            $table->decimal('overtime_multiplier', 3, 2)->default(1.25)->comment('Default overtime multiplier (1.25x, 1.5x, 2x)');
            $table->string('currency', 10)->default('USD');
            $table->integer('default_salary_release_day')->default(27)->comment('Day of month for salary release (1-31)');
            
            // Attendance & Deduction Settings
            $table->boolean('late_deduction_enabled')->default(true);
            $table->integer('late_grace_minutes')->default(15)->comment('Minutes before late is counted');
            $table->enum('late_calculation_unit', ['hour', 'minute'])->default('minute');
            $table->enum('absence_deduction_type', ['full_day', 'percentage'])->default('full_day');
            $table->integer('absence_termination_days')->nullable()->comment('Days of absence before termination');
            $table->boolean('early_leave_deduction_enabled')->default(true);
            $table->enum('missing_punch_handling', ['deduct', 'ignore'])->default('deduct');
            
            // Overtime Settings
            $table->boolean('overtime_enabled')->default(true);
            $table->boolean('overtime_requires_approval')->default(false);
            $table->decimal('overtime_normal_rate', 3, 2)->default(1.25);
            $table->decimal('overtime_weekend_rate', 3, 2)->default(1.5);
            $table->decimal('overtime_holiday_rate', 3, 2)->default(2.0);
            $table->integer('overtime_max_per_day')->nullable()->comment('Max overtime hours per day');
            $table->integer('overtime_max_per_month')->nullable()->comment('Max overtime hours per month');
            
            $table->timestamps();
            
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
