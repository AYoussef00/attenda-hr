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
        // Modify enum to include 'pending' status
        // MySQL doesn't support direct enum modification, so we use raw SQL
        DB::statement("ALTER TABLE companies MODIFY COLUMN status ENUM('active', 'inactive', 'pending') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        // First, update any 'pending' records to 'inactive'
        DB::table('companies')->where('status', 'pending')->update(['status' => 'inactive']);
        
        // Then modify enum back to original
        DB::statement("ALTER TABLE companies MODIFY COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
    }
};
