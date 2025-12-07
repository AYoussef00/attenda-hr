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
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('business_email');
            $table->string('company_name');
            $table->string('phone_number');
            $table->string('number_of_employees');
            $table->string('company_headquarters')->default('Saudi Arabia');
            $table->enum('choose_time_slot', ['Yes', 'No'])->default('No');
            $table->enum('status', ['pending', 'contacted', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
