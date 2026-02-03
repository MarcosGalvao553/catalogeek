<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Jobs\GenerateCatalogBatch;
use App\Services\PdfMergerService;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    /**
     * Download product image from URL and save locally
     */
    private function downloadProductImage($product)
    {
        if (!$product->image_url) {
            return null;
        }
        
        $filename = 'product_' . $product->id . '_' . md5($product->image_url) . '.jpg';
        $localPath = storage_path('app/public/product_images/' . $filename);
        
        // Se já existe, retornar o caminho
        if (file_exists($localPath)) {
            return $localPath;
        }
        
        // Criar diretório se não existir
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        try {
            // Baixar imagem
            $imageContent = @file_get_contents($product->image_url);
            if ($imageContent !== false) {
                file_put_contents($localPath, $imageContent);
                return $localPath;
            }
        } catch (\Exception $e) {
            // Se falhar, retornar null
            return null;
        }
        
        return null;
    }
    
    /**
     * Start catalog generation with batch processing
     */
    public function generateAsync(Request $request)
    {
        $request->validate([
            'funko' => 'boolean',
            'blokees' => 'boolean',
            'showStock' => 'boolean',
        ]);

        $query = Product::query();

        // Filtrar por marcaca
        $marcas = [];
        if ($request->funko) {
            $marcas[] = 'FUNKO';
        }
        if ($request->blokees) {
            $marcas[] = 'BLOKEES';
        }

        if (!empty($marcas)) {
            $query->whereIn('marca', $marcas);
        }

        // Filtrar por estoque
        if (!$request->showStock) {
            // Não mostrar produtos sem estoque
            $query->where('stock', '>', 0);
        }

        $products = $query->orderBy('name')->get();
        
        if ($products->isEmpty()) {
            return response()->json([
                'error' => 'Nenhum produto encontrado'
            ], 404);
        }
        
        // Dividir produtos em lotes de 64 produtos (cerca de 3-4 páginas por lote)
        $batchSize = 64;
        $batches = $products->chunk($batchSize);
        $sessionId = Str::uuid()->toString();
        
        // Inicializar progresso no cache
        cache([
            "catalog_progress_{$sessionId}" => [
                'completed' => 0,
                'total' => $batches->count(),
                'status' => 'processing'
            ]
        ], now()->addMinutes(30));
        
        // Despachar jobs em paralelo
        foreach ($batches as $index => $batch) {
            GenerateCatalogBatch::dispatch(
                $batch->toArray(),
                $request->showStock ?? false,
                $index,
                $sessionId
            );
        }
        
        return response()->json([
            'session_id' => $sessionId,
            'total_batches' => $batches->count(),
            'total_products' => $products->count(),
            'message' => 'Processamento iniciado'
        ]);
    }
    
    /**
     * Generate catalog using async batch processing (alias for generateAsync)
     */
    public function generate(Request $request)
    {
        return $this->generateAsync($request);
    }
    
    /**
     * Check progress of async catalog generation
     */
    public function checkProgress($sessionId)
    {
        $cacheKey = "catalog_progress_{$sessionId}";
        $progress = cache($cacheKey);
        
        if (!$progress) {
            return response()->json([
                'error' => 'Sessão não encontrada'
            ], 404);
        }
        
        $percentage = $progress['total'] > 0 
            ? round(($progress['completed'] / $progress['total']) * 100) 
            : 0;
        
        // Se completou todos os lotes, juntar os PDFs
        if ($progress['completed'] >= $progress['total'] && $progress['status'] === 'processing') {
            $this->mergeBatchPdfs($sessionId);
            $progress['status'] = 'completed';
            cache([$cacheKey => $progress], now()->addMinutes(30));
        }
        
        return response()->json([
            'completed' => $progress['completed'],
            'total' => $progress['total'],
            'percentage' => $percentage,
            'status' => $progress['status']
        ]);
    }
    
    /**
     * Download generated catalog
     */
    public function downloadCatalog($sessionId)
    {
        $finalFile = storage_path("app/private/temp_catalogs/final_{$sessionId}.pdf");
        
        if (!file_exists($finalFile)) {
            return response()->json([
                'error' => 'Catálogo não encontrado ou ainda sendo processado'
            ], 404);
        }
        
        return response()->download($finalFile, 'catalogo-produtos.pdf')->deleteFileAfterSend(true);
    }
    
    /**
     * Merge all batch PDFs into final catalog
     */
    private function mergeBatchPdfs($sessionId)
    {
        try {
            $tempDir = storage_path('app/private/temp_catalogs');
            $batchFiles = glob("{$tempDir}/catalog_batch_{$sessionId}_*.pdf");
            
            if (empty($batchFiles)) {
                \Log::error("No batch files found for session {$sessionId}");
                return false;
            }
            
            \Log::info("Found " . count($batchFiles) . " batch files for session {$sessionId}");
            
            // Ordenar por número do lote
            usort($batchFiles, function($a, $b) {
                preg_match('/_(\d+)\.pdf$/', $a, $matchA);
                preg_match('/_(\d+)\.pdf$/', $b, $matchB);
                return intval($matchA[1]) - intval($matchB[1]);
            });
            
            $merger = new PdfMergerService();
            $finalFile = "{$tempDir}/final_{$sessionId}.pdf";
            
            \Log::info("Starting PDF merge for session {$sessionId}");
            $success = $merger->merge($batchFiles, $finalFile);
            
            if (!$success || !file_exists($finalFile)) {
                \Log::error("Failed to merge PDFs for session {$sessionId}");
                return false;
            }
            
            \Log::info("PDF merge successful, final file size: " . filesize($finalFile) . " bytes");
            
            // Limpar arquivos temporários dos lotes APENAS se o merge foi bem-sucedido
            foreach ($batchFiles as $batchFile) {
                @unlink($batchFile);
            }
            
            \Log::info("Batch files cleaned up for session {$sessionId}");
            
            return true;
        } catch (\Exception $e) {
            \Log::error("Error merging batch PDFs for session {$sessionId}: " . $e->getMessage());
            return false;
        }
    }

    public function progress(Request $request)
    {
        // Simula progresso de geração
        $progress = rand(10, 100);
        return response()->json(['progress' => $progress]);
    }
}
