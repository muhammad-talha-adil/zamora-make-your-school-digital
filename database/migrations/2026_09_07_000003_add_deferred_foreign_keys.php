<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the foreign keys whose target table is created after the referencing one.
 *
 * Those constraints were declared inline in the create migrations, pointing at
 * tables that did not exist yet. Nothing complained because every table was
 * MyISAM, which ignores foreign keys; on InnoDB each one halts the migration.
 * The columns stayed where they were and the constraints are attached here,
 * once both sides exist.
 */
return new class extends Migration
{
    /**
     * @var list<array{table: string, column: string, references: string, onDelete: string}>
     */
    private const DEFERRED = [
        [
            'table' => 'inventory_purchases',
            'column' => 'supplier_id',
            'references' => 'suppliers',
            'onDelete' => 'set null',
        ],
        [
            'table' => 'inventory_returns',
            'column' => 'student_inventory_id',
            'references' => 'student_inventory_records',
            'onDelete' => 'cascade',
        ],
        // `purchase_return_items.reason_id` is not listed here: it already gets
        // its constraint from 2026_02_25_000002, which runs after `reasons`
        // exists.
        [
            'table' => 'ledgers',
            'column' => 'category_id',
            'references' => 'ledger_categories',
            'onDelete' => 'set null',
        ],
    ];

    public function up(): void
    {
        foreach (self::DEFERRED as $fk) {
            if (! Schema::hasTable($fk['table']) || ! Schema::hasTable($fk['references'])) {
                continue;
            }

            // A row pointing at something that was never there would block the
            // constraint, so clear those references first.
            $this->detachOrphans($fk['table'], $fk['column'], $fk['references']);

            Schema::table($fk['table'], function (Blueprint $table) use ($fk) {
                $table->foreign($fk['column'])
                    ->references('id')
                    ->on($fk['references'])
                    ->onDelete($fk['onDelete']);
            });
        }
    }

    public function down(): void
    {
        foreach (self::DEFERRED as $fk) {
            if (! Schema::hasTable($fk['table'])) {
                continue;
            }

            Schema::table($fk['table'], function (Blueprint $table) use ($fk) {
                $table->dropForeign([$fk['column']]);
            });
        }
    }

    /**
     * Nulls out values with no matching parent row.
     *
     * Only safe for a nullable column; `inventory_returns.student_inventory_id`
     * is not nullable, so an orphan there is deleted with its row instead.
     */
    private function detachOrphans(string $table, string $column, string $parent): void
    {
        $orphans = DB::table($table)
            ->whereNotNull($column)
            ->whereNotIn($column, fn ($q) => $q->select('id')->from($parent));

        $nullable = collect(Schema::getColumns($table))
            ->firstWhere('name', $column)['nullable'] ?? true;

        $nullable ? $orphans->update([$column => null]) : $orphans->delete();
    }
};
