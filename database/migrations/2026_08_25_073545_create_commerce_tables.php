<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'piece_id']);
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->string('country', 2)->default('SA');
            $table->string('city', 100);
            $table->string('district', 150)->nullable();
            $table->string('street', 200);
            $table->string('building', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->string('state', 30)->default('pending_payment')->index();
            $table->unsignedInteger('subtotal_minor')->default(0);
            $table->unsignedInteger('shipping_minor')->default(0);
            $table->unsignedInteger('total_minor')->default(0);
            $table->char('currency', 3)->default('SAR');

            // Immutable snapshot of the chosen address at purchase time.
            $table->json('address');

            $table->string('gateway', 20)->nullable();
            $table->string('checkout_ref')->nullable()->index();
            $table->string('payment_ref')->nullable()->unique();

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });

        Schema::create('order_sellers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('subtotal_minor')->default(0);
            $table->unsignedInteger('shipping_minor')->default(0);
            $table->string('transfer_status', 20)->default('pending'); // pending|transferred|failed|reversed
            $table->string('transfer_ref')->nullable()->unique();
            $table->timestamp('shipped_at')->nullable();
            $table->string('tracking_number', 80)->nullable();
            $table->timestamp('transferred_at')->nullable();

            $table->unique(['order_id', 'seller_id']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('piece_id')->constrained()->restrictOnDelete();
            // Snapshots: the piece text and price at purchase time.
            $table->text('text');
            $table->unsignedInteger('price_minor');
            $table->char('currency', 3)->default('SAR');
            $table->unsignedSmallInteger('quantity')->default(1);
        });

        Schema::create('processed_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway', 20);
            $table->string('event_id')->unique();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_webhooks');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('order_sellers');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('cart_items');
    }
};
