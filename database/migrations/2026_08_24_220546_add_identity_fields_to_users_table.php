<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->unique()->after('name');
            $table->text('bio')->nullable()->after('remember_token');
            $table->string('locale', 8)->nullable()->after('bio');
            $table->string('avatar_path')->nullable()->after('locale');
            $table->string('cover_path')->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['username', 'bio', 'locale', 'avatar_path', 'cover_path']);
        });
    }
};
