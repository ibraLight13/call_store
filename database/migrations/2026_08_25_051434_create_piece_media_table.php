<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piece_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->default(0);
            $table->string('original_disk')->default('local');
            $table->string('original_path');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->json('derivatives')->nullable();
            $table->timestamps();

            $table->index(['piece_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_media');
    }
};
