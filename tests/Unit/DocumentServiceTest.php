<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Models\Workspace;
use App\Services\DocumentService;
use App\Services\SearchService;
use App\Services\TextExtractorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $documentService;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('local');
        
        $this->workspace = Workspace::factory()->create();
        
        $this->documentService = new DocumentService(
            new TextExtractorService(),
            new SearchService()
        );
    }

    public function test_can_store_document_in_workspace()
    {
        $file = UploadedFile::fake()->createWithContent('test.txt', 'This is test content for the document.');
        
        $document = $this->documentService->storeDocument($file, $this->workspace, 'Test Document');
        
        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals($this->workspace->id, $document->workspace_id);
        $this->assertEquals('Test Document', $document->title);
        $this->assertEquals('txt', $document->file_type);
        $this->assertNotNull($document->file_path);
        $this->assertNotNull($document->search_index_id);
        
        // Verify file was stored in workspace directory
        $this->assertTrue(Storage::exists($document->file_path));
        $this->assertStringContainsString($this->workspace->slug, $document->file_path);
    }

    public function test_can_update_document()
    {
        $document = Document::factory()->create(['workspace_id' => $this->workspace->id]);
        
        $updatedDocument = $this->documentService->updateDocument($document, [
            'title' => 'Updated Title',
            'content' => 'Updated content'
        ]);
        
        $this->assertEquals('Updated Title', $updatedDocument->title);
        $this->assertEquals('Updated content', $updatedDocument->content);
    }

    public function test_can_delete_document()
    {
        $document = Document::factory()->create([
            'workspace_id' => $this->workspace->id,
            'file_path' => 'test-file.txt'
        ]);
        
        // Create fake file
        Storage::put($document->file_path, 'test content');
        
        $result = $this->documentService->deleteDocument($document);
        
        $this->assertTrue($result);
        $this->assertFalse(Storage::exists($document->file_path));
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_can_bulk_index_documents()
    {
        // Create unindexed documents
        Document::factory(3)->create([
            'workspace_id' => $this->workspace->id,
            'indexed_at' => null
        ]);
        
        $count = $this->documentService->bulkIndexDocuments($this->workspace);
        
        $this->assertEquals(3, $count);
        $this->assertEquals(3, $this->workspace->documents()->whereNotNull('indexed_at')->count());
    }

    public function test_can_get_workspace_statistics()
    {
        // Create documents with different states
        Document::factory(3)->create([
            'workspace_id' => $this->workspace->id,
            'file_size' => 1024,
            'file_type' => 'pdf',
            'indexed_at' => now(),
            'created_at' => now()->subDays(1)
        ]);
        
        Document::factory(2)->create([
            'workspace_id' => $this->workspace->id,
            'file_size' => 512,
            'file_type' => 'docx',
            'indexed_at' => null,
            'created_at' => now()->subDays(10)
        ]);
        
        $stats = $this->documentService->getStatistics($this->workspace);
        
        $this->assertEquals(5, $stats['total_documents']);
        $this->assertEquals(3, $stats['indexed_documents']);
        $this->assertEquals(2, $stats['unindexed_documents']);
        $this->assertEquals(4096, $stats['total_size']); // (3 * 1024) + (2 * 512)
        $this->assertEquals('4 KB', $stats['formatted_total_size']);
        $this->assertArrayHasKey('file_types', $stats);
        $this->assertEquals(3, $stats['file_types']['pdf']);
        $this->assertEquals(2, $stats['file_types']['docx']);
        $this->assertEquals(3, $stats['recent_uploads']); // 3 PDF documents created in last 7 days
    }

    public function test_validates_file_upload()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported file type: xyz');
        
        $file = UploadedFile::fake()->create('test.xyz', 100);
        
        $this->documentService->storeDocument($file, $this->workspace);
    }

    public function test_generates_unique_filename()
    {
        $file1 = UploadedFile::fake()->createWithContent('test.txt', 'This is test content for document 1.');
        $file2 = UploadedFile::fake()->createWithContent('test.txt', 'This is test content for document 2.');
        
        $document1 = $this->documentService->storeDocument($file1, $this->workspace);
        $document2 = $this->documentService->storeDocument($file2, $this->workspace);
        
        $this->assertNotEquals($document1->file_path, $document2->file_path);
    }

    public function test_generates_title_from_filename()
    {
        $file = UploadedFile::fake()->createWithContent(
            'my-important-document_final.txt',
            'test content'
        );
        
        $document = $this->documentService->storeDocument($file, $this->workspace);
        
        $this->assertEquals('My Important Document Final', $document->title);
    }
}
