<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GenerateCatalogBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    
    protected $products;
    protected $showStock;
    protected $batchNumber;
    protected $sessionId;

    /**
     * Create a new job instance.
     */
    public function __construct($products, $showStock, $batchNumber, $sessionId)
    {
        $this->products = $products;
        $this->showStock = $showStock;
        $this->batchNumber = $batchNumber;
        $this->sessionId = $sessionId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Converter arrays para objetos stdClass se necessário
        $products = collect($this->products)->map(function ($product) {
            if (is_array($product)) {
                $obj = (object) $product;
                $obj->local_image_path = $this->getLocalImagePath($obj);
                return $obj;
            }
            $product->local_image_path = $this->getLocalImagePath($product);
            return $product;
        });

        // Gerar PDF do lote
        $pdf = Pdf::loadView('catalog', [
            'products' => $products,
            'showStock' => $this->showStock
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);

        // Salvar PDF temporário
        $filename = "catalog_batch_{$this->sessionId}_{$this->batchNumber}.pdf";
        $pdfContent = $pdf->output();
        
        Storage::disk('local')->put("temp_catalogs/{$filename}", $pdfContent);
        
        // Atualizar progresso
        $this->updateProgress();
    }

    /**
     * Get local image path for product
     */
    private function getLocalImagePath($product)
    {
        $imageUrl = is_array($product) ? ($product['image_url'] ?? null) : ($product->image_url ?? null);
        $productId = is_array($product) ? ($product['id'] ?? null) : ($product->id ?? null);
        
        if (!$imageUrl || !$productId) {
            return null;
        }
        
        $filename = 'product_' . $productId . '_' . md5($imageUrl) . '.jpg';
        $localPath = storage_path('app/public/product_images/' . $filename);
        
        if (file_exists($localPath)) {
            return $localPath;
        }
        
        return null;
    }

    /**
     * Update progress in cache
     */
    private function updateProgress()
    {
        $cacheKey = "catalog_progress_{$this->sessionId}";
        $progress = cache($cacheKey, ['completed' => 0, 'total' => 0]);
        $progress['completed']++;
        cache([$cacheKey => $progress], now()->addMinutes(30));
    }
}
