<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="Project",
 *     type="object",
 *     title="Project",
 *     description="Project model",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Project ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="workspace_id",
 *         type="integer",
 *         description="Workspace ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Project name",
 *         example="My Project"
 *     ),
 *     @OA\Property(
 *         property="slug",
 *         type="string",
 *         description="Project slug",
 *         example="my-project"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         description="Project description",
 *         example="A sample project for document management"
 *     ),
 *     @OA\Property(
 *         property="color",
 *         type="string",
 *         nullable=true,
 *         description="Project color (hex code)",
 *         example="#007bff"
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         description="Whether the project is active",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         nullable=true,
 *         description="Project settings (JSON object)",
 *         example={"indexing_enabled": true, "auto_extract": false}
 *     ),
 *     @OA\Property(
 *         property="last_indexed_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Last indexing timestamp",
 *         example="2024-01-15T10:30:00Z"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Creation timestamp",
 *         example="2024-01-01T10:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Last update timestamp",
 *         example="2024-01-15T10:30:00Z"
 *     )
 * )
 */
class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'description',
        'color',
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
        
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    /**
     * Get the workspace that owns the project
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get documents belonging to this project
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get storage path for this project
     */
    public function getStoragePath(): string
    {
        return "workspaces/{$this->workspace->slug}/projects/{$this->slug}/documents";
    }

    /**
     * Get search index name for this project
     */
    public function getSearchIndexName(): string
    {
        return "documents_{$this->workspace->slug}_{$this->slug}";
    }

    /**
     * Get project statistics
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

    /**
     * Find project by workspace and slug
     */
    public static function findByWorkspaceAndSlug(Workspace $workspace, string $slug): ?self
    {
        return static::where('workspace_id', $workspace->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }
}
