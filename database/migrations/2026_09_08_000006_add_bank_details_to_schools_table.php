<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The bank account fee is deposited into, printed on every challan.
 *
 * Fee here is collected over the counter at a bank rather than at the school,
 * so the account title and number have to appear on the slip the parent hands
 * to the teller. It belongs to the school record like the address and the
 * logo do, not to a configuration file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('phone');
            $table->string('bank_account_title')->nullable()->after('bank_name');
            $table->string('bank_account_no', 64)->nullable()->after('bank_account_title');
            $table->string('bank_branch')->nullable()->after('bank_account_no');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_title', 'bank_account_no', 'bank_branch']);
        });
    }
};
