<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = ['pdf', 'doc', 'docx', 'txt'];
        $fileType = $this->faker->randomElement($fileTypes);
        $filename = $this->faker->words(3, true);
        
        return [
            'workspace_id' => Workspace::factory(),
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraphs(3, true),
            'file_path' => 'documents/' . Str::slug($filename) . '.' . $fileType,
            'file_type' => $fileType,
            'file_size' => $this->faker->numberBetween(1024, 5242880), // 1KB to 5MB
            'original_filename' => $filename . '.' . $fileType,
            'search_index_id' => Str::uuid(),
            'indexed_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the document is unindexed.
     */
    public function unindexed(): static
    {
        return $this->state(fn (array $attributes) => [
            'indexed_at' => null,
        ]);
    }

    /**
     * Indicate that the document is a PDF.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'pdf',
            'file_path' => str_replace(pathinfo($attributes['file_path'], PATHINFO_EXTENSION), 'pdf', $attributes['file_path']),
            'original_filename' => str_replace(pathinfo($attributes['original_filename'], PATHINFO_EXTENSION), 'pdf', $attributes['original_filename']),
        ]);
    }

    /**
     * Indicate that the document is a Word document.
     */
    public function word(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'docx',
            'file_path' => str_replace(pathinfo($attributes['file_path'], PATHINFO_EXTENSION), 'docx', $attributes['file_path']),
            'original_filename' => str_replace(pathinfo($attributes['original_filename'], PATHINFO_EXTENSION), 'docx', $attributes['original_filename']),
        ]);
    }

    /**
     * Indicate that the document is a text file.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'txt',
            'file_path' => str_replace(pathinfo($attributes['file_path'], PATHINFO_EXTENSION), 'txt', $attributes['file_path']),
            'original_filename' => str_replace(pathinfo($attributes['original_filename'], PATHINFO_EXTENSION), 'txt', $attributes['original_filename']),
        ]);
    }

    /**
     * Create a document with specific content for testing search.
     */
    public function withSearchableContent(string $title, string $content): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
            'content' => $content,
            'indexed_at' => now(),
        ]);
    }

    /**
     * Create a recent document (created in the last 7 days).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Create a large document.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_size' => $this->faker->numberBetween(5242880, 10485760), // 5MB to 10MB
            'content' => str_repeat($this->faker->paragraphs(10, true), 5),
        ]);
    }
}
