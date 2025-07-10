<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\IndexController;
use App\Http\Controllers\Api\ProjectController;
use App\Models\Workspace;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Workspace management routes (no workspace middleware needed)
Route::get('/workspaces', function () {
    return response()->json([
        'success' => true,
        'data' => Workspace::where('is_active', true)->get()
    ]);
});

Route::post('/workspaces', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255|unique:workspaces,slug',
        'description' => 'nullable|string'
    ]);

    if (empty($validated['slug'])) {
        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
    }
    
    $validated['api_key'] = \Illuminate\Support\Str::uuid();
    $validated['is_active'] = true;

    $workspace = Workspace::create($validated);

    return response()->json([
        'success' => true,
        'data' => $workspace
    ], 201);
});

// Workspace-aware routes
Route::prefix('workspaces/{workspace}')->middleware('workspace')->group(function () {
    // Project management routes
    Route::apiResource('projects', ProjectController::class);
    Route::get('projects/{project}/statistics', [ProjectController::class, 'statistics'])->name('projects.statistics');
    
    // Document management routes
    Route::apiResource('documents', DocumentController::class);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('documents-statistics', [DocumentController::class, 'statistics'])->name('documents.statistics');
    Route::post('documents-bulk-index', [DocumentController::class, 'bulkIndex'])->name('documents.bulk-index');

    // Search routes
    Route::get('search', [SearchController::class, 'search'])->name('search');
    Route::post('search/advanced', [SearchController::class, 'advancedSearch'])->name('search.advanced');
    Route::get('search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

    // Index management routes
    Route::get('index/status', [IndexController::class, 'status'])->name('index.status');
    Route::post('index/rebuild', [IndexController::class, 'rebuild'])->name('index.rebuild');
});

// Legacy routes for backwards compatibility (with workspace middleware)
Route::middleware('workspace')->group(function () {
    // Project management routes
    Route::apiResource('projects', ProjectController::class);
    Route::get('projects/{project}/statistics', [ProjectController::class, 'statistics']);
    
    // Document management routes
    Route::apiResource('documents', DocumentController::class);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);
    Route::get('documents-statistics', [DocumentController::class, 'statistics']);
    Route::post('documents-bulk-index', [DocumentController::class, 'bulkIndex']);

    // Search routes
    Route::get('search', [SearchController::class, 'search']);
    Route::post('search/advanced', [SearchController::class, 'advancedSearch']);
    Route::get('search/suggestions', [SearchController::class, 'suggestions']);

    // Index management routes
    Route::get('index/status', [IndexController::class, 'status']);
    Route::post('index/rebuild', [IndexController::class, 'rebuild']);
});
