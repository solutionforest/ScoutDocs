<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class IndexController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Get search index status.
     * 
     * @OA\Get(
     *     path="/api/index/status",
     *     summary="Get search index status",
     *     description="Get the current status of the search index for the workspace",
     *     operationId="getIndexStatus",
     *     tags={"Index"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Index status information",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="indexed_documents", type="integer", example=145),
     *                         @OA\Property(property="total_documents", type="integer", example=150),
     *                         @OA\Property(property="index_size", type="integer", example=2048576),
     *                         @OA\Property(property="formatted_index_size", type="string", example="2 MB"),
     *                         @OA\Property(property="last_updated", type="string", format="date-time"),
     *                         @OA\Property(property="index_health", type="string", enum={"healthy", "warning", "error"})
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $workspace = $request->attributes->get('workspace');
            $status = $this->searchService->getIndexStatus($workspace);
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get index status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rebuild the entire search index.
     * 
     * @OA\Post(
     *     path="/api/index/rebuild",
     *     summary="Rebuild search index",
     *     description="Rebuild the entire search index for the workspace",
     *     operationId="rebuildIndex",
     *     tags={"Index"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Index rebuilt successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="indexed_documents", type="integer", example=150),
     *                         @OA\Property(property="processing_time", type="number", format="float", example=45.23)
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function rebuild(Request $request): JsonResponse
    {
        try {
            $workspace = $request->attributes->get('workspace');
            $result = $this->searchService->rebuildIndex($workspace);
            
            return response()->json([
                'success' => true,
                'message' => 'Search index rebuilt successfully',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to rebuild search index',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
