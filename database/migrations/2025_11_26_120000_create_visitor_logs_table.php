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
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->string('country_code', 5)->nullable()->index();
            $table->string('country_name', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('path', 255)->index();
            $table->string('referrer', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};


