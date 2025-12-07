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
        Schema::create('asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('maintenance_type'); // Repair / Scheduled
            $table->text('problem_description');
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('vendor')->nullable();
            $table->date('start_date');
            $table->date('completion_date')->nullable();
            $table->enum('status', ['Open', 'In_Progress', 'Completed'])->default('Open');
            $table->timestamps();
            
            $table->index(['asset_id', 'status']);
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance');
    }
};
