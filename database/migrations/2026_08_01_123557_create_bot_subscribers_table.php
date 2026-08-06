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
        Schema::create('bot_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('telegram_id');
            $table->string('telegram_username')->nullable();
            $table->string('telegram_first_name')->nullable();
            $table->string('telegram_last_name')->nullable();
            $table->string('telegram_language', 5)->nullable();
            $table->date('birthday')->nullable();
            $table->string('language', 5)->nullable(); // override от people/bot
            $table->jsonb('settings')->default('{}');
            $table->string('status')->default('active'); // active, blocked, merged
            $table->foreignId('merged_into_id')->nullable()->constrained('bot_subscribers')->onDelete('set null');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique(['bot_id', 'telegram_id']);
            $table->index(['bot_id', 'status']);
            $table->index(['bot_id', 'person_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_subscribers');
    }
};
