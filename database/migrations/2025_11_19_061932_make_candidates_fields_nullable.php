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
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->json('skills')->nullable()->change();
            $table->integer('experience_years')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->json('skills')->nullable(false)->change();
            $table->integer('experience_years')->nullable(false)->change();
        });
    }
};
