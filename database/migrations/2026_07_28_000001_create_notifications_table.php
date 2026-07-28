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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            // Приоритет и категория
            $table->string('priority', 20)->default('normal')->index()
                ->after('data'); // critical | high | normal | low
            $table->string('category', 100)->nullable()->index()
                ->after('priority'); // system.errors | orders.new | users.registered
            // TTL / Expiration
            $table->timestamp('expires_at')->nullable()->index()
                ->after('read_at');
            // Read receipt (когда пользователь открыл, а не просто "прочитал")
            $table->timestamp('opened_at')->nullable()
                ->after('read_at');
            // Группировка
            $table->string('group_key', 255)->nullable()->index()
                ->after('category');
            $table->unsignedSmallInteger('group_count')->default(1)
                ->after('group_key');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
