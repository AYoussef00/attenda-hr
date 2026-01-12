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
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('basic_salary', 10, 2)->nullable()->after('status');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('basic_salary');
            $table->decimal('overtime_rate', 10, 2)->default(1.25)->after('hourly_rate');
            $table->decimal('allowances_fixed', 10, 2)->default(0)->after('overtime_rate');
            $table->decimal('deductions_fixed', 10, 2)->default(0)->after('allowances_fixed');
            $table->integer('working_hours_per_day')->default(8)->after('deductions_fixed');
            $table->integer('working_days_per_month')->default(26)->after('working_hours_per_day');
            $table->string('bank_name')->nullable()->after('working_days_per_month');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('iban')->nullable()->after('bank_account_number');
            $table->integer('salary_release_day')->default(27)->after('iban');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'basic_salary',
                'hourly_rate',
                'overtime_rate',
                'allowances_fixed',
                'deductions_fixed',
                'working_hours_per_day',
                'working_days_per_month',
                'bank_name',
                'bank_account_number',
                'iban',
                'salary_release_day',
            ]);
        });
    }
};
