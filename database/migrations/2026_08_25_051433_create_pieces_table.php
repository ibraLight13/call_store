<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieces', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('text');
            $table->string('normalized_text', 500)->nullable()->index();
            $table->string('transliteration')->nullable();
            $table->string('text_language', 2)->default('ar');

            $table->foreignId('script_id')->nullable()->constrained('scripts')->nullOnDelete();
            $table->foreignId('medium_id')->nullable()->constrained('mediums')->nullOnDelete();
            $table->foreignId('theme_id')->nullable()->constrained('themes')->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();

            $table->unsignedSmallInteger('width_cm')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();

            $table->string('listing_type', 20)->default('ready');
            $table->unsignedInteger('price_minor')->nullable();
            $table->char('currency', 3)->default('SAR');
            $table->boolean('unique_stock')->default(true);

            $table->text('description')->nullable();

            $table->string('state', 30)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('saves_count')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);

            $table->timestamps();
        });

        Schema::create('piece_tag', function (Blueprint $table): void {
            $table->primary(['piece_id', 'tag_id']);
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_tag');
        Schema::dropIfExists('pieces');
    }
};
