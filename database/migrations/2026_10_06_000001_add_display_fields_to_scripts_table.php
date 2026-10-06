<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            // Key into config('catalog.fonts'); null keeps the per-slug default from catalog.script_fonts.
            $table->string('font', 30)->nullable();
            $table->unsignedSmallInteger('weight')->nullable();
            $table->boolean('show_on_home')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            $table->dropColumn(['font', 'weight', 'show_on_home']);
        });
    }
};
