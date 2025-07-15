<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datastore Configuration Preview - {{ $project->hotel_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        pre {
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            tab-size: 4;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">Datastore Configuration Overrides</h1>
                <p class="text-sm text-gray-600 mt-1">Project: {{ $project->hotel_name }}</p>
            </div>
            
            <div class="p-6">
                <div class="mb-4">
                    <p class="text-gray-700 mb-2">These are the configuration overrides that will be saved. Only differences from the base configuration are shown.</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 overflow-auto">
                    <pre class="text-sm text-gray-900">{{ $json }}</pre>
                </div>
                
                <div class="mt-6 flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        Generated on {{ now()->format('Y-m-d H:i:s') }}
                    </div>
                    <button onclick="window.close()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors">
                        Close Window
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>