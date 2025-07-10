<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'description' => $this->faker->optional()->sentence(),
            'api_key' => 'workspace_' . Str::random(40),
            'is_active' => true,
            'settings' => [
                'max_file_size' => 10485760, // 10MB
                'allowed_file_types' => ['pdf', 'doc', 'docx', 'txt'],
                'auto_index' => true
            ],
        ];
    }

    /**
     * Indicate that the workspace is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
