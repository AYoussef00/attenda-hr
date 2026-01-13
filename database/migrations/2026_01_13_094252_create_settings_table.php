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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert default values
        DB::table('settings')->insert([
            [
                'key' => 'settings_text1',
                'value' => 'Finally, a performance management platform that works your way.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'settings_text2',
                'value' => 'Bring goals, feedback, and competencies together in one place with a platform that adapts to your process — not the other way around.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
