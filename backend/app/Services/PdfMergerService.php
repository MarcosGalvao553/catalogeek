<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PdfMergerService
{
    /**
     * Merge multiple PDF files into one
     *
     * @param array $pdfPaths Array of PDF file paths
     * @param string $outputPath Output path for merged PDF
     * @return bool
     */
    public function merge(array $pdfPaths, string $outputPath): bool
    {
        try {
            $fpdi = new Fpdi();
            
            Log::info("Starting merge of " . count($pdfPaths) . " PDFs to {$outputPath}");
            
            foreach ($pdfPaths as $pdfPath) {
                if (!file_exists($pdfPath)) {
                    Log::warning("PDF file not found: {$pdfPath}");
                    continue;
                }
                
                $pageCount = $fpdi->setSourceFile($pdfPath);
                
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $fpdi->importPage($pageNo);
                    $size = $fpdi->getTemplateSize($templateId);
                    
                    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $fpdi->useTemplate($templateId);
                }
            }
            
            // Verificar se há páginas antes de salvar
            if ($fpdi->PageNo() === 0) {
                Log::error("No pages to merge!");
                return false;
            }
            
            Log::info("Saving merged PDF with {$fpdi->PageNo()} pages to {$outputPath}");
            $fpdi->Output($outputPath, 'F');
            
            // Verificar se o arquivo foi realmente criado
            if (!file_exists($outputPath)) {
                Log::error("Output file was not created: {$outputPath}");
                return false;
            }
            
            Log::info("Merge successful, file size: " . filesize($outputPath) . " bytes");
            return true;
        } catch (\Exception $e) {
            Log::error('PDF merge error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Merge PDFs from storage paths
     *
     * @param array $storagePaths Array of storage paths (relative to storage/app)
     * @param string $outputStoragePath Output storage path
     * @return bool
     */
    public function mergeFromStorage(array $storagePaths, string $outputStoragePath): bool
    {
        $tempPaths = [];
        
        foreach ($storagePaths as $storagePath) {
            $fullPath = storage_path('app/' . $storagePath);
            if (file_exists($fullPath)) {
                $tempPaths[] = $fullPath;
            }
        }
        
        if (empty($tempPaths)) {
            return false;
        }
        
        $outputFullPath = storage_path('app/' . $outputStoragePath);
        
        // Criar diretório se não existir
        $dir = dirname($outputFullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $this->merge($tempPaths, $outputFullPath);
    }
}
