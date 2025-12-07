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
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->boolean('attendance_bonus_enabled')->default(false)->after('missing_punch_handling')->comment('Enable attendance bonus');
            $table->enum('attendance_bonus_type', ['fixed_amount', 'percentage', 'per_day'])->nullable()->after('attendance_bonus_enabled')->comment('Type of attendance bonus');
            $table->decimal('attendance_bonus_amount', 10, 2)->nullable()->after('attendance_bonus_type')->comment('Fixed amount or percentage value');
            $table->enum('attendance_bonus_condition', ['perfect_attendance', 'no_late', 'no_absence', 'custom_days'])->nullable()->after('attendance_bonus_amount')->comment('Condition to earn bonus');
            $table->integer('attendance_bonus_min_days')->nullable()->after('attendance_bonus_condition')->comment('Minimum days of attendance required (for custom_days condition)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_bonus_enabled',
                'attendance_bonus_type',
                'attendance_bonus_amount',
                'attendance_bonus_condition',
                'attendance_bonus_min_days',
            ]);
        });
    }
};
