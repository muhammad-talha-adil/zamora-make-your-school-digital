<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the payment methods table.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('code', 20)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed default payment methods
        $paymentMethods = [
            ['name' => 'Cash', 'code' => 'cash', 'is_active' => true],
            ['name' => 'Bank Transfer', 'code' => 'bank_transfer', 'is_active' => true],
            ['name' => 'JazzCash', 'code' => 'jazzcash', 'is_active' => true],
            ['name' => 'EasyPaisa', 'code' => 'easypaisa', 'is_active' => true],
            ['name' => 'Cheque', 'code' => 'cheque', 'is_active' => true],
            ['name' => 'Online Payment', 'code' => 'online', 'is_active' => true],
        ];

        foreach ($paymentMethods as $method) {
            DB::table('payment_methods')->insert($method);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
