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
        Schema::create('flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('trigger_type')->default('command'); // command, callback, deeplink, button
            $table->string('trigger_value')->default('start'); // /start, help, campaign_123
            $table->string('status')->default('draft'); // draft, active, archived
            $table->timestamps();

            $table->index(['bot_id', 'status']);
            $table->index(['bot_id', 'trigger_type', 'trigger_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flows');
    }
};
