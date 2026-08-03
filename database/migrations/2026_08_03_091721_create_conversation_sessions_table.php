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
        Schema::create('conversation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_subscriber_id')->constrained()->onDelete('cascade');
            $table->foreignId('flow_version_id')->constrained()->onDelete('cascade');
            $table->string('current_block_id');
            $table->jsonb('context')->default('{}');
            $table->string('status')->default('active'); // active, paused, completed
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['bot_subscriber_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_sessions');
    }
};
