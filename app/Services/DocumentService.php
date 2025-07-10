<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class DocumentService
{
    protected TextExtractorService $textExtractor;
    protected SearchService $searchService;

    public function __construct(TextExtractorService $textExtractor, SearchService $searchService)
    {
        $this->textExtractor = $textExtractor;
        $this->searchService = $searchService;
    }

    /**
     * Store uploaded document and extract content
     */
    public function storeDocument(UploadedFile $file, Workspace $workspace, ?string $title = null, ?int $projectId = null): Document
    {
        try {
            // Validate file
            $this->validateFile($file);
            
            // Generate unique filename
            $filename = $this->generateUniqueFilename($file);
            
            // Store file in workspace directory
            $filePath = $file->storeAs($workspace->getStoragePath(), $filename, 'local');
            $fullPath = Storage::path($filePath);
            
            // Extract text content
            $content = $this->textExtractor->extractText($fullPath, $file->getClientOriginalExtension());
            
            // Create document record
            $document = Document::create([
                'workspace_id' => $workspace->id,
                'project_id' => $projectId,
                'title' => $title ?: $this->generateTitle($file->getClientOriginalName()),
                'content' => $content,
                'file_path' => $filePath,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'original_filename' => $file->getClientOriginalName(),
                'search_index_id' => Str::uuid(),
            ]);
            
            // Index document for search
            $this->indexDocument($document, $workspace);
            
            Log::info('Document stored and indexed successfully', [
                'workspace_id' => $workspace->id,
                'document_id' => $document->id,
                'filename' => $file->getClientOriginalName(),
                'content_length' => strlen($content)
            ]);
            
            return $document;
            
        } catch (Exception $e) {
            Log::error('Failed to store document', [
                'workspace_id' => $workspace->id,
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update document content and re-index
     */
    public function updateDocument(Document $document, array $data): Document
    {
        $oldIndexed = $document->isIndexed();
        
        $document->update($data);
        
        // Re-index if content changed and was previously indexed
        if (isset($data['content']) && $oldIndexed) {
            $this->indexDocument($document, $document->workspace);
        }
        
        return $document->fresh();
    }

    /**
     * Delete document and remove from search index
     */
    public function deleteDocument(Document $document): bool
    {
        try {
            // Remove from search index
            if ($document->isIndexed()) {
                $document->unsearchable();
            }
            
            // Delete physical file
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
            
            // Delete database record
            $result = $document->delete();
            
            Log::info('Document deleted successfully', [
                'document_id' => $document->id,
                'filename' => $document->original_filename
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Failed to delete document', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Index document for search
     */
    public function indexDocument(Document $document, Workspace $workspace): void
    {
        try {
            $document->searchable();
            $document->markAsIndexed();
            $workspace->markAsIndexed();
            
            Log::info('Document indexed successfully', [
                'workspace_id' => $workspace->id,
                'document_id' => $document->id
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to index document', [
                'workspace_id' => $workspace->id,
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Bulk index documents for a workspace
     */
    public function bulkIndexDocuments(Workspace $workspace): int
    {
        $unindexedDocuments = $workspace->documents()->whereNull('indexed_at')->get();
        $count = 0;
        
        foreach ($unindexedDocuments as $document) {
            try {
                $this->indexDocument($document, $workspace);
                $count++;
            } catch (Exception $e) {
                Log::warning('Failed to index document in bulk operation', [
                    'workspace_id' => $workspace->id,
                    'document_id' => $document->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $count;
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!$this->textExtractor->isSupported($extension)) {
            throw new Exception("Unsupported file type: {$extension}");
        }
        
        // Check file size
        if ($file->getSize() > $this->textExtractor->getMaxFileSize()) {
            throw new Exception('File size exceeds maximum allowed size');
        }
        
        // Check if file is valid
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }
    }

    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $basename = Str::slug($basename);
        
        return $basename . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    /**
     * Generate document title from filename
     */
    protected function generateTitle(string $filename): string
    {
        $title = pathinfo($filename, PATHINFO_FILENAME);
        return Str::title(str_replace(['_', '-'], ' ', $title));
    }

    /**
     * Get document statistics for workspace
     */
    public function getStatistics(Workspace $workspace): array
    {
        return $workspace->getStatistics();
    }
}
