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
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'name')) {
                $table->string('name')->after('id');
            }
            if (!Schema::hasColumn('companies', 'email')) {
                $table->string('email')->nullable()->after('name');
            }
            if (!Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('companies', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('companies', 'logo')) {
                $table->string('logo')->nullable()->after('address');
            }
            if (!Schema::hasColumn('companies', 'attendance_methods')) {
                $table->json('attendance_methods')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('companies', 'ip_whitelist')) {
                $table->json('ip_whitelist')->nullable()->after('attendance_methods');
            }
            if (!Schema::hasColumn('companies', 'settings')) {
                $table->json('settings')->nullable()->after('ip_whitelist');
            }
            if (!Schema::hasColumn('companies', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('settings');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $columns = ['name', 'email', 'phone', 'address', 'logo', 'attendance_methods', 'ip_whitelist', 'settings', 'status'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
