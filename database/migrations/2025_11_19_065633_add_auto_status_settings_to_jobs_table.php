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
        Schema::table('jobs', function (Blueprint $table) {
            $table->integer('auto_reject_min')->nullable()->after('skills')->comment('Minimum match percentage for auto rejection');
            $table->integer('auto_reject_max')->nullable()->after('auto_reject_min')->comment('Maximum match percentage for auto rejection');
            $table->integer('auto_accept_min')->nullable()->after('auto_reject_max')->comment('Minimum match percentage for auto acceptance');
            $table->integer('auto_accept_max')->nullable()->after('auto_accept_min')->comment('Maximum match percentage for auto acceptance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['auto_reject_min', 'auto_reject_max', 'auto_accept_min', 'auto_accept_max']);
        });
    }
};
