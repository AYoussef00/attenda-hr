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
            if (! Schema::hasColumn('chat_threads', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->index()->after('last_message_at');
            }

            if (! Schema::hasColumn('chat_threads', 'duration_seconds')) {
                $table->unsignedInteger('duration_seconds')->nullable()->after('ended_at');
            }

            if (! Schema::hasColumn('chat_threads', 'resolved')) {
                $table->boolean('resolved')->nullable()->after('duration_seconds');
            }

            if (! Schema::hasColumn('chat_threads', 'issue_summary')) {
                $table->text('issue_summary')->nullable()->after('resolved');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            if (Schema::hasColumn('chat_threads', 'ended_at')) {
                $table->dropColumn('ended_at');
            }
            if (Schema::hasColumn('chat_threads', 'duration_seconds')) {
                $table->dropColumn('duration_seconds');
            }
            if (Schema::hasColumn('chat_threads', 'resolved')) {
                $table->dropColumn('resolved');
            }
            if (Schema::hasColumn('chat_threads', 'issue_summary')) {
                $table->dropColumn('issue_summary');
            }
        });
    }
};


