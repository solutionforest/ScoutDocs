<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'formatted_file_size' => $this->formatted_file_size,
            'original_filename' => $this->original_filename,
            'indexed' => $this->isIndexed(),
            'indexed_at' => $this->indexed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'content' => $this->when(
                $request->query('include_content') === 'true',
                $this->content
            ),
        ];
    }
}
