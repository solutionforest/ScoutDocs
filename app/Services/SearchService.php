<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Workspace;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use TeamTNT\TNTSearch\TNTSearch;
use Exception;

class SearchService
{
    protected TNTSearch $tnt;

    public function __construct()
    {
        $this->tnt = new TNTSearch;
        $this->configureTNTSearch();
    }

    /**
     * Search documents with query and filters for a specific workspace
     */
    public function search(Workspace $workspace, string $query, array $filters = [], int $page = 1, int $perPage = 10): array
    {
        try {
            $startTime = microtime(true);
            
            // Use Laravel Scout for basic search within workspace
            $searchQuery = Document::search($query)->where('workspace_id', $workspace->id);
            
            // Apply filters
            if (isset($filters['file_type']) && !empty($filters['file_type'])) {
                $searchQuery->whereIn('file_type', (array) $filters['file_type']);
            }
            
            if (isset($filters['date_from'])) {
                $searchQuery->where('created_at', '>=', $filters['date_from']);
            }
            
            if (isset($filters['date_to'])) {
                $searchQuery->where('created_at', '<=', $filters['date_to']);
            }
            
            // Get paginated results
            $results = $searchQuery->paginate($perPage, 'page', $page);
            
            $searchTime = microtime(true) - $startTime;
            
            // Format results with snippets
            $formattedResults = $this->formatSearchResults($results->items(), $query);
            
            return [
                'results' => $formattedResults,
                'pagination' => [
                    'total' => $results->total(),
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                ],
                'query' => $query,
                'search_time' => round($searchTime, 3),
                'total_indexed' => $this->getTotalIndexedDocuments($workspace),
                'workspace' => [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'slug' => $workspace->slug,
                ]
            ];
            
        } catch (Exception $e) {
            Log::error('Search failed', [
                'workspace_id' => $workspace->id,
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get search suggestions based on partial query
     */
    public function getSuggestions(Workspace $workspace, string $partialQuery, int $limit = 5): array
    {
        try {
            // Simple implementation - get documents that match title or content in workspace
            $suggestions = $workspace->documents()
                ->where(function ($query) use ($partialQuery) {
                    $query->where('title', 'LIKE', "%{$partialQuery}%")
                          ->orWhere('content', 'LIKE', "%{$partialQuery}%");
                })
                ->limit($limit)
                ->pluck('title')
                ->unique()
                ->values()
                ->toArray();
            
            return $suggestions;
            
        } catch (Exception $e) {
            Log::error('Failed to get search suggestions', [
                'workspace_id' => $workspace->id,
                'query' => $partialQuery,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Advanced search with boolean operators
     */
    public function advancedSearch(Workspace $workspace, array $parameters): array
    {
        $query = $parameters['query'] ?? '';
        $filters = $parameters['filters'] ?? [];
        $sort = $parameters['sort'] ?? 'relevance';
        $page = $parameters['page'] ?? 1;
        $perPage = $parameters['per_page'] ?? 10;
        
        $results = $this->search($workspace, $query, $filters, $page, $perPage);
        
        // Apply custom sorting if needed
        if ($sort !== 'relevance') {
            $results['results'] = $this->applySorting($results['results'], $sort);
        }
        
        // Add search parameters to results
        $results['filters'] = $filters;
        $results['sort'] = $sort;
        
        return $results;
    }

    /**
     * Rebuild search index for workspace
     */
    public function rebuildIndex(Workspace $workspace): array
    {
        try {
            $startTime = microtime(true);
            
            // Get all documents for workspace
            $documents = $workspace->documents;
            
            // Remove all from index first
            $documents->unsearchable();
            
            // Re-add to index
            $indexed = 0;
            foreach ($documents as $document) {
                try {
                    $document->searchable();
                    $document->markAsIndexed();
                    $indexed++;
                } catch (Exception $e) {
                    Log::warning('Failed to index document during rebuild', [
                        'workspace_id' => $workspace->id,
                        'document_id' => $document->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $rebuildTime = microtime(true) - $startTime;
            $workspace->markAsIndexed();
            
            Log::info('Search index rebuilt', [
                'workspace_id' => $workspace->id,
                'total_documents' => $documents->count(),
                'indexed_documents' => $indexed,
                'rebuild_time' => $rebuildTime
            ]);
            
            return [
                'total_documents' => $documents->count(),
                'indexed_documents' => $indexed,
                'processing_time' => round($rebuildTime, 3)
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to rebuild search index', [
                'workspace_id' => $workspace->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get index status and statistics for workspace
     */
    public function getIndexStatus(Workspace $workspace): array
    {
        $totalDocuments = $workspace->documents()->count();
        $indexedDocuments = $workspace->documents()->whereNotNull('indexed_at')->count();
        $lastIndexed = $workspace->documents()->whereNotNull('indexed_at')
            ->latest('indexed_at')
            ->value('indexed_at');
        
        $indexPath = $this->getIndexPath($workspace);
        $indexSize = file_exists($indexPath) ? filesize($indexPath) : 0;
        
        return [
            'total_documents' => $totalDocuments,
            'indexed_documents' => $indexedDocuments,
            'unindexed_documents' => $totalDocuments - $indexedDocuments,
            'index_size' => $indexSize,
            'formatted_index_size' => $this->formatBytes($indexSize),
            'last_updated' => $lastIndexed,
            'index_health' => $this->getIndexHealth($workspace)
        ];
    }

    /**
     * Format search results with snippets and highlighting
     */
    protected function formatSearchResults(array $documents, string $query): array
    {
        $results = [];
        $queryTerms = $this->extractQueryTerms($query);
        
        foreach ($documents as $document) {
            $results[] = [
                'id' => $document->id,
                'title' => $document->title,
                'snippet' => $this->generateSnippet($document->content, $queryTerms),
                'file_type' => $document->file_type,
                'file_size' => $document->file_size,
                'formatted_file_size' => $document->formatted_file_size,
                'original_filename' => $document->original_filename,
                'created_at' => $document->created_at,
                'score' => $this->calculateRelevanceScore($document, $queryTerms)
            ];
        }
        
        return $results;
    }

    /**
     * Generate snippet with highlighted search terms
     */
    protected function generateSnippet(string $content, array $queryTerms, int $snippetLength = 200): string
    {
        if (empty($queryTerms)) {
            return substr($content, 0, $snippetLength) . '...';
        }
        
        // Find the first occurrence of any query term
        $positions = [];
        foreach ($queryTerms as $term) {
            $pos = stripos($content, $term);
            if ($pos !== false) {
                $positions[] = $pos;
            }
        }
        
        if (empty($positions)) {
            return substr($content, 0, $snippetLength) . '...';
        }
        
        // Start snippet from the earliest match
        $startPos = min($positions);
        $startPos = max(0, $startPos - 50); // Add some context before
        
        $snippet = substr($content, $startPos, $snippetLength);
        
        // Highlight query terms
        foreach ($queryTerms as $term) {
            $snippet = preg_replace('/(' . preg_quote($term, '/') . ')/i', '<mark>$1</mark>', $snippet);
        }
        
        return $snippet . '...';
    }

    /**
     * Extract individual terms from search query
     */
    protected function extractQueryTerms(string $query): array
    {
        // Remove quotes and split by spaces
        $query = str_replace(['"', "'"], '', $query);
        $terms = preg_split('/\s+/', trim($query));
        
        // Filter out empty terms and short words
        return array_filter($terms, function ($term) {
            return strlen($term) >= 2;
        });
    }

    /**
     * Calculate relevance score for a document
     */
    protected function calculateRelevanceScore(Document $document, array $queryTerms): float
    {
        if (empty($queryTerms)) {
            return 0.0;
        }
        
        $score = 0.0;
        $titleWeight = 2.0;
        $contentWeight = 1.0;
        
        foreach ($queryTerms as $term) {
            // Title matches are weighted higher
            $titleMatches = substr_count(strtolower($document->title), strtolower($term));
            $contentMatches = substr_count(strtolower($document->content), strtolower($term));
            
            $score += ($titleMatches * $titleWeight) + ($contentMatches * $contentWeight);
        }
        
        // Normalize by content length
        $normalizedScore = $score / (strlen($document->content) / 1000);
        
        return min(1.0, max(0.0, $normalizedScore));
    }

    /**
     * Apply custom sorting to search results
     */
    protected function applySorting(array $results, string $sort): array
    {
        switch ($sort) {
            case 'date_desc':
                usort($results, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
                break;
            case 'date_asc':
                usort($results, function ($a, $b) {
                    return strtotime($a['created_at']) - strtotime($b['created_at']);
                });
                break;
            case 'title_asc':
                usort($results, function ($a, $b) {
                    return strcmp($a['title'], $b['title']);
                });
                break;
            case 'title_desc':
                usort($results, function ($a, $b) {
                    return strcmp($b['title'], $a['title']);
                });
                break;
            case 'size_desc':
                usort($results, function ($a, $b) {
                    return $b['file_size'] - $a['file_size'];
                });
                break;
            case 'size_asc':
                usort($results, function ($a, $b) {
                    return $a['file_size'] - $b['file_size'];
                });
                break;
            default:
                // Default to relevance (score)
                usort($results, function ($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
        }
        
        return $results;
    }

    /**
     * Configure TNT Search
     */
    protected function configureTNTSearch(): void
    {
        $config = config('scout.tntsearch');
        $dbConnection = config('database.default');
        
        if ($dbConnection === 'sqlite') {
            $this->tnt->loadConfig([
                'driver' => 'sqlite',
                'database' => database_path('database.sqlite'),
                'storage' => $config['storage'] ?? storage_path('search-index'),
            ]);
        } else {
            $this->tnt->loadConfig([
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host', '127.0.0.1'),
                'database' => config('database.connections.mysql.database', 'laravel'),
                'username' => config('database.connections.mysql.username', 'root'),
                'password' => config('database.connections.mysql.password', ''),
                'storage' => $config['storage'] ?? storage_path('search-index'),
            ]);
        }
    }

    /**
     * Get total number of indexed documents in workspace
     */
    protected function getTotalIndexedDocuments(Workspace $workspace): int
    {
        return $workspace->documents()->whereNotNull('indexed_at')->count();
    }

    /**
     * Get index file size
     */
    protected function getIndexSize(): string
    {
        $indexPath = config('scout.tntsearch.storage', storage_path('search-index'));
        $totalSize = 0;
        
        if (is_dir($indexPath)) {
            $files = glob($indexPath . '/*.index');
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $totalSize += filesize($file);
                }
            }
        }
        
        return $this->formatBytes($totalSize);
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get workspace-specific index file path
     */
    protected function getIndexPath(Workspace $workspace): string
    {
        $indexDir = config('scout.tntsearch.storage', storage_path('search-index'));
        return $indexDir . '/workspace_' . $workspace->id . '.index';
    }

    /**
     * Get index health status for workspace
     */
    protected function getIndexHealth(Workspace $workspace): string
    {
        $totalDocuments = $workspace->documents()->count();
        $indexedDocuments = $workspace->documents()->whereNotNull('indexed_at')->count();
        
        if ($totalDocuments === 0) {
            return 'healthy';
        }
        
        $indexRatio = $indexedDocuments / $totalDocuments;
        
        if ($indexRatio >= 0.95) {
            return 'healthy';
        } elseif ($indexRatio >= 0.8) {
            return 'warning';
        } else {
            return 'error';
        }
    }
}
