<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Document extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'workspace_id',
        'project_id',
        'title',
        'content',
        'file_path',
        'file_type',
        'file_size',
        'original_filename',
        'indexed_at',
        'search_index_id',
    ];

    // ...existing code...

    /**
     * Get the workspace that owns the document
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the project that owns the document
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // ...existing code...

    protected $casts = [
        'indexed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'file_type' => $this->file_type,
            'original_filename' => $this->original_filename,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'documents';
    }

    /**
     * Check if document is indexed.
     */
    public function isIndexed(): bool
    {
        return !is_null($this->indexed_at);
    }

    /**
     * Mark document as indexed.
     */
    public function markAsIndexed(): void
    {
        $this->update(['indexed_at' => now()]);
    }

    /**
     * Get file size in human readable format.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
