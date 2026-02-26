<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hot-path index hardening.
 *
 * This migration is intentionally defensive (try/catch) to keep installs resilient
 * across DB engines and across module upgrade paths.
 */
class HardenHotpathIndexes extends Migration
{
    /**
     * PostgreSQL aborts the whole transaction after any SQL error, even if caught in PHP.
     * Disable wrapping this migration in a transaction and avoid errors via IF NOT EXISTS.
     */
    public $withinTransaction = false;

    public function up()
    {
        // Events: daily caps and max-per-day checks query by (user_id, event_type, created_at)
        $this->createIndexIfMissing(
            'overflowachievement_events',
            'oa_ev_user_type_created',
            ['user_id', 'event_type', 'created_at']
        );

        // Safety: older installs might be missing this composite index.
        $this->createIndexIfMissing(
            'overflowachievement_events',
            'oa_ev_user_type_subject',
            ['user_id', 'event_type', 'subject_type', 'subject_id']
        );

        // Achievements: help threshold-only queries / future optimizations.
        $this->createIndexIfMissing(
            'overflowachievement_achievements',
            'oa_ach_threshold',
            ['threshold']
        );

        // Unlocked: common list queries by user ordered by unlocked_at.
        $this->createIndexIfMissing(
            'overflowachievement_unlocked',
            'oa_un_user_unlocked',
            ['user_id', 'unlocked_at']
        );
    }

    public function down()
    {
        $this->dropIndexIfExists('oa_ev_user_type_created', 'overflowachievement_events');
        $this->dropIndexIfExists('oa_ev_user_type_subject', 'overflowachievement_events');
        $this->dropIndexIfExists('oa_ach_threshold', 'overflowachievement_achievements');
        $this->dropIndexIfExists('oa_un_user_unlocked', 'overflowachievement_unlocked');
    }

    private function createIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $cols = implode(', ', array_map(fn($c) => '"'.$c.'"', $columns));
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON \"{$table}\" ({$cols})");
            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $dbName = DB::getDatabaseName();
            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', $dbName)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();
            if ($exists) {
                return;
            }
            Schema::table($table, function (Blueprint $bp) use ($columns, $indexName) {
                $bp->index($columns, $indexName);
            });
            return;
        }

        try {
            Schema::table($table, function (Blueprint $bp) use ($columns, $indexName) {
                $bp->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function dropIndexIfExists(string $indexName, string $table): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS {$indexName}");
            return;
        }

        try {
            Schema::table($table, function (Blueprint $bp) use ($indexName) {
                $bp->dropIndex($indexName);
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
