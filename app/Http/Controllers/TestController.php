<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestController extends Controller
{
    public function index(Request $request)
    {
        // Check if test interface is enabled
        if (!config('app.test_interface_enabled', false)) {
            abort(404);
        }

        // Simple authentication check if password is configured
        $testPassword = config('app.test_interface_password');
        if ($testPassword) {
            $authenticated = $request->session()->get('test_interface_authenticated', false);
            
            if (!$authenticated && $request->isMethod('post')) {
                $password = $request->input('password');
                if ($password === $testPassword) {
                    $request->session()->put('test_interface_authenticated', true);
                    $authenticated = true;
                } else {
                    return view('test.login', ['error' => 'Invalid password']);
                }
            }
            
            if (!$authenticated) {
                return view('test.login');
            }
        }
        
        $workspaces = Workspace::where('is_active', true)->get();
        
        return view('test.index', compact('workspaces'));
    }
    
    public function apiTest(Request $request)
    {
        if (!config('app.test_interface_enabled', false)) {
            return response()->json(['error' => 'Test interface disabled'], 404);
        }
        
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'method' => 'required|in:GET,POST,PUT,DELETE',
            'workspace_key' => 'nullable|string',
            'data' => 'nullable|array',
            'query' => 'nullable|array',
        ]);
        
        try {
            $url = url('/api' . $validated['endpoint']);
            $method = strtolower($validated['method']);
            
            $options = [];
            
            // Add workspace key if provided
            if (!empty($validated['workspace_key'])) {
                $options['headers']['X-Workspace-Key'] = $validated['workspace_key'];
            }
            
            // Add query parameters
            if (!empty($validated['query'])) {
                $url .= '?' . http_build_query($validated['query']);
            }
            
            // Add request data for POST/PUT
            if (in_array($method, ['post', 'put']) && !empty($validated['data'])) {
                $options['json'] = $validated['data'];
                $options['headers']['Content-Type'] = 'application/json';
            }
            
            $response = Http::withOptions($options)->$method($url);
            
            return response()->json([
                'success' => true,
                'status_code' => $response->status(),
                'headers' => $response->headers(),
                'data' => $response->json(),
                'raw_response' => $response->body(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
