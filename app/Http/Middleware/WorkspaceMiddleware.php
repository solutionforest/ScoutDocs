<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to get workspace from different sources
        $workspaceIdentifier = null;
        
        // 1. From URL parameter (for workspace-aware routes)
        if ($request->route('workspace')) {
            $workspaceIdentifier = $request->route('workspace');
        }
        
        // 2. From header
        if (!$workspaceIdentifier) {
            $workspaceIdentifier = $request->header('X-Workspace-Key');
        }
        
        // 3. From query parameter
        if (!$workspaceIdentifier) {
            $workspaceIdentifier = $request->query('api_key');
        }
        
        if (!$workspaceIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace identifier is required',
                'error' => 'Missing workspace slug in URL, X-Workspace-Key header, or api_key parameter'
            ], 401);
        }
        
        // Find workspace by slug or API key
        $workspace = Workspace::where('slug', $workspaceIdentifier)
                               ->orWhere('api_key', $workspaceIdentifier)
                               ->where('is_active', true)
                               ->first();
        
        if (!$workspace) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid workspace identifier',
                'error' => 'Workspace not found or inactive'
            ], 401);
        }
        
        // Add workspace to request for use in controllers
        $request->attributes->set('workspace', $workspace);
        
        return $next($request);
    }
}
