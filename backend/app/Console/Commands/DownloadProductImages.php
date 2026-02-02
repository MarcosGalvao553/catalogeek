<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

class DownloadProductImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:download-images {--force : Force re-download of existing images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download all product images from external URLs to local storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $products = Product::whereNotNull('image_url')->get();
        
        if ($products->isEmpty()) {
            $this->info('No products with image URLs found.');
            return 0;
        }
        
        $this->info("Found {$products->count()} products with images.");
        
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();
        
        $downloaded = 0;
        $skipped = 0;
        $failed = 0;
        
        foreach ($products as $product) {
            $result = $this->downloadProductImage($product, $force);
            
            if ($result === 'downloaded') {
                $downloaded++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } else {
                $failed++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Download completed!");
        $this->info("Downloaded: $downloaded");
        $this->info("Skipped (already exists): $skipped");
        $this->info("Failed: $failed");
        
        return 0;
    }
    
    /**
     * Download product image from URL and save locally
     */
    private function downloadProductImage($product, $force = false)
    {
        if (!$product->image_url) {
            return 'failed';
        }
        
        $filename = 'product_' . $product->id . '_' . md5($product->image_url) . '.jpg';
        $localPath = storage_path('app/public/product_images/' . $filename);
        
        // Se já existe e não é força, pular
        if (file_exists($localPath) && !$force) {
            return 'skipped';
        }
        
        // Criar diretório se não existir
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        try {
            // Baixar imagem com timeout
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,  // timeout de 10 segundos
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $imageContent = @file_get_contents($product->image_url, false, $context);
            
            if ($imageContent !== false && strlen($imageContent) > 0) {
                file_put_contents($localPath, $imageContent);
                return 'downloaded';
            }
        } catch (\Exception $e) {
            $this->error("Failed to download image for product {$product->id}: {$e->getMessage()}");
            return 'failed';
        }
        
        return 'failed';
    }
}
