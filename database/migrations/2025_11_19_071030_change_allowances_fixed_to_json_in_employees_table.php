<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, change column type from decimal to text temporarily
        Schema::table('employees', function (Blueprint $table) {
            $table->text('allowances_fixed')->nullable()->change();
        });

        // Convert existing decimal values to JSON format
        DB::table('employees')->whereNotNull('allowances_fixed')->get()->each(function ($employee) {
            $oldValue = $employee->allowances_fixed;
            // Check if it's already JSON
            $decoded = json_decode($oldValue, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Already JSON, skip
                return;
            }
            
            // It's a decimal value, convert it
            $numericValue = (float) $oldValue;
            if ($numericValue > 0) {
                $jsonValue = json_encode([
                    [
                        'type' => 'General Allowance',
                        'amount' => $numericValue,
                    ]
                ]);
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update(['allowances_fixed' => $jsonValue]);
            } else {
                // Set to null if 0
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update(['allowances_fixed' => null]);
            }
        });

        // Change column type from text to json
        Schema::table('employees', function (Blueprint $table) {
            $table->json('allowances_fixed')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change column type from json to text temporarily
        Schema::table('employees', function (Blueprint $table) {
            $table->text('allowances_fixed')->nullable()->change();
        });

        // Convert JSON back to decimal (sum all allowances)
        DB::table('employees')->whereNotNull('allowances_fixed')->get()->each(function ($employee) {
            $jsonValue = json_decode($employee->allowances_fixed, true);
            if (is_array($jsonValue)) {
                $total = array_sum(array_column($jsonValue, 'amount'));
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update(['allowances_fixed' => (string) $total]);
            } else {
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update(['allowances_fixed' => '0']);
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('allowances_fixed', 10, 2)->default(0)->change();
        });
    }
};
