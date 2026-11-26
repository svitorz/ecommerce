<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colunas de chave estrangeira que ainda não têm índice (o PostgreSQL não cria sozinho).
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private array $columns = [
        ['orders', 'user_id'],
        ['payments', 'order_id'],
        ['addresses', 'user_id'],
        ['orders_products', 'order_id'],
        ['orders_products', 'product_id'],
        ['products', 'category_id'],
        ['favorite_products', 'product_id'],
        ['product_ratings', 'product_id'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->columns as [$table, $column]) {
            if (! $this->hasLeadingIndex($table, $column)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($column));
            }
        }
    }

    /**
     * Reverse the migrations. Só remove os índices que esta migração criou.
     */
    public function down(): void
    {
        foreach ($this->columns as [$table, $column]) {
            $name = "{$table}_{$column}_index";

            if ($this->indexExists($name)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
            }
        }
    }

    /**
     * Verifica se alguma definição de índice da tabela começa pela coluna informada.
     */
    private function hasLeadingIndex(string $table, string $column): bool
    {
        $definitions = DB::select(
            'select indexdef from pg_indexes where schemaname = current_schema() and tablename = ?',
            [$table],
        );

        foreach ($definitions as $definition) {
            if (preg_match('/\(\s*"?'.preg_quote($column, '/').'"?\s*[,)]/', $definition->indexdef)) {
                return true;
            }
        }

        return false;
    }

    private function indexExists(string $name): bool
    {
        return DB::table('pg_indexes')
            ->where('schemaname', DB::raw('current_schema()'))
            ->where('indexname', $name)
            ->exists();
    }
};
