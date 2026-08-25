<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scripts', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedTinyInteger('sort')->default(0);
        });

        Schema::create('mediums', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedTinyInteger('sort')->default(0);
        });

        Schema::create('themes', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedTinyInteger('sort')->default(0);
        });

        Schema::create('colors', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('hex', 7);
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedTinyInteger('sort')->default(0);
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('themes');
        Schema::dropIfExists('mediums');
        Schema::dropIfExists('scripts');
    }
};
