<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class MigrateImagesToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:migrate-s3 
                            {--limit= : Limit the number of files to upload per folder (for testing)}
                            {--force : Overwrite existing files on S3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate local images from storage/app/public/property to AWS S3';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $force = $this->option('force');

        // Define the source folders and their S3 prefix logic
        $sources = [
            'public' => storage_path('app/public'),
            'user' => storage_path('app'), // This will catch the 'user' folder inside app
        ];

        $totalUploaded = 0;
        $totalSkipped = 0;

        foreach ($sources as $key => $basePath) {
            if (!is_dir($basePath)) {
                $this->warn("Source path not found, skipping: {$basePath}");
                continue;
            }

            $this->info("\n--- Processing Source: {$key} ---");
            
            // For 'user', we only want to process the 'user' subfolder
            // For 'public', we process everything inside
            $searchPath = ($key === 'user') ? $basePath . DIRECTORY_SEPARATOR . 'user' : $basePath;
            
            if (!is_dir($searchPath)) {
                $this->warn("Search path not found, skipping: {$searchPath}");
                continue;
            }

            $directory = new RecursiveDirectoryIterator($searchPath, RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);

            $counts = [];

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $fullPath = $file->getPathname();
                
                // For 'public', S3 path starts AFTER 'public/'
                // For 'user', S3 path starts WITH 'user/'
                $relativePath = ($key === 'public') 
                    ? str_replace($basePath . DIRECTORY_SEPARATOR, '', $fullPath)
                    : str_replace(storage_path('app') . DIRECTORY_SEPARATOR, '', $fullPath);
                
                // Normalize path for S3
                $s3Path = str_replace('\\', '/', $relativePath);
                
                $folderName = dirname($s3Path);
                
                if (!isset($counts[$folderName])) {
                    $counts[$folderName] = 0;
                }

                if ($limit && $counts[$folderName] >= (int)$limit) {
                    continue;
                }

                if (!$force && Storage::disk('s3')->exists($s3Path)) {
                    $this->line("<fg=yellow>Skipping (Exists):</> {$s3Path}");
                    $totalSkipped++;
                    continue;
                }

                try {
                    $stream = fopen($fullPath, 'r');
                    $result = Storage::disk('s3')->put($s3Path, $stream);
                    
                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    if ($result) {
                        $counts[$folderName]++;
                        $totalUploaded++;
                        $this->info("  ✓ Uploaded: " . $file->getFilename() . " -> " . $s3Path);
                    } else {
                        $this->error("Failed to upload: {$s3Path}");
                    }

                } catch (\Exception $e) {
                    $this->error("Error uploading {$s3Path}: " . $e->getMessage());
                }
            }
        }

        $this->info("\n--- Migration Summary ---");
        $this->info("Total Uploaded: {$totalUploaded}");
        $this->info("Total Skipped: {$totalSkipped}");
        $this->info("Migration completed.");
    }
}
