<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_message_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ai_message_id')
                ->constrained('ai_messages')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->text('content');

            $table->timestamps();

            $table->index([
                'ai_message_id',
                'user_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_message_comments');
    }
};