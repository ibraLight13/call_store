<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pieces', function (Blueprint $table): void {
            $table->index(['state', 'published_at'], 'pieces_feed_index');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE pieces ADD FULLTEXT INDEX pieces_normalized_fulltext (normalized_text)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE pieces DROP INDEX pieces_normalized_fulltext');
        }

        Schema::table('pieces', function (Blueprint $table): void {
            $table->dropIndex('pieces_feed_index');
        });
    }
};
