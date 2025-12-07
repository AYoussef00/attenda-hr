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
        Schema::table('chat_threads', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_threads', 'assigned_admin_id')) {
                $table->unsignedBigInteger('assigned_admin_id')->nullable()->after('visitor_id');
                $table->foreign('assigned_admin_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            if (Schema::hasColumn('chat_threads', 'assigned_admin_id')) {
                $table->dropForeign(['assigned_admin_id']);
                $table->dropColumn('assigned_admin_id');
            }
        });
    }
};


