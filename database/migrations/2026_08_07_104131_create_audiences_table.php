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
        Schema::create('audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type')->default('static'); // static, dynamic
            $table->jsonb('filters')->nullable(); // используется только для type=dynamic
            $table->unsignedInteger('cached_count')->default(0); // последний известный размер аудитории
            $table->timestamp('cached_count_at')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audiences');
    }
};
