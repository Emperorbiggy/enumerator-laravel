@extends('app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Upload NIN Verification File</h1>
            
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="mb-6">
                    <label for="lga" class="block text-sm font-medium text-gray-700 mb-2">
                        Select LGA <span class="text-red-500">*</span>
                    </label>
                    <select id="lga" name="lga" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose an LGA...</option>
                        @foreach($lgas as $lga)
                            <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-2">
                        Upload CSV/Excel File <span class="text-red-500">*</span>
                    </label>
                    <input type="file" id="file" name="file" required
                           accept=".csv,.xlsx,.xls"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-2 text-sm text-gray-500">
                        Supported formats: CSV, XLSX, XLS (Max 10MB)<br>
                        File must contain a column named "NIN"
                    </p>
                </div>

                <div class="mb-6">
                    <button type="submit" id="uploadBtn"
                            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="btnText">Upload and Verify NINs</span>
                        <span id="btnSpinner" class="hidden">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </form>

            <!-- Progress Bar -->
            <div id="progressContainer" class="hidden mb-6">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Processing...</span>
                    <span id="progressText">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>

            <!-- Results -->
            <div id="resultsContainer" class="hidden">
                <div class="border-t pt-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Verification Results</h2>
                    <div id="resultsContent"></div>
                </div>
            </div>

            <!-- Error Messages -->
            <div id="errorContainer" class="hidden">
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Error</h3>
                            <div id="errorMessage" class="mt-2 text-sm text-red-700"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 rounded-lg p-6 mt-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-3">Instructions</h3>
            <ul class="list-disc list-inside text-blue-700 space-y-1">
                <li>Select the LGA for the members you're uploading</li>
                <li>Upload a CSV or Excel file containing NIN numbers</li>
                <li>The file must have a column named "NIN" (case-insensitive)</li>
                <li>Each NIN will be verified using the Prembly API</li>
                <li>Successfully verified NINs will be automatically added as members</li>
                <li>Processing may take several minutes depending on the number of NINs</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    const uploadBtn = document.getElementById('uploadBtn');
    const progressContainer = document.getElementById('progressContainer');
    const resultsContainer = document.getElementById('resultsContainer');
    const errorContainer = document.getElementById('errorContainer');
    
    // Reset UI
    resultsContainer.classList.add('hidden');
    errorContainer.classList.add('hidden');
    progressContainer.classList.remove('hidden');
    uploadBtn.disabled = true;
    btnText.classList.add('hidden');
    btnSpinner.classList.remove('hidden');
    
    try {
        const response = await fetch('/members/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showResults(result);
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Network error. Please try again.');
    } finally {
        uploadBtn.disabled = false;
        btnText.classList.remove('hidden');
        btnSpinner.classList.add('hidden');
        progressContainer.classList.add('hidden');
    }
});

function showResults(result) {
    const resultsContainer = document.getElementById('resultsContainer');
    const resultsContent = document.getElementById('resultsContent');
    
    const html = `
        <div class="space-y-4">
            <div class="bg-green-50 border border-green-200 rounded-md p-4">
                <h4 class="text-green-800 font-semibold mb-2">Summary</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Total NINs:</span>
                        <span class="font-medium ml-2">${result.results.total}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Verified:</span>
                        <span class="font-medium text-green-600 ml-2">${result.results.verified}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Failed:</span>
                        <span class="font-medium text-red-600 ml-2">${result.results.failed}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Already Exists:</span>
                        <span class="font-medium text-yellow-600 ml-2">${result.results.already_exists}</span>
                    </div>
                </div>
                <p class="mt-3 text-green-700">${result.message}</p>
            </div>
            
            ${result.results.errors.length > 0 ? `
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <h4 class="text-red-800 font-semibold mb-2">Errors</h4>
                    <div class="max-h-40 overflow-y-auto">
                        <ul class="text-sm text-red-700 space-y-1">
                            ${result.results.errors.map(error => `<li>• ${error}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            ` : ''}
        </div>
    `;
    
    resultsContent.innerHTML = html;
    resultsContainer.classList.remove('hidden');
}

function showError(message) {
    const errorContainer = document.getElementById('errorContainer');
    const errorMessage = document.getElementById('errorMessage');
    
    errorMessage.textContent = message;
    errorContainer.classList.remove('hidden');
}
</script>
@endsection
