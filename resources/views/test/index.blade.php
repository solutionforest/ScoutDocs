<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Search API - Test Interface</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/plugins/autoloader/prism-autoloader.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div id="app">
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Document Search API Test Interface</h1>
                <p class="text-gray-600">Comprehensive testing interface for all API endpoints</p>
                <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-md p-3">
                    <p class="text-yellow-800 text-sm">
                        ⚠️ This interface is only available in debug mode. Disable in production by setting TEST_INTERFACE_ENABLED=false
                    </p>
                </div>
            </div>

            <!-- Workspace Management -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Workspace Management</h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Create Workspace -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold mb-3">Create New Workspace</h3>
                        <div class="space-y-3">
                            <input v-model="newWorkspace.name" type="text" placeholder="Workspace Name" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <input v-model="newWorkspace.slug" type="text" placeholder="Workspace Slug" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <textarea v-model="newWorkspace.description" placeholder="Description" 
                                      class="w-full border border-gray-300 rounded-md px-3 py-2" rows="2"></textarea>
                            <button @click="createWorkspace" :disabled="loading" 
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-md disabled:opacity-50">
                                Create Workspace
                            </button>
                        </div>
                    </div>

                    <!-- Select Workspace -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold mb-3">Select Active Workspace</h3>
                        <div class="space-y-3">
                            <select v-model="selectedWorkspace" @change="loadWorkspaceData" 
                                    class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Select a workspace...</option>
                                <option v-for="workspace in workspaces" :key="workspace.api_key" :value="workspace.api_key">
                                    @{{ workspace.name }} (@{{ workspace.slug }})
                                </option>
                            </select>
                            <div v-if="selectedWorkspace" class="text-sm text-gray-600">
                                <strong>API Key:</strong> @{{ selectedWorkspace }}
                            </div>
                            <button @click="refreshWorkspaces" :disabled="loading" 
                                    class="w-full bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-md disabled:opacity-50">
                                Refresh Workspaces
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Management -->
            <div v-if="selectedWorkspace" class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Project Management</h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Create Project -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold mb-3">Create New Project</h3>
                        <div class="space-y-3">
                            <input v-model="newProject.name" type="text" placeholder="Project Name" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <textarea v-model="newProject.description" placeholder="Project Description" 
                                      class="w-full border border-gray-300 rounded-md px-3 py-2" rows="2"></textarea>
                            <input v-model="newProject.color" type="color" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <button @click="createProject" :disabled="loading" 
                                    class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-md disabled:opacity-50">
                                Create Project
                            </button>
                        </div>
                    </div>

                    <!-- Project List -->
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="font-semibold">Projects in Workspace</h3>
                            <button @click="loadProjects" class="text-blue-500 text-sm">Refresh</button>
                        </div>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <div v-for="project in projects" :key="project.id" 
                                 class="flex items-center justify-between p-2 border rounded">
                                <div class="flex items-center space-x-2">
                                    <div class="w-4 h-4 rounded" :style="{backgroundColor: project.color || '#007bff'}"></div>
                                    <span class="text-sm">@{{ project.name }}</span>
                                </div>
                                <select v-model="selectedProject" class="text-xs border rounded px-1 py-1">
                                    <option value="">Select</option>
                                    <option :value="project.id">@{{ project.id }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Management -->
            <div v-if="selectedWorkspace" class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Document Management</h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Upload Document -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold mb-3">Upload Document</h3>
                        <div class="space-y-3">
                            <input type="file" @change="handleFileUpload" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <input v-model="uploadData.title" type="text" placeholder="Document Title (optional)" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <textarea v-model="uploadData.description" placeholder="Document Description (optional)" 
                                      class="w-full border border-gray-300 rounded-md px-3 py-2" rows="2"></textarea>
                            <select v-model="uploadData.project_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Select Project (optional)</option>
                                <option v-for="project in projects" :key="project.id" :value="project.id">
                                    @{{ project.name }}
                                </option>
                            </select>
                            <button @click="uploadDocument" :disabled="loading || !uploadFile" 
                                    class="w-full bg-purple-500 hover:bg-purple-600 text-white py-2 px-4 rounded-md disabled:opacity-50">
                                Upload & Index Document
                            </button>
                        </div>
                    </div>

                    <!-- Document List -->
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="font-semibold">Documents</h3>
                            <button @click="loadDocuments" class="text-blue-500 text-sm">Refresh</button>
                        </div>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <div v-for="doc in documents" :key="doc.id" 
                                 class="p-2 border rounded text-sm">
                                <div class="font-medium">@{{ doc.title || doc.filename }}</div>
                                <div class="text-gray-500 text-xs">
                                    Type: @{{ doc.file_type }} | Size: @{{ formatFileSize(doc.file_size) }}
                                    <span v-if="doc.project">| Project: @{{ doc.project.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Index -->
            <div v-if="selectedWorkspace" class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Search & Index Management</h2>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Search Documents -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold mb-3">Search Documents</h3>
                        <div class="space-y-3">
                            <input v-model="searchQuery" type="text" placeholder="Search query..." 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <select v-model="searchFilters.project_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">All Projects</option>
                                <option v-for="project in projects" :key="project.id" :value="project.id">
                                    @{{ project.name }}
                                </option>
                            </select>
                            <select v-model="searchFilters.file_type" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">All File Types</option>
                                <option value="pdf">PDF</option>
                                <option value="docx">Word Document</option>
                                <option value="txt">Text File</option>
                            </select>
                            <div class="flex space-x-2">
                                <button @click="performSearch" :disabled="loading" 
                                        class="flex-1 bg-indigo-500 hover:bg-indigo-600 text-white py-2 px-4 rounded-md disabled:opacity-50">
                                    Search
                                </button>
                                <button @click="getSearchSuggestions" :disabled="loading" 
                                        class="flex-1 bg-teal-500 hover:bg-teal-600 text-white py-2 px-4 rounded-md disabled:opacity-50">
                                    Suggestions
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Index Management -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold mb-3">Index Management</h3>
                        <div class="space-y-3">
                            <button @click="getIndexStatus" :disabled="loading" 
                                    class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-md disabled:opacity-50">
                                Check Index Status
                            </button>
                            <button @click="rebuildIndex" :disabled="loading" 
                                    class="w-full bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-md disabled:opacity-50">
                                Rebuild Index
                            </button>
                            <button @click="getStatistics" :disabled="loading" 
                                    class="w-full bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-md disabled:opacity-50">
                                Get Statistics
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Response Display -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">API Response</h2>
                
                <div v-if="lastResponse.data">
                    <!-- Status -->
                    <div class="mb-4">
                        <span class="inline-block px-3 py-1 rounded text-sm font-medium mr-2"
                              :class="lastResponse.status >= 200 && lastResponse.status < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                            @{{ lastResponse.status }} @{{ lastResponse.statusText }}
                        </span>
                        <span class="text-sm text-gray-600">@{{ lastResponse.method }} @{{ lastResponse.url }}</span>
                    </div>

                    <!-- Response Data -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Response Data</label>
                        <pre class="bg-gray-50 p-4 rounded-md text-sm overflow-auto max-h-96 border"><code class="language-json">@{{ formatJson(lastResponse.data) }}</code></pre>
                    </div>
                </div>

                <div v-else class="text-gray-500 text-center py-8">
                    No response yet. Use the actions above to test the API.
                </div>
            </div>

            <!-- Documentation Link -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">API Documentation</h2>
                <div class="flex space-x-4">
                    <a href="{{ url('/api/documentation') }}" target="_blank" 
                       class="inline-block bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-md">
                        View OpenAPI Documentation
                    </a>
                    <button @click="showRawApiTester = !showRawApiTester" 
                            class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-md">
                        @{{ showRawApiTester ? 'Hide' : 'Show' }} Raw API Tester
                    </button>
                </div>
            </div>

            <!-- Raw API Tester (Hidden by default) -->
            <div v-if="showRawApiTester" class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h2 class="text-xl font-semibold mb-4">Raw API Tester</h2>
                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Raw Request -->
                    <div>
                        <h3 class="font-semibold mb-3">Custom Request</h3>
                        <div class="space-y-3">
                            <div class="grid grid-cols-4 gap-2">
                                <select v-model="rawRequest.method" class="border border-gray-300 rounded-md px-3 py-2">
                                    <option>GET</option>
                                    <option>POST</option>
                                    <option>PUT</option>
                                    <option>DELETE</option>
                                </select>
                                <input v-model="rawRequest.endpoint" type="text" placeholder="/documents" 
                                       class="col-span-3 border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            
                            <div v-if="rawRequest.method === 'GET'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Query Parameters</label>
                                <div v-for="(param, index) in rawRequest.queryParams" :key="index" class="flex gap-2 mb-2">
                                    <input v-model="param.key" placeholder="key" class="flex-1 border border-gray-300 rounded-md px-2 py-1 text-sm">
                                    <input v-model="param.value" placeholder="value" class="flex-1 border border-gray-300 rounded-md px-2 py-1 text-sm">
                                    <button @click="removeQueryParam(index)" class="px-2 py-1 bg-red-500 text-white rounded text-sm">×</button>
                                </div>
                                <button @click="addQueryParam" class="text-blue-500 text-sm">+ Add Parameter</button>
                            </div>

                            <div v-if="['POST', 'PUT'].includes(rawRequest.method)">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Request Body (JSON)</label>
                                <textarea v-model="rawRequest.body" rows="6" placeholder='{"title": "Test Document"}'
                                          class="w-full border border-gray-300 rounded-md px-3 py-2 font-mono text-sm"></textarea>
                            </div>

                            <button @click="sendRawRequest" :disabled="loading" 
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-md disabled:opacity-50">
                                Send Raw Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    loading: false,
                    selectedWorkspace: '',
                    selectedProject: '',
                    uploadFile: null,
                    showRawApiTester: false,
                    
                    // Data containers
                    workspaces: @json($workspaces),
                    projects: [],
                    documents: [],
                    
                    // Form data
                    newWorkspace: {
                        name: '',
                        slug: '',
                        description: ''
                    },
                    newProject: {
                        name: '',
                        description: '',
                        color: '#007bff'
                    },
                    uploadData: {
                        title: '',
                        description: '',
                        project_id: ''
                    },
                    searchQuery: '',
                    searchFilters: {
                        project_id: '',
                        file_type: ''
                    },
                    
                    // Raw API tester
                    rawRequest: {
                        method: 'GET',
                        endpoint: '/documents',
                        queryParams: [{key: '', value: ''}],
                        body: ''
                    },
                    
                    // Response
                    lastResponse: {}
                }
            },
            mounted() {
                // Set up axios defaults
                axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                axios.defaults.headers.common['Accept'] = 'application/json';
                
                // Auto-select first workspace if available
                if (this.workspaces.length > 0 && !this.selectedWorkspace) {
                    this.selectedWorkspace = this.workspaces[0].api_key;
                    this.loadWorkspaceData();
                }
            },
            methods: {
                async apiCall(method, url, data = null, headers = {}) {
                    this.loading = true;
                    try {
                        // Add workspace header for workspace-aware routes
                        if (this.selectedWorkspace && url.includes('/workspaces/')) {
                            headers['X-Workspace-Key'] = this.selectedWorkspace;
                        }
                        
                        const config = { method, url, headers };
                        if (data) {
                            if (data instanceof FormData) {
                                config.data = data;
                            } else {
                                config.data = data;
                                config.headers['Content-Type'] = 'application/json';
                            }
                        }
                        
                        console.log('Making API call:', { method: method.toUpperCase(), url, headers });
                        
                        const response = await axios(config);
                        
                        this.lastResponse = {
                            status: response.status,
                            statusText: response.statusText,
                            method: method.toUpperCase(),
                            url: url,
                            data: response.data
                        };
                        
                        console.log('API response:', this.lastResponse);
                        return response.data;
                    } catch (error) {
                        console.error('API call failed:', error);
                        this.lastResponse = {
                            status: error.response?.status || 500,
                            statusText: error.response?.statusText || 'Error',
                            method: method.toUpperCase(),
                            url: url,
                            data: error.response?.data || { error: error.message }
                        };
                        // Don't throw error to allow UI to continue
                        return null;
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => {
                            if (window.Prism) {
                                window.Prism.highlightAll();
                            }
                        });
                    }
                },
                
                // Workspace Management
                async createWorkspace() {
                    if (!this.newWorkspace.name || !this.newWorkspace.slug) {
                        alert('Please provide both name and slug for the workspace');
                        return;
                    }
                    
                    const result = await this.apiCall('post', '/api/workspaces', this.newWorkspace);
                    if (result) {
                        this.newWorkspace = { name: '', slug: '', description: '' };
                        await this.refreshWorkspaces();
                    }
                },
                
                async refreshWorkspaces() {
                    const response = await this.apiCall('get', '/api/workspaces');
                    if (response && response.data) {
                        this.workspaces = response.data;
                    }
                },
                
                async loadWorkspaceData() {
                    if (this.selectedWorkspace) {
                        await this.loadProjects();
                        await this.loadDocuments();
                    } else {
                        this.projects = [];
                        this.documents = [];
                    }
                },
                
                // Project Management
                async createProject() {
                    if (!this.selectedWorkspace) {
                        alert('Please select a workspace first');
                        return;
                    }
                    
                    if (!this.newProject.name) {
                        alert('Please provide a project name');
                        return;
                    }
                    
                    const workspaceSlug = this.getWorkspaceSlug();
                    if (!workspaceSlug) {
                        alert('Invalid workspace selected');
                        return;
                    }
                    
                    const result = await this.apiCall('post', `/api/workspaces/${workspaceSlug}/projects`, this.newProject);
                    if (result) {
                        this.newProject = { name: '', description: '', color: '#007bff' };
                        await this.loadProjects();
                    }
                },
                
                async loadProjects() {
                    if (!this.selectedWorkspace) return;
                    
                    const workspaceSlug = this.getWorkspaceSlug();
                    if (!workspaceSlug) return;
                    
                    const response = await this.apiCall('get', `/api/workspaces/${workspaceSlug}/projects`);
                    if (response && response.data) {
                        this.projects = response.data;
                    } else {
                        this.projects = [];
                    }
                },
                
                // Document Management
                handleFileUpload(event) {
                    this.uploadFile = event.target.files[0];
                },
                
                async uploadDocument() {
                    if (!this.uploadFile || !this.selectedWorkspace) {
                        alert('Please select a file and workspace');
                        return;
                    }
                    
                    const workspaceSlug = this.getWorkspaceSlug();
                    if (!workspaceSlug) {
                        alert('Invalid workspace selected');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('file', this.uploadFile);
                    
                    if (this.uploadData.title) {
                        formData.append('title', this.uploadData.title);
                    }
                    if (this.uploadData.description) {
                        formData.append('description', this.uploadData.description);
                    }
                    if (this.uploadData.project_id) {
                        formData.append('project_id', this.uploadData.project_id);
                    }
                    
                    const result = await this.apiCall('post', `/api/workspaces/${workspaceSlug}/documents`, formData);
                    if (result) {
                        this.uploadData = { title: '', description: '', project_id: '' };
                        this.uploadFile = null;
                        // Clear file input
                        const fileInput = document.querySelector('input[type="file"]');
                        if (fileInput) {
                            fileInput.value = '';
                        }
                        await this.loadDocuments();
                    }
                },
                
                async loadDocuments() {
                    if (!this.selectedWorkspace) return;
                    
                    const workspaceSlug = this.getWorkspaceSlug();
                    if (!workspaceSlug) return;
                    
                    const response = await this.apiCall('get', `/api/workspaces/${workspaceSlug}/documents`);
                    if (response && response.data) {
                        this.documents = response.data;
                    } else {
                        this.documents = [];
                    }
                },
                
                // Search & Index
                async performSearch() {
                    if (!this.selectedWorkspace || !this.searchQuery) {
                        alert('Please select a workspace and enter a search query');
                        return;
                    }
                    
                    const workspaceSlug = this.getWorkspaceSlug();
                    if (!workspaceSlug) {
                        alert('Invalid workspace selected');
                        return;
                    }
                    
                    const params = { q: this.searchQuery };
                    
                    if (this.searchFilters.project_id) {
                        params.project_id = this.searchFilters.project_id;
                    }
                    if (this.searchFilters.file_type) {
                        params.file_type = this.searchFilters.file_type;
                    }
                    
                    const queryString = new URLSearchParams(params).toString();
                    await this.apiCall('get', `/api/workspaces/${workspaceSlug}/search?${queryString}`);
                },
                
                async getSearchSuggestions() {
                    if (!this.selectedWorkspace) {
                        alert('Please select a workspace');
                        return;
                    }
                    
                    const workspaceSlug = this.getWorkspaceSlug();
                    if (!workspaceSlug) {
                        alert('Invalid workspace selected');
                        return;
                    }
                    
                    const params = this.searchQuery ? `?q=${encodeURIComponent(this.searchQuery)}` : '';
                    await this.apiCall('get', `/api/workspaces/${workspaceSlug}/search/suggestions${params}`);
                },
                
                async getIndexStatus() {
                    if (!this.selectedWorkspace) {
                        alert('Please select a workspace');
                        return;
                    }
                    
                    const workspaceSlug = this.getWorkspaceSlug();
                    if (!workspaceSlug) {
                        alert('Invalid workspace selected');
                        return;
                    }
                    
                    await this.apiCall('get', `/api/workspaces/${workspaceSlug}/index/status`);
                },
                
                async rebuildIndex() {
                    if (!this.selectedWorkspace) {
                        alert('Please select a workspace');
                        return;
                    }
                    
                    if (!confirm('Are you sure you want to rebuild the index? This may take some time.')) {
                        return;
                    }
                    
                    const workspaceSlug = this.getWorkspaceSlug();
                    if (!workspaceSlug) {
                        alert('Invalid workspace selected');
                        return;
                    }
                    
                    await this.apiCall('post', `/api/workspaces/${workspaceSlug}/index/rebuild`);
                },
                
                async getStatistics() {
                    if (!this.selectedWorkspace) {
                        alert('Please select a workspace');
                        return;
                    }
                    
                    const workspaceSlug = this.getWorkspaceSlug();
                    if (!workspaceSlug) {
                        alert('Invalid workspace selected');
                        return;
                    }
                    
                    await this.apiCall('get', `/api/workspaces/${workspaceSlug}/documents-statistics`);
                },
                
                // Raw API Tester
                addQueryParam() {
                    this.rawRequest.queryParams.push({key: '', value: ''});
                },
                
                removeQueryParam(index) {
                    this.rawRequest.queryParams.splice(index, 1);
                },
                
                async sendRawRequest() {
                    if (!this.selectedWorkspace) {
                        alert('Please select a workspace');
                        return;
                    }
                    
                    if (!this.rawRequest.endpoint) {
                        alert('Please provide an endpoint');
                        return;
                    }
                    
                    let url = this.rawRequest.endpoint;
                    let data = null;
                    
                    // Add workspace prefix if not already there
                    if (!url.startsWith('/api/workspaces/')) {
                        const workspaceSlug = this.getWorkspaceSlug();
                        if (!workspaceSlug) {
                            alert('Invalid workspace selected');
                            return;
                        }
                        url = `/api/workspaces/${workspaceSlug}${url}`;
                    }
                    
                    // Add query parameters for GET requests
                    if (this.rawRequest.method === 'GET') {
                        const params = new URLSearchParams();
                        this.rawRequest.queryParams.forEach(param => {
                            if (param.key && param.value) {
                                params.append(param.key, param.value);
                            }
                        });
                        if (params.toString()) {
                            url += '?' + params.toString();
                        }
                    }
                    
                    // Add request body for POST/PUT
                    if (['POST', 'PUT'].includes(this.rawRequest.method) && this.rawRequest.body) {
                        try {
                            data = JSON.parse(this.rawRequest.body);
                        } catch (e) {
                            alert('Invalid JSON in request body');
                            return;
                        }
                    }
                    
                    await this.apiCall(this.rawRequest.method.toLowerCase(), url, data);
                },
                
                // Utility functions
                getWorkspaceSlug() {
                    if (!this.selectedWorkspace) return '';
                    const workspace = this.workspaces.find(w => w.api_key === this.selectedWorkspace);
                    return workspace ? workspace.slug : '';
                },
                
                getWorkspace() {
                    if (!this.selectedWorkspace) return null;
                    return this.workspaces.find(w => w.api_key === this.selectedWorkspace);
                },
                
                formatJson(obj) {
                    return JSON.stringify(obj, null, 2);
                },
                
                formatFileSize(bytes) {
                    if (!bytes) return 'Unknown';
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(1024));
                    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
