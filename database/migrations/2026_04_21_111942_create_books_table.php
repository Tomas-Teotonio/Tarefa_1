<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('isbn')->unique();
            $table->string('name');
            $table->foreignId('publisher_id')->constrained()->restrictOnDelete();
            $table->text('bibliography')->nullable();
            $table->string('cover_image')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->index('name');
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};