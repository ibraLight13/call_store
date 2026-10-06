<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 30)->unique(); // hero|marquee|feed|how|faq|cta
            $table->boolean('is_visible')->default(true);
            // Admin overrides, per locale; empty fields fall back to lang/*/shell.php.
            $table->json('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
