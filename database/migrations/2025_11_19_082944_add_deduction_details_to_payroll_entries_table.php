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
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->decimal('attendance_deductions', 10, 2)->default(0)->after('total_deductions');
            $table->decimal('leave_deductions', 10, 2)->default(0)->after('attendance_deductions');
            $table->decimal('manual_deductions', 10, 2)->default(0)->after('leave_deductions');
            $table->decimal('fixed_deductions', 10, 2)->default(0)->after('manual_deductions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_deductions',
                'leave_deductions',
                'manual_deductions',
                'fixed_deductions',
            ]);
        });
    }
};
