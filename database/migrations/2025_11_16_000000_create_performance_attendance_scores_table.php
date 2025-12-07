<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('performance_attendance_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // YM format, e.g. 2025-11
            $table->string('month', 7);

            $table->unsignedInteger('working_days')->default(0);
            $table->unsignedInteger('late_count')->default(0);
            $table->unsignedInteger('early_leave_count')->default(0);
            $table->unsignedInteger('absence_days')->default(0);
            $table->unsignedInteger('perfect_days')->default(0);

            // Final numeric score
            $table->decimal('score', 5, 2)->default(0);

            // qualitative status: excellent / good / fair / poor
            $table->enum('status', ['excellent', 'good', 'fair', 'poor'])->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'month'], 'performance_scores_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_attendance_scores');
    }
};


