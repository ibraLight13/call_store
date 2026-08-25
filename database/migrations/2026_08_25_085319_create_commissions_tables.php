<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique(); // CM-1024
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();

            $table->string('state', 30)->default('requested')->index();

            $table->text('text');
            $table->string('text_language', 2)->default('ar');
            $table->foreignId('script_id')->nullable()->constrained('scripts')->nullOnDelete();
            $table->foreignId('medium_id')->nullable()->constrained('mediums')->nullOnDelete();
            $table->unsignedSmallInteger('width_cm')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->text('message')->nullable(); // buyer's initial brief

            $table->unsignedInteger('budget_minor')->nullable();
            $table->unsignedInteger('quote_minor')->nullable();
            $table->unsignedInteger('deposit_minor')->nullable();
            $table->char('currency', 3)->default('SAR');
            $table->date('deadline')->nullable();

            // Settlement refs (deposit + balance are two separate captures).
            $table->string('checkout_ref')->nullable()->index();
            $table->string('deposit_payment_ref')->nullable()->unique();
            $table->string('balance_payment_ref')->nullable()->unique();
            $table->string('transfer_status', 20)->default('pending'); // pending|transferred|failed|reversed
            $table->string('transfer_ref')->nullable()->unique();
            $table->timestamp('transferred_at')->nullable();

            $table->unsignedTinyInteger('revision_count')->default(0);
            $table->string('tracking_number', 80)->nullable();

            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('deposit_paid_at')->nullable();
            $table->timestamp('balance_paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });

        Schema::create('commission_proofs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('commission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('image_path'); // private disk — never served directly
            $table->text('seller_note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['commission_id', 'version']);
        });

        Schema::create('commission_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('commission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['commission_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_messages');
        Schema::dropIfExists('commission_proofs');
        Schema::dropIfExists('commissions');
    }
};
