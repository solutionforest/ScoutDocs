# Document Search API Engine - Implementation Guide

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Technology Stack](#technology-stack)
3. [Database Schema](#database-schema)
4. [Implementation Steps](#implementation-steps)
5. [API Endpoints](#api-endpoints)
6. [Key Features](#key-features)
7. [API Response Format](#api-response-format)
8. [Directory Structure](#directory-structure)
9. [Required Packages](#required-packages)
10. [Configuration](#configuration)
11. [Usage Examples](#usage-examples)
12. [Testing](#testing)
13. [Performance Optimization](#performance-optimization)

## Architecture Overview

### Components
- **Document Model**: Store document metadata (title, content, file path, etc.)
- **TNT Search Integration**: Full-text search engine for fast document searching
- **API Controllers**: Handle document upload and search requests
- **Search Service**: Encapsulate search logic and indexing operations
- **File Storage**: Store uploaded documents securely
- **Search Index**: TNT Search index files for optimized searching
- **Text Extractor**: Extract text content from various file formats

### Data Flow
1. **Document Upload**: User uploads document → Text extraction → Store metadata → Index content → Return response
2. **Document Search**: User searches → Query TNT index → Rank results → Return formatted response
3. **Document Management**: CRUD operations on documents with automatic index updates

## Technology Stack

- **Laravel 12** - Main framework
- **TNT Search** - Full-text search engine
- **SQLite/MySQL** - Document metadata storage
- **File Storage** - Document file storage (local/cloud)
- **Queue System** - Asynchronous document processing

## Database Schema

### Documents Table
```sql
CREATE TABLE documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT,
    file_path VARCHAR(500) NULL,
    file_type VARCHAR(50) NULL,
    file_size INTEGER UNSIGNED NULL,
    original_filename VARCHAR(255) NULL,
    indexed_at TIMESTAMP NULL,
    search_index_id VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_title (title),
    INDEX idx_file_type (file_type),
    INDEX idx_indexed_at (indexed_at),
    INDEX idx_created_at (created_at)
);
```

## Implementation Steps

### Phase 1: Setup and Dependencies
1. ✅ Install TNT Search package
2. ✅ Create Document model and migration
3. ✅ Set up TNT Search configuration
4. ✅ Configure file storage

### Phase 2: Core Services
5. ✅ Create Search Service class
6. ✅ Create Text Extractor Service
7. ✅ Create Document Service
8. ✅ Set up Queue Jobs for indexing

### Phase 3: API Layer
9. ✅ Create API Controllers
10. ✅ Create Request validation classes
11. ✅ Create API Resources
12. ✅ Set up API routes

### Phase 4: File Processing
13. ✅ Add text extraction for different file types
14. ✅ Implement file upload handling
15. ✅ Add search indexing logic

### Phase 5: Testing and Optimization
16. ✅ Unit and Feature tests
17. ✅ Performance optimization
18. ✅ Documentation and examples

## API Endpoints

### Document Management
- `POST /api/documents` - Upload and index documents
- `GET /api/documents` - List all documents (paginated)
- `GET /api/documents/{id}` - Get specific document
- `PUT /api/documents/{id}` - Update document
- `DELETE /api/documents/{id}` - Delete document and remove from index

### Search Operations
- `GET /api/search` - Search documents
- `POST /api/search/advanced` - Advanced search with filters
- `GET /api/search/suggestions` - Get search suggestions

### Index Management
- `POST /api/index/rebuild` - Rebuild search index
- `GET /api/index/status` - Get indexing status

## Key Features

### Document Upload
- Support multiple file formats (PDF, DOC, DOCX, TXT, RTF)
- Automatic text extraction
- Metadata storage in database
- Asynchronous content indexing
- File validation and sanitization
- Duplicate detection

### Document Search
- Full-text search across all indexed documents
- Ranked results with relevance scoring
- Search result snippets with highlighted terms
- Boolean operators (AND, OR, NOT)
- Phrase queries with quotes
- Wildcard and fuzzy matching
- Search filters (file type, date range, size)
- Pagination and sorting options

### Performance Features
- Asynchronous indexing with queue jobs
- Search result caching with Redis
- Index optimization and maintenance
- Bulk document operations
- Search analytics and logging

## API Response Format

### Document Upload Response
```json
{
  "success": true,
  "data": {
    "id": 123,
    "title": "Annual Report 2024",
    "file_type": "pdf",
    "file_size": 2048576,
    "original_filename": "annual_report_2024.pdf",
    "indexed": true,
    "created_at": "2025-07-10T10:00:00Z"
  },
  "message": "Document uploaded and indexed successfully"
}
```

### Search Response
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 123,
        "title": "Annual Report 2024",
        "snippet": "The company achieved remarkable growth in <mark>revenue</mark> and market share...",
        "file_type": "pdf",
        "file_size": 2048576,
        "score": 0.95,
        "created_at": "2025-07-10T10:00:00Z"
      }
    ],
    "pagination": {
      "total": 25,
      "current_page": 1,
      "per_page": 10,
      "last_page": 3,
      "from": 1,
      "to": 10
    }
  },
  "query": "revenue growth",
  "search_time": 0.045,
  "total_indexed": 150
}
```

### Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "file": ["The file field is required."],
    "title": ["The title must be at least 3 characters."]
  }
}
```

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── DocumentController.php
│   │       ├── SearchController.php
│   │       └── IndexController.php
│   ├── Requests/
│   │   ├── StoreDocumentRequest.php
│   │   ├── UpdateDocumentRequest.php
│   │   └── SearchRequest.php
│   └── Resources/
│       ├── DocumentResource.php
│       ├── DocumentCollection.php
│       └── SearchResultResource.php
├── Models/
│   └── Document.php
├── Services/
│   ├── DocumentService.php
│   ├── SearchService.php
│   └── TextExtractorService.php
├── Jobs/
│   ├── IndexDocumentJob.php
│   └── OptimizeSearchIndexJob.php
└── Exceptions/
    ├── DocumentNotFoundException.php
    └── SearchIndexException.php

config/
├── tntsearch.php
├── filesystems.php (updated)
└── queue.php (updated)

database/
├── migrations/
│   ├── 2025_07_10_100000_create_documents_table.php
│   └── 2025_07_10_100001_add_search_fields_to_documents_table.php
├── seeders/
│   └── DocumentSeeder.php
└── factories/
    └── DocumentFactory.php

storage/
├── app/
│   └── documents/
├── search-index/
└── logs/

tests/
├── Feature/
│   ├── DocumentApiTest.php
│   ├── SearchApiTest.php
│   └── FileUploadTest.php
└── Unit/
    ├── DocumentServiceTest.php
    ├── SearchServiceTest.php
    └── TextExtractorTest.php

routes/
└── api.php (updated)
```

## Required Packages

### Core Dependencies
```bash
composer require teamtnt/tntsearch
composer require teamtnt/laravel-scout-tntsearch-driver
```

### Text Extraction
```bash
composer require smalot/pdfparser
composer require phpoffice/phpword
composer require phpoffice/phpspreadsheet
```

### Additional Utilities
```bash
composer require league/flysystem
composer require intervention/image
composer require predis/predis (for Redis caching)
```

### Development Dependencies
```bash
composer require --dev pestphp/pest
composer require --dev pestphp/pest-plugin-laravel
```

## Configuration

### TNT Search Configuration
```php
// config/tntsearch.php
return [
    'storage' => storage_path('search-index'),
    'fuzziness' => true,
    'fuzzy' => [
        'prefix_length' => 2,
        'max_expansions' => 50,
        'distance' => 2
    ],
    'asYouType' => false,
    'searchBoolean' => true,
    'maxDocs' => 500
];
```

### File Storage Configuration
```php
// config/filesystems.php - Add documents disk
'documents' => [
    'driver' => 'local',
    'root' => storage_path('app/documents'),
    'url' => env('APP_URL').'/storage/documents',
    'visibility' => 'private',
],
```

## Usage Examples

### Upload Document
```bash
curl -X POST http://localhost:8000/api/documents \
  -H "Content-Type: multipart/form-data" \
  -F "file=@document.pdf" \
  -F "title=Annual Report 2024"
```

### Search Documents
```bash
curl -X GET "http://localhost:8000/api/search?q=revenue+growth&page=1&per_page=10"
```

### Advanced Search with Filters
```bash
curl -X POST http://localhost:8000/api/search/advanced \
  -H "Content-Type: application/json" \
  -d '{
    "query": "financial performance",
    "filters": {
      "file_type": ["pdf", "docx"],
      "date_from": "2024-01-01",
      "date_to": "2024-12-31"
    },
    "sort": "relevance",
    "page": 1,
    "per_page": 20
  }'
```

## Testing

### Feature Tests
- Document upload and validation
- Search functionality and filtering
- File processing and text extraction
- API response formats
- Error handling

### Unit Tests
- Document model operations
- Search service methods
- Text extraction accuracy
- Index management

### Performance Tests
- Search response times
- Large file processing
- Concurrent upload handling
- Index optimization

## Performance Optimization

### Search Performance
- Index optimization strategies
- Query caching with Redis
- Result pagination
- Search analytics

### File Processing
- Asynchronous processing with queues
- Memory management for large files
- Batch operations
- Error handling and retry logic

### Storage Optimization
- File compression
- Duplicate detection
- Cleanup strategies
- Index maintenance

## Security Considerations

- File type validation
- Virus scanning integration
- Access control for documents
- API rate limiting
- Input sanitization
- Secure file storage

## Monitoring and Logging

- Search query logging
- Performance metrics
- Error tracking
- Index health monitoring
- Usage analytics

---

## Getting Started

1. Follow the implementation steps in order
2. Test each component as you build
3. Use the provided examples for reference
4. Monitor performance and optimize as needed

For detailed implementation, see the actual code files in the project.
