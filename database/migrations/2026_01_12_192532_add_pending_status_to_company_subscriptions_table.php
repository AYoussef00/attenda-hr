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
        DB::statement("ALTER TABLE company_subscriptions MODIFY COLUMN status ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        // First, update any 'pending' records to 'active'
        DB::table('company_subscriptions')->where('status', 'pending')->update(['status' => 'active']);
        
        // Then modify enum back to original
        DB::statement("ALTER TABLE company_subscriptions MODIFY COLUMN status ENUM('active', 'expired', 'cancelled') DEFAULT 'active'");
    }
};
