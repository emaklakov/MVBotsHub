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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('enabled_2fa')->default(true)->after('remember_token');
            $table->boolean('enabled_multi_device_login')->default(false)->after('enabled_2fa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('enabled_2fa');
            $table->dropColumn('enabled_multi_device_login');
        });
    }
};
