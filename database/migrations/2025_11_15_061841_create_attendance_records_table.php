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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->enum('type', ['in', 'out']);
            $table->dateTime('datetime');
            $table->enum('method', ['qr', 'ip', 'manual'])->default('manual');
            $table->string('ip_address')->nullable();
            $table->string('wifi_ssid')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->string('device_info')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'datetime']);
            $table->index(['company_id', 'datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
