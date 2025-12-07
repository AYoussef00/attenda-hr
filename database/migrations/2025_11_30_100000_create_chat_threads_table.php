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
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id')->index();
            $table->string('status')->default('open'); // open, closed
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedInteger('unread_admin_count')->default(0);
            $table->timestamp('ended_at')->nullable()->index();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('resolved')->nullable();
            $table->text('issue_summary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};


