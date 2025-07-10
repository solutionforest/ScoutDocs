<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search documents.
     * 
     * @OA\Get(
     *     path="/api/search",
     *     summary="Search documents",
     *     description="Search for documents using full-text search with filters",
     *     operationId="searchDocuments",
     *     tags={"Search"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query",
     *         required=true,
     *         @OA\Schema(type="string", example="annual report")
     *     ),
     *     @OA\Parameter(
     *         name="file_type",
     *         in="query",
     *         description="Filter by file type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pdf", "doc", "docx", "txt"})
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter documents from date",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter documents to date",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
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
     *         description="Search results",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="results", type="array", @OA\Items(ref="#/components/schemas/SearchResult")),
     *                         @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
     *                         @OA\Property(property="query", type="string"),
     *                         @OA\Property(property="total_found", type="integer")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function search(SearchRequest $request): JsonResponse
    {
        try {
            $workspace = $request->attributes->get('workspace');
            $query = $request->input('q');
            $filters = [
                'file_type' => $request->input('file_type'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ];
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            
            $results = $this->searchService->search($workspace, $query, $filters, $page, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Advanced search with more parameters.
     * 
     * @OA\Post(
     *     path="/api/search/advanced",
     *     summary="Advanced document search",
     *     description="Perform advanced search with multiple filters and sorting options",
     *     operationId="advancedSearchDocuments",
     *     tags={"Search"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"query"},
     *             @OA\Property(property="query", type="string", example="financial report growth"),
     *             @OA\Property(
     *                 property="filters",
     *                 type="object",
     *                 @OA\Property(property="file_type", type="array", @OA\Items(type="string", enum={"pdf", "doc", "docx", "txt"})),
     *                 @OA\Property(property="date_from", type="string", format="date"),
     *                 @OA\Property(property="date_to", type="string", format="date")
     *             ),
     *             @OA\Property(property="sort", type="string", enum={"relevance", "date_desc", "date_asc", "title_asc", "title_desc", "size_desc", "size_asc"}, default="relevance"),
     *             @OA\Property(property="page", type="integer", minimum=1, default=1),
     *             @OA\Property(property="per_page", type="integer", minimum=1, maximum=100, default=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Advanced search results",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="results", type="array", @OA\Items(ref="#/components/schemas/SearchResult")),
     *                         @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
     *                         @OA\Property(property="query", type="string"),
     *                         @OA\Property(property="filters", type="object"),
     *                         @OA\Property(property="sort", type="string"),
     *                         @OA\Property(property="total_found", type="integer")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function advancedSearch(Request $request): JsonResponse
    {
        try {
            $workspace = $request->attributes->get('workspace');
            
            $validated = $request->validate([
                'query' => 'required|string|min:2|max:500',
                'filters' => 'nullable|array',
                'filters.file_type' => 'nullable|array',
                'filters.file_type.*' => 'string|in:pdf,doc,docx,txt',
                'filters.date_from' => 'nullable|date',
                'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
                'sort' => 'nullable|string|in:relevance,date_desc,date_asc,title_asc,title_desc,size_desc,size_asc',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);
            
            $results = $this->searchService->advancedSearch($workspace, $validated);
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Advanced search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get search suggestions.
     * 
     * @OA\Get(
     *     path="/api/search/suggestions",
     *     summary="Get search suggestions",
     *     description="Get search term suggestions based on indexed content",
     *     operationId="getSearchSuggestions",
     *     tags={"Search"},
     *     security={{"WorkspaceKey": {}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Partial search query",
     *         required=true,
     *         @OA\Schema(type="string", example="annu")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of suggestions",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=20, default=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search suggestions",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="suggestions", type="array", @OA\Items(type="string")),
     *                         @OA\Property(property="query", type="string")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $workspace = $request->attributes->get('workspace');
            
            $validated = $request->validate([
                'q' => 'required|string|min:1|max:100',
                'limit' => 'nullable|integer|min:1|max:20'
            ]);
            
            $suggestions = $this->searchService->getSuggestions(
                $workspace,
                $validated['q'],
                $validated['limit'] ?? 5
            );
            
            return response()->json([
                'success' => true,
                'data' => [
                    'suggestions' => $suggestions,
                    'query' => $validated['q']
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
