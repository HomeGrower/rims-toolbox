<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptimizeQueries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize-queries {--analyze : Run EXPLAIN on slow queries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze and optimize database queries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Analyzing database performance...');

        // Check table sizes
        $this->checkTableSizes();

        // Analyze slow queries
        $this->analyzeSlowQueries();

        // Check missing indexes
        $this->checkMissingIndexes();

        // Optimize tables
        $this->optimizeTables();

        $this->info('Database optimization complete!');
    }

    /**
     * Check table sizes
     */
    private function checkTableSizes()
    {
        $this->info("\nTable Sizes:");
        
        $tables = DB::select("
            SELECT 
                table_name AS 'Table',
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
                ROUND((data_length / 1024 / 1024), 2) AS 'Data (MB)',
                ROUND((index_length / 1024 / 1024), 2) AS 'Index (MB)',
                table_rows AS 'Rows'
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
            LIMIT 10
        ");

        $this->table(
            ['Table', 'Size (MB)', 'Data (MB)', 'Index (MB)', 'Rows'],
            $tables
        );
    }

    /**
     * Analyze slow queries
     */
    private function analyzeSlowQueries()
    {
        $this->info("\nSlow Queries Analysis:");

        // Check if slow query log is enabled
        $slowLogEnabled = DB::select("SHOW VARIABLES LIKE 'slow_query_log'")[0]->Value ?? 'OFF';
        
        if ($slowLogEnabled !== 'ON') {
            $this->warn('Slow query log is not enabled. Enable it for better analysis.');
            return;
        }

        // Get recent slow queries from performance schema
        $slowQueries = DB::select("
            SELECT 
                DIGEST_TEXT as query,
                COUNT_STAR as exec_count,
                ROUND(AVG_TIMER_WAIT / 1000000000, 2) as avg_time_sec,
                ROUND(SUM_TIMER_WAIT / 1000000000, 2) as total_time_sec
            FROM performance_schema.events_statements_summary_by_digest
            WHERE DIGEST_TEXT IS NOT NULL
            AND AVG_TIMER_WAIT > 1000000000  -- More than 1 second
            ORDER BY AVG_TIMER_WAIT DESC
            LIMIT 5
        ");

        if (empty($slowQueries)) {
            $this->info('No slow queries found.');
            return;
        }

        foreach ($slowQueries as $query) {
            $this->warn("\nSlow Query:");
            $this->line("Query: " . substr($query->query, 0, 100) . '...');
            $this->line("Executions: {$query->exec_count}");
            $this->line("Avg Time: {$query->avg_time_sec}s");
            $this->line("Total Time: {$query->total_time_sec}s");

            if ($this->option('analyze')) {
                $this->analyzeQuery($query->query);
            }
        }
    }

    /**
     * Analyze a specific query
     */
    private function analyzeQuery($query)
    {
        // Skip if it's a summary query
        if (strpos($query, '?') !== false) {
            return;
        }

        try {
            $explain = DB::select("EXPLAIN {$query}");
            $this->table(
                ['id', 'select_type', 'table', 'type', 'possible_keys', 'key', 'rows', 'Extra'],
                $explain
            );
        } catch (\Exception $e) {
            $this->error("Could not analyze query: " . $e->getMessage());
        }
    }

    /**
     * Check for missing indexes
     */
    private function checkMissingIndexes()
    {
        $this->info("\nChecking for missing indexes...");

        // Common queries that might need indexes
        $checks = [
            [
                'table' => 'projects',
                'columns' => ['hotel_chain_id', 'hotel_brand_id'],
                'reason' => 'Frequent filtering by hotel chain and brand'
            ],
            [
                'table' => 'project_data',
                'columns' => ['project_id', 'key'],
                'reason' => 'Composite key lookups'
            ],
            [
                'table' => 'project_setup_teams',
                'columns' => ['project_id', 'status'],
                'reason' => 'Status filtering for projects'
            ],
        ];

        foreach ($checks as $check) {
            $this->checkIndex($check['table'], $check['columns'], $check['reason']);
        }
    }

    /**
     * Check if an index exists
     */
    private function checkIndex($table, $columns, $reason)
    {
        $columnList = implode(',', $columns);
        
        $indexes = DB::select("
            SHOW INDEX FROM {$table} 
            WHERE Column_name IN ('" . implode("','", $columns) . "')
        ");

        if (empty($indexes)) {
            $this->warn("Missing index on {$table}({$columnList}): {$reason}");
        }
    }

    /**
     * Optimize tables
     */
    private function optimizeTables()
    {
        $this->info("\nOptimizing tables...");

        $tables = DB::select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            AND engine = 'InnoDB'
        ");

        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE {$table->table_name}");
                $bar->advance();
            } catch (\Exception $e) {
                Log::warning("Could not optimize table {$table->table_name}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->line('');
    }
}