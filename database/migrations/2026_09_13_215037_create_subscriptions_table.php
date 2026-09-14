<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Single-row table, same pattern as `schools` / `theme_settings`: one
     * installation, one subscription record. Expiring or suspending it must
     * never touch application data — only the middleware that reads this row
     * decides whether the rest of the app may be reached.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['demo', 'active', 'suspended', 'expired'])->default('active');
            $table->enum('block_reason', [
                'subscription_expired',
                'domain_expiring',
                'hosting_expiring',
                'other',
            ])->nullable();
            $table->text('block_reason_note')->nullable();
            $table->timestamp('demo_expires_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
