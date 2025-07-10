<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Exception;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects in workspace.
     * 
     * @OA\Get(
     *     path="/api/projects",
     *     summary="List all projects in workspace",
     *     description="Get a paginated list of all projects in the workspace",
     *     operationId="getProjects",
     *     tags={"Projects"},
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
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Project")),
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
        
        $projects = $workspace->projects()
            ->where('is_active', true)
            ->latest()
            ->paginate($perPage);

        return ProjectResource::collection($projects);
    }

    /**
     * Store a newly created project.
     * 
     * @OA\Post(
     *     path="/api/projects",
     *     summary="Create a new project",
     *     description="Create a new project in the workspace",
     *     operationId="storeProject",
     *     tags={"Projects"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", description="Project name", example="Marketing Materials"),
     *             @OA\Property(property="description", type="string", description="Project description", example="All marketing related documents"),
     *             @OA\Property(property="color", type="string", description="Hex color for UI", example="#3B82F6")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Project created successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Project"))
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|min:3|max:255',
                'description' => 'nullable|string|max:1000',
                'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            ]);

            $workspace = $request->attributes->get('workspace');
            
            $project = Project::create([
                'workspace_id' => $workspace->id,
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'color' => $validatedData['color'] ?? '#3B82F6',
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => new ProjectResource($project)
            ], 201);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified project.
     * 
     * @OA\Get(
     *     path="/api/projects/{id}",
     *     summary="Get a specific project",
     *     description="Retrieve details of a specific project by ID",
     *     operationId="getProject",
     *     tags={"Projects"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Project ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project details",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Project"))
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Project not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        
        // Ensure project belongs to the workspace
        if ($project->workspace_id !== $workspace->id) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => new ProjectResource($project)
        ]);
    }

    /**
     * Get project statistics.
     * 
     * @OA\Get(
     *     path="/api/projects/{id}/statistics",
     *     summary="Get project statistics",
     *     description="Get detailed statistics for a specific project",
     *     operationId="getProjectStatistics",
     *     tags={"Projects"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Project ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project statistics",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="total_documents", type="integer"),
     *                         @OA\Property(property="indexed_documents", type="integer"),
     *                         @OA\Property(property="unindexed_documents", type="integer"),
     *                         @OA\Property(property="total_size", type="integer"),
     *                         @OA\Property(property="formatted_total_size", type="string"),
     *                         @OA\Property(property="recent_uploads", type="integer"),
     *                         @OA\Property(property="file_types", type="object"),
     *                         @OA\Property(property="last_indexed_at", type="string", format="date-time", nullable=true)
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Project not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function statistics(Request $request, Project $project): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        
        // Ensure project belongs to the workspace
        if ($project->workspace_id !== $workspace->id) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }
        
        $statistics = $project->getStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
