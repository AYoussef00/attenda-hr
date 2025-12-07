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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('action');
            $table->string('model')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['user_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index(['model', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
