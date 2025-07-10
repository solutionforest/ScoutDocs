<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'api_key',
        'is_active',
        'settings',
        'last_indexed_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'last_indexed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($workspace) {
            if (empty($workspace->slug)) {
                $workspace->slug = Str::slug($workspace->name);
            }
            if (empty($workspace->api_key)) {
                $workspace->api_key = 'ws_' . Str::random(32);
            }
        });
    }

    /**
     * Get documents belonging to this workspace
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get projects belonging to this workspace
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get workspace by API key
     */
    public static function findByApiKey(string $apiKey): ?self
    {
        return static::where('api_key', $apiKey)->where('is_active', true)->first();
    }

    /**
     * Get search index name for this workspace
     */
    public function getSearchIndexName(): string
    {
        return "documents_{$this->slug}";
    }

    /**
     * Get storage path for this workspace
     */
    public function getStoragePath(): string
    {
        return "workspaces/{$this->slug}/documents";
    }

    /**
     * Get search index path for this workspace
     */
    public function getSearchIndexPath(): string
    {
        return storage_path("search-index/{$this->slug}");
    }

    /**
     * Get workspace statistics
     */
    public function getStatistics(): array
    {
        $totalDocuments = $this->documents()->count();
        $indexedDocuments = $this->documents()->whereNotNull('indexed_at')->count();
        $totalSize = $this->documents()->sum('file_size') ?: 0;
        $recentUploads = $this->documents()->where('created_at', '>=', now()->subDays(7))->count();
        
        return [
            'total_documents' => $totalDocuments,
            'indexed_documents' => $indexedDocuments,
            'unindexed_documents' => $totalDocuments - $indexedDocuments,
            'total_size' => $totalSize,
            'formatted_total_size' => $this->formatBytes($totalSize),
            'recent_uploads' => $recentUploads,
            'file_types' => $this->documents()
                ->groupBy('file_type')
                ->selectRaw('file_type, count(*) as count')
                ->pluck('count', 'file_type')
                ->toArray(),
            'last_indexed_at' => $this->last_indexed_at,
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Update last indexed timestamp
     */
    public function markAsIndexed(): void
    {
        $this->update(['last_indexed_at' => now()]);
    }
}
