import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Upload({ lgas }) {
    return (
        <AdminLayout title="NIN Upload">
            <UploadContent lgas={lgas} />
        </AdminLayout>
    );
}

function UploadContent({ lgas }) {
    const [selectedLga, setSelectedLga] = useState('');
    const [selectedWard, setSelectedWard] = useState('');
    const [wards, setWards] = useState([]);
    const [selectedFile, setSelectedFile] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [progress, setProgress] = useState(0);
    const [results, setResults] = useState(null);
    const [formData, setFormData] = useState({
        state: '',
        lga: '',
        ward: '',
        file: null,
        skipVerification: false
    });
    const [error, setError] = useState('');

    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        const fieldValue = type === 'checkbox' ? checked : value;
        
        setFormData(prev => ({
            ...prev,
            [name]: fieldValue
        }));
        
        // Reset dependent fields
        if (name === 'state') {
            setSelectedLga('');
            setSelectedWard('');
            setWards([]);
            setFormData(prev => ({
                ...prev,
                lga: '',
                ward: ''
            }));
        } else if (name === 'lga') {
            setSelectedWard('');
            setFormData(prev => ({
                ...prev,
                ward: ''
            }));
            
            // Fetch wards for selected LGA
            if (value) {
                fetchWards(value);
            } else {
                setWards([]);
            }
        }
    };

    const fetchWards = async (lgaId) => {
        try {
            const response = await fetch(`/api/lgas/${lgaId}/wards`);
            const data = await response.json();
            setWards(data.wards || []);
        } catch (error) {
            console.error('Error fetching wards:', error);
            setWards([]);
        }
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            // Check file size (10MB max)
            if (file.size > 10 * 1024 * 1024) {
                setError('File size must be less than 10MB');
                setSelectedFile(null);
                return;
            }
            
            // Check file type
            const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if (!allowedTypes.includes(file.type)) {
                setError('Please upload a CSV or Excel file');
                setSelectedFile(null);
                return;
            }
            
            setSelectedFile(file);
            setError('');
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!formData.state || !selectedFile) {
            setError('Please select a state and upload a file');
            return;
        }

        setProcessing(true);
        setProgress(0);
        setError('');
        setResults(null);

        const submitData = new FormData();
        submitData.append('state', formData.state);
        submitData.append('file', selectedFile);
        
        // Only append optional fields if they have values
        if (formData.lga) {
            submitData.append('lga', formData.lga);
        }
        
        if (formData.ward) {
            submitData.append('ward', formData.ward);
        }
        
        // Append skip verification flag
        submitData.append('skip_verification', formData.skipVerification ? '1' : '0');

        try {
            const response = await fetch('/members/upload', {
                method: 'POST',
                body: submitData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();

            if (result.success) {
                setResults(result);
                setProgress(100);
            } else {
                setError(result.message);
            }
        } catch (err) {
            setError('Network error. Please try again.');
        } finally {
            setProcessing(false);
        }
    };

    return (
        <>
            <Head title="Upload NIN File" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <h1 className="text-2xl font-bold text-gray-800 mb-6">Upload NIN Verification File</h1>
                            <form onSubmit={handleSubmit}>
                                <div className="space-y-6">
                                    <div>
                                        <label htmlFor="state" className="block text-sm font-medium text-gray-700 mb-2">
                                            Select State *
                                        </label>
                                        <select
                                            id="state"
                                            name="state"
                                            value={formData.state}
                                            onChange={handleInputChange}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        >
                                            <option value="">Select State</option>
                                            <option value="Osun">Osun</option>
                                        </select>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Currently only Osun state is available
                                        </p>
                                    </div>

                                    {formData.state && (
                                        <div>
                                            <label htmlFor="lga" className="block text-sm font-medium text-gray-700 mb-2">
                                                Select Local Government Area (Optional)
                                            </label>
                                            <select
                                                id="lga"
                                                name="lga"
                                                value={formData.lga}
                                                onChange={handleInputChange}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option value="">Select LGA (Optional - will randomize if not selected)</option>
                                                {lgas.map(lga => (
                                                    <option key={lga.id} value={lga.id}>
                                                        {lga.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <p className="mt-1 text-sm text-gray-500">
                                                Leave empty to randomize LGA, Ward, and Polling Unit
                                            </p>
                                        </div>
                                    )}

                                    {formData.lga && wards.length > 0 && (
                                        <div>
                                            <label htmlFor="ward" className="block text-sm font-medium text-gray-700 mb-2">
                                                Select Ward (Optional)
                                            </label>
                                            <select
                                                id="ward"
                                                name="ward"
                                                value={formData.ward}
                                                onChange={handleInputChange}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option value="">Select Ward (Optional - will randomize if not selected)</option>
                                                {wards.map(ward => (
                                                    <option key={ward.id} value={ward.id}>
                                                        {ward.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <p className="mt-1 text-sm text-gray-500">
                                                Leave empty to randomize Ward and Polling Unit within selected LGA
                                            </p>
                                        </div>
                                    )}
                                </div>

                                <div className="mb-6">
                                    <label htmlFor="file" className="block text-sm font-medium text-gray-700 mb-2">
                                        Upload CSV/Excel File <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="file"
                                        id="file"
                                        onChange={handleFileChange}
                                        required
                                        accept=".csv,.xlsx,.xls"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                    <p className="mt-2 text-sm text-gray-500">
                                        Supported formats: CSV, XLSX, XLS (Max 10MB)<br />
                                        File must contain a column named "NIN"
                                    </p>
                                    {selectedFile && (
                                        <p className="mt-2 text-sm text-green-600">
                                            Selected: {selectedFile.name}
                                        </p>
                                    )}
                                </div>

                                <div className="mb-6">
                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            id="skipVerification"
                                            name="skipVerification"
                                            checked={formData.skipVerification}
                                            onChange={handleInputChange}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="skipVerification" className="ml-2 block text-sm text-gray-700">
                                            Skip NIN Verification
                                        </label>
                                    </div>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Check this to skip API verification and create members directly from NINs in the file
                                    </p>
                                </div>

                                <div className="mb-6">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? (
                                            <span className="flex items-center justify-center">
                                                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Processing...
                                            </span>
                                        ) : (
                                            'Upload and Verify NINs'
                                        )}
                                    </button>
                                </div>
                            </form>

                            {/* Progress Bar */}
                            {processing && (
                                <div className="mb-6">
                                    <div className="flex justify-between text-sm text-gray-600 mb-2">
                                        <span>Processing...</span>
                                        <span>{progress}%</span>
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-2.5">
                                        <div
                                            className="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                                            style={{ width: `${progress}%` }}
                                        ></div>
                                    </div>
                                </div>
                            )}

                            {/* Results */}
                            {results && (
                                <div className="border-t pt-6">
                                    <h3 className="text-xl font-semibold text-gray-800 mb-4">Verification Results</h3>
                                    
                                    <div className="space-y-4">
                                        <div className="bg-green-50 border border-green-200 rounded-md p-4">
                                            <h4 className="text-green-800 font-semibold mb-2">Summary</h4>
                                            <div className="grid grid-cols-2 gap-4 text-sm">
                                                <div>
                                                    <span className="text-gray-600">Total NINs:</span>
                                                    <span className="font-medium ml-2">{results.results.total}</span>
                                                </div>
                                                <div>
                                                    <span className="text-gray-600">Verified:</span>
                                                    <span className="font-medium text-green-600 ml-2">{results.results.verified}</span>
                                                </div>
                                                <div>
                                                    <span className="text-gray-600">Failed:</span>
                                                    <span className="font-medium text-red-600 ml-2">{results.results.failed}</span>
                                                </div>
                                                <div>
                                                    <span className="text-gray-600">Already Exists:</span>
                                                    <span className="font-medium text-yellow-600 ml-2">{results.results.already_exists}</span>
                                                </div>
                                            </div>
                                            <p className="mt-3 text-green-700">{results.message}</p>
                                        </div>
                                        
                                        {results.results.errors.length > 0 && (
                                            <div className="bg-red-50 border border-red-200 rounded-md p-4">
                                                <h4 className="text-red-800 font-semibold mb-2">Errors</h4>
                                                <div className="max-h-40 overflow-y-auto">
                                                    <ul className="text-sm text-red-700 space-y-1">
                                                        {results.results.errors.map((error, index) => (
                                                            <li key={index}>• {error}</li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Error Messages */}
                            {error && (
                                <div className="bg-red-50 border border-red-200 rounded-md p-4">
                                    <div className="flex">
                                        <div className="flex-shrink-0">
                                            <svg className="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                        <div className="ml-3">
                                            <h3 className="text-sm font-medium text-red-800">Error</h3>
                                            <div className="mt-2 text-sm text-red-700">{error}</div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Instructions */}
                    <div className="bg-blue-50 rounded-lg p-6 mt-6">
                        <h3 className="text-lg font-semibold text-blue-800 mb-3">Instructions</h3>
                        <div className="space-y-4">
                            <div>
                                <h4 className="font-medium text-blue-800 mb-2">Normal Mode (API Verification):</h4>
                                <ul className="list-disc list-inside text-blue-700 space-y-1">
                                    <li>Upload a CSV or Excel file containing NIN numbers</li>
                                    <li>The file must have a column named "NIN" (case-insensitive)</li>
                                    <li>Each NIN will be verified using the Prembly API</li>
                                    <li>Member data will be extracted from API response</li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="font-medium text-blue-800 mb-2">Skip Verification Mode:</h4>
                                <ul className="list-disc list-inside text-blue-700 space-y-1">
                                    <li>Check "Skip NIN Verification" to bypass API calls</li>
                                    <li>File must contain these columns: NIN, FIRST NAME, LAST NAME, GENDER, ADDRESS, PHONE NUMBER</li>
                                    <li>Optional column: DATE OF BIRTH (will default to 1990-01-01 if not provided)</li>
                                    <li>Column names are case-insensitive (e.g., "First Name" or "firstname" both work)</li>
                                    <li>Photo will be set to: https://humanity.peoplefirst.org.ng/images/avatar.png</li>
                                    <li>Cost-effective option for bulk uploads with pre-verified data</li>
                                </ul>
                            </div>
                            <div className="text-sm text-blue-600">
                                <strong>Note:</strong> Processing may take several minutes depending on the number of NINs
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
