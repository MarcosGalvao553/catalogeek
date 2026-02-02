<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanTempCatalogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalogs:clean-temp {--hours=24 : Delete files older than X hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean temporary catalog PDF files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $tempDir = storage_path('app/temp_catalogs');
        
        if (!is_dir($tempDir)) {
            $this->info('No temp directory found.');
            return 0;
        }
        
        $files = File::files($tempDir);
        $cutoffTime = now()->subHours($hours)->timestamp;
        $deletedCount = 0;
        
        foreach ($files as $file) {
            if ($file->getMTime() < $cutoffTime) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }
        
        $this->info("Deleted {$deletedCount} temporary catalog files older than {$hours} hours.");
        
        return 0;
    }
}
