<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Exception;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Display a listing of documents.
     * 
     * @OA\Get(
     *     path="/api/documents",
     *     summary="List all documents",
     *     description="Get a paginated list of all documents in the workspace",
     *     operationId="getDocuments",
     *     tags={"Documents"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=10)
     *     ),
     *     @OA\Parameter(
     *         name="project_id",
     *         in="query",
     *         description="Filter by project ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Document")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->get('per_page', 10), 100);
        $workspace = $request->attributes->get('workspace');
        $projectId = $request->query('project_id');
        
        $query = $workspace->documents();
        
        // Filter by project if specified
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        
        $documents = $query->latest()->paginate($perPage);

        return DocumentResource::collection($documents);
    }

    /**
     * Store a newly uploaded document.
     * 
     * @OA\Post(
     *     path="/api/documents",
     *     summary="Upload a new document",
     *     description="Upload a document file to the workspace and automatically extract text content for search indexing",
     *     operationId="storeDocument",
     *     tags={"Documents"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="file", type="string", format="binary", description="Document file to upload"),
     *                 @OA\Property(property="title", type="string", description="Optional document title", example="Quarterly Report"),
     *                 @OA\Property(property="project_id", type="integer", description="Optional project ID to associate with document")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Document uploaded successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Document"))
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $title = $request->input('title');
            $projectId = $request->input('project_id');
            $workspace = $request->attributes->get('workspace');
            
            // Validate project belongs to workspace if provided
            if ($projectId) {
                $project = $workspace->projects()->find($projectId);
                if (!$project) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Project not found in this workspace'
                    ], 404);
                }
            }
            
            $document = $this->documentService->storeDocument($file, $workspace, $title, $projectId);
            
            return response()->json([
                'success' => true,
                'message' => 'Document uploaded and indexed successfully',
                'data' => new DocumentResource($document)
            ], 201);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified document.
     * 
     * @OA\Get(
     *     path="/api/documents/{id}",
     *     summary="Get a specific document",
     *     description="Retrieve details of a specific document by ID",
     *     operationId="getDocument",
     *     tags={"Documents"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Document ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document details",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Document"))
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Document not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(Request $request, Document $document): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        
        // Ensure document belongs to the workspace
        if ($document->workspace_id !== $workspace->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => new DocumentResource($document)
        ]);
    }

    /**
     * Update the specified document.
     * 
     * @OA\Put(
     *     path="/api/documents/{id}",
     *     summary="Update a document",
     *     description="Update document title or content",
     *     operationId="updateDocument",
     *     tags={"Documents"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Document ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Document Title"),
     *             @OA\Property(property="content", type="string", example="Updated document content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document updated successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Document"))
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Document not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        try {
            $workspace = $request->attributes->get('workspace');
            
            // Ensure document belongs to the workspace
            if ($document->workspace_id !== $workspace->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }
            
            $validatedData = $request->validate([
                'title' => 'sometimes|string|min:3|max:255',
                'content' => 'sometimes|string'
            ]);
            
            $updatedDocument = $this->documentService->updateDocument($document, $validatedData);
            
            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'data' => new DocumentResource($updatedDocument)
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified document.
     * 
     * @OA\Delete(
     *     path="/api/documents/{id}",
     *     summary="Delete a document",
     *     description="Delete a document and remove it from search index",
     *     operationId="deleteDocument",
     *     tags={"Documents"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Document ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Document not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function destroy(Request $request, Document $document): JsonResponse
    {
        try {
            $workspace = $request->attributes->get('workspace');
            
            // Ensure document belongs to the workspace
            if ($document->workspace_id !== $workspace->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }
            
            $this->documentService->deleteDocument($document);
            
            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Download the specified document.
     * 
     * @OA\Get(
     *     path="/api/documents/{id}/download",
     *     summary="Download document file",
     *     description="Download the original file of a document",
     *     operationId="downloadDocument",
     *     tags={"Documents"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Document ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document file download",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Document or file not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function download(Request $request, Document $document)
    {
        try {
            $workspace = $request->attributes->get('workspace');
            
            // Ensure document belongs to the workspace
            if ($document->workspace_id !== $workspace->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }
            
            if (!$document->file_path || !Storage::exists($document->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document file not found'
                ], 404);
            }
            
            return Storage::download(
                $document->file_path,
                $document->original_filename
            );
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download document',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get document statistics.
     * 
     * @OA\Get(
     *     path="/api/documents-statistics",
     *     summary="Get document statistics",
     *     description="Get statistical information about documents in the workspace",
     *     operationId="getDocumentStatistics",
     *     tags={"Documents"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Document statistics",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="total_documents", type="integer", example=150),
     *                         @OA\Property(property="indexed_documents", type="integer", example=145),
     *                         @OA\Property(property="total_size", type="integer", example=52428800),
     *                         @OA\Property(property="formatted_total_size", type="string", example="50 MB"),
     *                         @OA\Property(property="file_types", type="object"),
     *                         @OA\Property(property="recent_uploads", type="integer", example=15)
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $workspace = $request->attributes->get('workspace');
            $stats = $this->documentService->getStatistics($workspace);
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk index unindexed documents.
     * 
     * @OA\Post(
     *     path="/api/documents-bulk-index",
     *     summary="Bulk index documents",
     *     description="Index all unindexed documents in the workspace for search",
     *     operationId="bulkIndexDocuments",
     *     tags={"Documents"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Documents indexed successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="indexed_count", type="integer", example=25)
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function bulkIndex(Request $request): JsonResponse
    {
        try {
            $workspace = $request->attributes->get('workspace');
            $indexedCount = $this->documentService->bulkIndexDocuments($workspace);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully indexed {$indexedCount} documents",
                'data' => [
                    'indexed_count' => $indexedCount
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk index documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
