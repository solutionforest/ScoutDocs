<?php

namespace App\Services;

use Exception;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

class TextExtractorService
{
    /**
     * Extract text content from uploaded file
     */
    public function extractText(string $filePath, string $fileType): string
    {
        try {
            switch (strtolower($fileType)) {
                case 'pdf':
                    return $this->extractFromPdf($filePath);
                case 'doc':
                case 'docx':
                    return $this->extractFromWord($filePath);
                case 'txt':
                    return $this->extractFromText($filePath);
                default:
                    throw new Exception("Unsupported file type: {$fileType}");
            }
        } catch (Exception $e) {
            Log::error("Text extraction failed for file: {$filePath}", [
                'error' => $e->getMessage(),
                'file_type' => $fileType
            ]);
            throw $e;
        }
    }

    /**
     * Extract text from PDF file
     */
    private function extractFromPdf(string $filePath): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        
        $text = $pdf->getText();
        
        if (empty(trim($text))) {
            throw new Exception('No text content found in PDF file');
        }
        
        return $this->cleanText($text);
    }

    /**
     * Extract text from Word document
     */
    private function extractFromWord(string $filePath): string
    {
        $phpWord = IOFactory::load($filePath);
        $text = '';
        
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $childElement) {
                        if (method_exists($childElement, 'getText')) {
                            $text .= $childElement->getText() . "\n";
                        }
                    }
                }
            }
        }
        
        if (empty(trim($text))) {
            throw new Exception('No text content found in Word document');
        }
        
        return $this->cleanText($text);
    }

    /**
     * Extract text from plain text file
     */
    private function extractFromText(string $filePath): string
    {
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new Exception('Failed to read text file');
        }
        
        if (empty(trim($content))) {
            throw new Exception('Text file is empty');
        }
        
        return $this->cleanText($content);
    }

    /**
     * Clean and normalize extracted text
     */
    private function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Remove excessive line breaks
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Get supported file types
     */
    public function getSupportedTypes(): array
    {
        return ['pdf', 'doc', 'docx', 'txt'];
    }

    /**
     * Check if file type is supported
     */
    public function isSupported(string $fileType): bool
    {
        return in_array(strtolower($fileType), $this->getSupportedTypes());
    }

    /**
     * Get maximum file size in bytes (default 10MB)
     */
    public function getMaxFileSize(): int
    {
        return config('filesystems.max_file_size', 10485760); // 10MB
    }
}
