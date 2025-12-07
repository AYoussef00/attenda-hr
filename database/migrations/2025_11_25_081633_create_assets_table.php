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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('asset_code')->unique();
            $table->string('type'); // Laptop, Mobile, Monitor, Car...
            $table->string('model');
            $table->string('serial_number')->nullable();
            $table->date('purchase_date');
            $table->decimal('cost', 10, 2);
            $table->enum('status', ['Available', 'Assigned', 'Under_Maintenance', 'Damaged', 'Retired'])->default('Available');
            $table->date('warranty_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index('asset_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
