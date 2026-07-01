<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('model_id')->nullable();

            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->unsignedInteger('max_tokens')->default(1024);

            $table->timestamps();

            $table->index('user_id');
            $table->index('model_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};