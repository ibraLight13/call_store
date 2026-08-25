<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_private')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('board_piece', function (Blueprint $table): void {
            $table->primary(['board_id', 'piece_id']);
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // saves_count on pieces counts distinct (user, piece) save pairs.
        Schema::create('piece_saves', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'piece_id']);
        });

        Schema::create('piece_likes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'piece_id']);
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['piece_id', 'created_at']);
            $table->foreign('parent_id')->references('id')->on('comments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('piece_likes');
        Schema::dropIfExists('piece_saves');
        Schema::dropIfExists('board_piece');
        Schema::dropIfExists('boards');
    }
};
