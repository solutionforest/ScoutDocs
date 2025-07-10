<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     title="Document Search API",
 *     version="1.0.0",
 *     description="A powerful document search API engine using TNT Search for fast full-text search capabilities. This API supports multi-tenant workspaces, document upload, text extraction, and advanced search features.",
 *     @OA\Contact(
 *         email="support@example.com",
 *         name="API Support"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="WorkspaceKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-Workspace-Key",
 *     description="Workspace API key for authentication"
 * )
 * 
 * @OA\Tag(
 *     name="Documents",
 *     description="Document management operations"
 * )
 * 
 * @OA\Tag(
 *     name="Search",
 *     description="Document search operations"
 * )
 * 
 * @OA\Tag(
 *     name="Index",
 *     description="Search index management"
 * )
 * 
 * @OA\Schema(
 *     schema="Document",
 *     type="object",
 *     required={"id", "title", "file_type", "created_at"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Annual Report 2024"),
 *     @OA\Property(property="file_type", type="string", example="pdf"),
 *     @OA\Property(property="file_size", type="integer", example=2048576),
 *     @OA\Property(property="formatted_file_size", type="string", example="2 MB"),
 *     @OA\Property(property="original_filename", type="string", example="annual_report_2024.pdf"),
 *     @OA\Property(property="indexed", type="boolean", example=true),
 *     @OA\Property(property="indexed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="SearchResult",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Annual Report 2024"),
 *     @OA\Property(property="snippet", type="string", example="The company achieved remarkable <mark>growth</mark> in revenue..."),
 *     @OA\Property(property="file_type", type="string", example="pdf"),
 *     @OA\Property(property="file_size", type="integer", example=2048576),
 *     @OA\Property(property="formatted_file_size", type="string", example="2 MB"),
 *     @OA\Property(property="original_filename", type="string", example="annual_report_2024.pdf"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="score", type="number", format="float", example=0.95)
 * )
 * 
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean"),
 *     @OA\Property(property="message", type="string", nullable=true),
 *     @OA\Property(property="data", type="object", nullable=true)
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error description"),
 *     @OA\Property(property="error", type="string", example="Detailed error message")
 * )
 * 
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     @OA\Property(property="total", type="integer", example=100),
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=10),
 *     @OA\Property(property="last_page", type="integer", example=10),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="to", type="integer", example=10)
 * )
 */
class ApiController
{
    // This class serves as a container for OpenAPI documentation annotations
}
