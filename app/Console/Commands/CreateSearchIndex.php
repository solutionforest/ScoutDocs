<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use TeamTNT\TNTSearch\TNTSearch;
use App\Models\Document;

class CreateSearchIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:create-index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create TNT Search index for documents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating TNT Search index...');
        
        $tnt = new TNTSearch;
        
        $dbConnection = config('database.default');
        
        if ($dbConnection === 'sqlite') {
            $tnt->loadConfig([
                'driver' => 'sqlite',
                'database' => database_path('database.sqlite'),
                'storage' => storage_path('search-index'),
            ]);
        } else {
            $tnt->loadConfig([
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host', '127.0.0.1'),
                'database' => config('database.connections.mysql.database', 'laravel'),
                'username' => config('database.connections.mysql.username', 'root'),
                'password' => config('database.connections.mysql.password', ''),
                'storage' => storage_path('search-index'),
            ]);
        }
        
        $indexer = $tnt->createIndex('documents');
        $indexer->query('SELECT * FROM documents;');
        $indexer->setPrimaryKey('id');
        $indexer->run();
        
        $this->info('TNT Search index created successfully!');
        
        // Now index all existing documents
        $this->info('Indexing existing documents...');
        Document::makeAllSearchable();
        
        $this->info('All documents indexed successfully!');
        
        return 0;
    }
}
