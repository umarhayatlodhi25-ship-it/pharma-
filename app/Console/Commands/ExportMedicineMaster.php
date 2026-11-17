<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportMedicineMaster extends Command
{
    protected $signature = 'medicine:export
                            {--chunk=2000 : Records per chunk file}
                            {--output=database/medicine_export : Output directory}
                            {--format=json : Output format (json)}';

    protected $description = 'Export medicine_master table to chunked JSON files for production import';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $outputDir = base_path($this->option('output'));

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $total = DB::table('medicine_master')->count();
        $this->info("Total records to export: {$total}");

        if ($total === 0) {
            $this->warn('No records found in medicine_master table.');
            return self::FAILURE;
        }

        $chunks = (int) ceil($total / $chunkSize);
        $this->info("Exporting in {$chunks} chunks of {$chunkSize} records each...");
        $bar = $this->output->createProgressBar($chunks);
        $bar->start();

        $exported = 0;
        $chunkIndex = 0;
        $manifest = [
            'exported_at'  => now()->toIso8601String(),
            'total_records' => $total,
            'chunk_size'   => $chunkSize,
            'chunks'       => [],
        ];

        DB::table('medicine_master')
            ->orderBy('id')
            ->chunk($chunkSize, function ($records) use ($outputDir, &$chunkIndex, &$exported, &$manifest, $bar) {
                $filename = "chunk_{$chunkIndex}.json";
                $filepath = $outputDir . DIRECTORY_SEPARATOR . $filename;

                $rows = $records->map(function ($r) {
                    return (array) $r;
                })->toArray();

                file_put_contents($filepath, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                $manifest['chunks'][] = [
                    'file'  => $filename,
                    'count' => count($rows),
                    'from_id' => $rows[0]['id'] ?? null,
                    'to_id'   => end($rows)['id'] ?? null,
                ];

                $exported += count($rows);
                $chunkIndex++;
                $bar->advance();
            });

        $bar->finish();
        $this->newLine();

        // Write manifest
        file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("✅ Export complete: {$exported} records in {$chunkIndex} files → {$outputDir}");
        $this->info("Manifest: {$outputDir}/manifest.json");

        return self::SUCCESS;
    }
}

