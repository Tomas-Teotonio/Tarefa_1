<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversation_shares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ai_conversation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('visibility'); // public | restricted
            $table->string('token')->unique();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->index(['ai_conversation_id', 'visibility', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_shares');
    }
};