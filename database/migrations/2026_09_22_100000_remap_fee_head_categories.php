<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Head Category Redesign
 *
 * The original `category` column conflated billing frequency (monthly/
 * annual/one_time) with what the fee is actually for (transport/fine/
 * discount/misc) — duplicating the separate `default_frequency` column.
 *
 * A native enum column can't be widened/narrowed in place without a driver-
 * specific ALTER (and this app has no doctrine/dbal for Schema::change()), so
 * this migration adds a new enum column with the new value set, backfills it
 * from the old column via the mapping below, then swaps it in.
 *
 * Old -> new mapping:
 * - monthly   -> tuition
 * - annual    -> annual_charges
 * - one_time  -> misc
 * - transport -> transport (unchanged)
 * - fine      -> fine (unchanged)
 * - discount  -> discount (unchanged)
 * - misc      -> misc (unchanged)
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $oldToNew = [
        'monthly' => 'tuition',
        'annual' => 'annual_charges',
        'one_time' => 'misc',
        'transport' => 'transport',
        'fine' => 'fine',
        'discount' => 'discount',
        'misc' => 'misc',
    ];

    /**
     * @var array<string, string>
     */
    private array $newToOld = [
        'tuition' => 'monthly',
        'admission' => 'one_time',
        'annual_charges' => 'annual',
        'examination' => 'one_time',
        'transport' => 'transport',
        'library' => 'monthly',
        'laboratory' => 'monthly',
        'sports' => 'monthly',
        'security' => 'one_time',
        'fine' => 'fine',
        'discount' => 'discount',
        'misc' => 'misc',
    ];

    /**
     * @var list<string>
     */
    private array $newCategories = [
        'tuition',
        'admission',
        'annual_charges',
        'examination',
        'transport',
        'library',
        'laboratory',
        'sports',
        'security',
        'fine',
        'discount',
        'misc',
    ];

    /**
     * @var list<string>
     */
    private array $oldCategories = [
        'monthly',
        'annual',
        'one_time',
        'transport',
        'fine',
        'discount',
        'misc',
    ];

    public function up(): void
    {
        Schema::table('fee_heads', function (Blueprint $table): void {
            $table->enum('category_new', $this->newCategories)->nullable()->after('category');
        });

        DB::table('fee_heads')->select('id', 'category')->orderBy('id')->each(function (object $row): void {
            $new = $this->oldToNew[$row->category] ?? 'misc';

            DB::table('fee_heads')->where('id', $row->id)->update(['category_new' => $new]);
        });

        Schema::table('fee_heads', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'category']);
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });

        Schema::table('fee_heads', function (Blueprint $table): void {
            $table->renameColumn('category_new', 'category');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(sprintf(
                'ALTER TABLE fee_heads MODIFY COLUMN category ENUM(%s) NOT NULL',
                $this->quotedList($this->newCategories)
            ));
        }

        Schema::table('fee_heads', function (Blueprint $table): void {
            $table->index('category');
            $table->index(['is_active', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('fee_heads', function (Blueprint $table): void {
            $table->enum('category_old', $this->oldCategories)->nullable()->after('category');
        });

        DB::table('fee_heads')->select('id', 'category')->orderBy('id')->each(function (object $row): void {
            $old = $this->newToOld[$row->category] ?? 'misc';

            DB::table('fee_heads')->where('id', $row->id)->update(['category_old' => $old]);
        });

        Schema::table('fee_heads', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'category']);
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });

        Schema::table('fee_heads', function (Blueprint $table): void {
            $table->renameColumn('category_old', 'category');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(sprintf(
                'ALTER TABLE fee_heads MODIFY COLUMN category ENUM(%s) NOT NULL',
                $this->quotedList($this->oldCategories)
            ));
        }

        Schema::table('fee_heads', function (Blueprint $table): void {
            $table->index('category');
            $table->index(['is_active', 'category']);
        });
    }

    /**
     * @param  list<string>  $values
     */
    private function quotedList(array $values): string
    {
        return implode(', ', array_map(fn (string $value): string => "'{$value}'", $values));
    }
};
