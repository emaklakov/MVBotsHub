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
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->text('token'); // encrypted cast в модели
            $table->string('webhook_token', 64)->unique()->nullable(); // публичный ID для URL
            $table->string('webhook_secret_token')->nullable();
            $table->string('webhook_url')->nullable();
            $table->jsonb('settings')->default('{}');
            $table->string('status')->default('disabled'); // active, paused, disabled
            $table->string('channel_type')->default('telegram');
            $table->timestamps();

            $table->index('status');
            $table->index('webhook_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
