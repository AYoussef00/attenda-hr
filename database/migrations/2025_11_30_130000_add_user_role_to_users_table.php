<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new "user" role to the enum column.
        // NOTE: This uses a raw statement because altering ENUM via schema builder
        // is not fully supported across all drivers.
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM('super_admin','company_admin','hr','manager','employee','user') NOT NULL DEFAULT 'employee'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove "user" role from the enum definition (back to original list)
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM('super_admin','company_admin','hr','manager','employee') NOT NULL DEFAULT 'employee'
        ");
    }
};


