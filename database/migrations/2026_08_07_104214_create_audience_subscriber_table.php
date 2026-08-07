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
        // pivot используется только для type=static — вручную набранных списков
        Schema::create('audience_subscriber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audience_id')->constrained()->onDelete('cascade');
            $table->foreignId('bot_subscriber_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['audience_id', 'bot_subscriber_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audience_subscriber');
    }
};
