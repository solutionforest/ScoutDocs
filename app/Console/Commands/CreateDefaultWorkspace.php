<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Workspace;
use App\Models\Document;

class CreateDefaultWorkspace extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workspace:create-default';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default workspace and assign existing documents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating default workspace...');
        
        // Create default workspace
        $workspace = Workspace::create([
            'name' => 'Default Workspace',
            'slug' => 'default',
            'description' => 'Default workspace for existing documents',
            'api_key' => 'ws_default_' . now()->timestamp,
            'is_active' => true,
        ]);
        
        $this->info("Default workspace created with API key: {$workspace->api_key}");
        
        // Assign existing documents to default workspace
        $documentsUpdated = Document::whereNull('workspace_id')->update([
            'workspace_id' => $workspace->id
        ]);
        
        $this->info("Assigned {$documentsUpdated} existing documents to default workspace");
        
        // Create additional demo workspaces
        $clientWorkspace = Workspace::create([
            'name' => 'Client A Workspace',
            'slug' => 'client-a',
            'description' => 'Workspace for Client A documents',
            'is_active' => true,
        ]);
        
        $officeWorkspace = Workspace::create([
            'name' => 'Office Documents',
            'slug' => 'office',
            'description' => 'Internal office document workspace',
            'is_active' => true,
        ]);
        
        $this->info("Created additional workspaces:");
        $this->line("Client A Workspace - API Key: {$clientWorkspace->api_key}");
        $this->line("Office Workspace - API Key: {$officeWorkspace->api_key}");
        
        $this->info('✅ Default workspace setup completed!');
        
        return 0;
    }
}
