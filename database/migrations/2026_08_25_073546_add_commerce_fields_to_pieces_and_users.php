<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pieces', function (Blueprint $table): void {
            $table->timestamp('sold_at')->nullable()->after('state');
            $table->index(['listing_type', 'state', 'sold_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('stripe_account_id')->nullable()->unique()->after('locale');
            $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['stripe_account_id', 'stripe_charges_enabled']);
        });

        Schema::table('pieces', function (Blueprint $table): void {
            $table->dropIndex(['listing_type', 'state', 'sold_at']);
            $table->dropColumn('sold_at');
        });
    }
};
