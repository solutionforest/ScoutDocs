<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\Document;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    protected Workspace $workspace;
    protected string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->workspace = Workspace::factory()->create();
        $this->apiKey = $this->workspace->api_key;
    }

    public function test_workspace_middleware_allows_project_access()
    {
        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->get('/api/projects');

        $response->assertStatus(200);
    }

    public function test_can_list_workspace_projects()
    {
        Project::factory(3)->create(['workspace_id' => $this->workspace->id]);
        Project::factory(2)->create(); // Other workspace projects

        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->get('/api/projects');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'color',
                    'is_active',
                    'documents_count',
                    'created_at',
                    'updated_at'
                ]
            ],
            'links',
            'meta'
        ]);

        $projects = $response->json('data');
        $this->assertCount(3, $projects); // Only workspace projects
    }

    public function test_can_create_project()
    {
        $projectData = [
            'name' => 'Marketing Materials',
            'description' => 'All marketing related documents',
            'color' => '#FF5733'
        ];

        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->postJson('/api/projects', $projectData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Project created successfully'
        ]);

        $this->assertDatabaseHas('projects', [
            'workspace_id' => $this->workspace->id,
            'name' => 'Marketing Materials',
            'description' => 'All marketing related documents',
            'color' => '#FF5733'
        ]);
    }

    public function test_can_get_specific_project()
    {
        $project = Project::factory()->create(['workspace_id' => $this->workspace->id]);

        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->get("/api/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug
            ]
        ]);
    }

    public function test_cannot_access_project_from_different_workspace()
    {
        $otherWorkspace = Workspace::factory()->create();
        $otherProject = Project::factory()->create(['workspace_id' => $otherWorkspace->id]);

        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->get("/api/projects/{$otherProject->id}");

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Project not found'
        ]);
    }

    public function test_can_get_project_statistics()
    {
        $project = Project::factory()->create(['workspace_id' => $this->workspace->id]);
        
        // Create documents for the project
        Document::factory(3)->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $project->id,
            'file_size' => 1024,
            'file_type' => 'pdf',
            'indexed_at' => now(),
            'created_at' => now()->subDays(1)
        ]);
        
        Document::factory(2)->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $project->id,
            'file_size' => 512,
            'file_type' => 'docx',
            'indexed_at' => null,
            'created_at' => now()->subDays(10)
        ]);

        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->get("/api/projects/{$project->id}/statistics");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_documents',
                'indexed_documents',
                'unindexed_documents',
                'total_size',
                'formatted_total_size',
                'recent_uploads',
                'file_types'
            ]
        ]);

        $stats = $response->json('data');
        $this->assertEquals(5, $stats['total_documents']);
        $this->assertEquals(3, $stats['indexed_documents']);
        $this->assertEquals(2, $stats['unindexed_documents']);
        $this->assertEquals(3, $stats['recent_uploads']); // 3 PDF documents created in last 7 days
    }

    public function test_can_filter_documents_by_project()
    {
        $project1 = Project::factory()->create(['workspace_id' => $this->workspace->id]);
        $project2 = Project::factory()->create(['workspace_id' => $this->workspace->id]);
        
        // Create documents for different projects
        Document::factory(3)->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $project1->id
        ]);
        
        Document::factory(2)->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => $project2->id
        ]);
        
        Document::factory(1)->create([
            'workspace_id' => $this->workspace->id,
            'project_id' => null // No project
        ]);

        // Test filtering by project1
        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->get("/api/documents?project_id={$project1->id}");

        $response->assertStatus(200);
        $documents = $response->json('data');
        $this->assertCount(3, $documents);

        // Test filtering by project2
        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->get("/api/documents?project_id={$project2->id}");

        $response->assertStatus(200);
        $documents = $response->json('data');
        $this->assertCount(2, $documents);

        // Test all documents (no filter)
        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->get('/api/documents');

        $response->assertStatus(200);
        $documents = $response->json('data');
        $this->assertCount(6, $documents); // All documents
    }

    public function test_can_upload_document_to_project()
    {
        $project = Project::factory()->create(['workspace_id' => $this->workspace->id]);
        
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'test.txt', 
            'This is test content for the project document.'
        );

        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->post('/api/documents', [
            'file' => $file,
            'title' => 'Project Test Document',
            'project_id' => $project->id
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Document uploaded and indexed successfully'
        ]);

        $this->assertDatabaseHas('documents', [
            'workspace_id' => $this->workspace->id,
            'project_id' => $project->id,
            'title' => 'Project Test Document'
        ]);
    }

    public function test_cannot_upload_document_to_project_in_different_workspace()
    {
        $otherWorkspace = Workspace::factory()->create();
        $otherProject = Project::factory()->create(['workspace_id' => $otherWorkspace->id]);
        
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'test.txt', 
            'This is test content.'
        );

        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->post('/api/documents', [
            'file' => $file,
            'title' => 'Test Document',
            'project_id' => $otherProject->id
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Project not found in this workspace'
        ]);
    }

    public function test_project_validation_rules()
    {
        // Test required name
        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->postJson('/api/projects', []);

        $response->assertStatus(422);

        // Test invalid color format
        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->postJson('/api/projects', [
            'name' => 'Test Project',
            'color' => 'invalid-color'
        ]);

        $response->assertStatus(422);

        // Test valid data
        $response = $this->withHeaders([
            'X-Workspace-Key' => $this->apiKey,
        ])->postJson('/api/projects', [
            'name' => 'Valid Project',
            'description' => 'Valid description',
            'color' => '#FF5733'
        ]);

        $response->assertStatus(201);
    }
}
