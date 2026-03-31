import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { useToast } from '@/Components/ToastContainer';

export default function DataSub() {
    const { topPerformers, filteredPerformers, networks, selectedNetwork, stats, externalData, defaultDataPlans } = usePage().props;
    const [selectedNetworkLocal, setSelectedNetworkLocal] = useState(selectedNetwork);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedItems, setSelectedItems] = useState(new Set());
    const [selectAll, setSelectAll] = useState(false);
    const [externalDataState, setExternalDataState] = useState(externalData || null);
    const [selectedDataPlan, setSelectedDataPlan] = useState('');
    const [isSending, setIsSending] = useState(false);
    const [sendingProgress, setSendingProgress] = useState({ current: 0, total: 0 });
    const [individualSending, setIndividualSending] = useState(new Set());
    const [markingCompleted, setMarkingCompleted] = useState(false);
    const { success, error, info } = useToast();

    // Handle network selection
    const handleNetworkChange = (network) => {
        setSelectedNetworkLocal(network);
        setSelectedItems(new Set());
        setSelectAll(false);
        setSelectedDataPlan(''); // Reset data plan when network changes
    };

    // Handle individual selection
    const handleItemSelect = (performerId) => {
        const newSelected = new Set(selectedItems);
        if (newSelected.has(performerId)) {
            newSelected.delete(performerId);
        } else {
            newSelected.add(performerId);
        }
        setSelectedItems(newSelected);
        setSelectAll(newSelected.size === filteredPerformersList.length);
    };

    // Handle select all
    const handleSelectAll = () => {
        if (selectAll) {
            setSelectedItems(new Set());
        } else {
            const allIds = filteredPerformersList.map(performer => performer.id);
            setSelectedItems(new Set(allIds));
        }
        setSelectAll(!selectAll);
    };

    // Filter data plans by selected network
    const getFilteredDataPlans = () => {
        if (!externalDataState || !Array.isArray(externalDataState) || !selectedNetworkLocal) {
            return [];
        }
        
        return externalDataState.filter(plan => 
            plan.network && plan.network.toLowerCase() === selectedNetworkLocal.toLowerCase()
        );
    };

    // Handle mark completed
    const handleMarkCompleted = async () => {
        if (!window.confirm('Are you sure you want to mark all filtered performers as completed? This will create successful transactions for all of them with their default data plans.')) {
            return;
        }

        setMarkingCompleted(true);

        try {
            info('Marking all filtered performers as completed...', 3000);

            const response = await fetch('/admin/mark-all-completed', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    performer_ids: filteredPerformersList.map(p => p.id),
                }),
            });

            const data = await response.json();

            if (data.success) {
                success(`Successfully marked ${data.marked_count} performers as completed!`, 5000);
                // Refresh the page to update the data
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                error('Error marking completed: ' + data.message, 5000);
            }

        } catch (err) {
            console.error('Mark completed error:', err);
            error('Error marking completed: ' + err.message, 5000);
        } finally {
            setMarkingCompleted(false);
        }
    };

    // Handle send data
    const handleSendData = async () => {
        if (selectedItems.size === 0 || !selectedDataPlan || !selectedNetworkLocal) {
            return;
        }

        setIsSending(true);

        try {
            const performerIds = Array.from(selectedItems);
            setSendingProgress({ current: 0, total: performerIds.length });
            
            // Show initial info toast
            info(`Starting batch data send for ${performerIds.length} enumerators...`, 3000);
            
            // Use fetch instead of Inertia router for API calls
            const response = await fetch('/admin/send-batch-data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    performer_ids: performerIds,
                    plan_code: selectedDataPlan,
                    network: selectedNetworkLocal,
                }),
            });

            const data = await response.json();

            if (data.success) {
                success(`Data sent successfully! ${data.message}`, 5000);
                setSelectedItems(new Set());
                setSelectAll(false);
                setSelectedDataPlan('');
                // Refresh the page to update the data
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                error('Error sending data: ' + data.message, 5000);
            }

        } catch (err) {
            console.error('Send data error:', err);
            error('Error sending data: ' + err.message, 5000);
        } finally {
            setIsSending(false);
            setSendingProgress({ current: 0, total: 0 });
        }
    };

    // Handle individual data sending
    const handleSendIndividualData = async (performer) => {
        if (!performer.browsing_number || !performer.browsing_network) {
            error('Performer missing browsing number or network', 3000);
            return;
        }

        // Add to sending set to disable button
        setIndividualSending(prev => new Set(prev).add(performer.id));

        try {
            const defaultPlan = defaultDataPlans[performer.browsing_network.toUpperCase()];
            
            if (!defaultPlan) {
                error(`No default plan configured for network: ${performer.browsing_network}`, 3000);
                return;
            }

            info(`Sending data to ${performer.full_name} using ${defaultPlan} plan...`, 2000);

            const response = await fetch('/admin/send-individual-data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    performer_id: performer.id,
                    browsing_number: performer.browsing_number,
                    browsing_network: performer.browsing_network,
                }),
            });

            const data = await response.json();

            if (data.success) {
                success(data.message, 4000);
                // Refresh the page to update the data
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                error('Error sending data: ' + data.message, 4000);
            }

        } catch (err) {
            console.error('Send individual data error:', err);
            error('Error sending data: ' + err.message, 4000);
        } finally {
            // Remove from sending set to re-enable button
            setIndividualSending(prev => {
                const newSet = new Set(prev);
                newSet.delete(performer.id);
                return newSet;
            });
        }
    };

    // Filter performers based on search and network
    const filteredPerformersList = filteredPerformers.filter(performer => {
        const matchesSearch = !searchTerm || 
            performer.full_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            performer.code.toLowerCase().includes(searchTerm.toLowerCase()) ||
            performer.email.toLowerCase().includes(searchTerm.toLowerCase());
        
        const matchesNetwork = !selectedNetworkLocal || 
            (performer.browsing_network && performer.browsing_network.toLowerCase() === selectedNetworkLocal.toLowerCase());
        
        const hasEnoughRegistrations = performer.members_registered >= 2;
        
        return matchesSearch && matchesNetwork && hasEnoughRegistrations;
    });

    // Network colors for badges
    const networkColors = {
        'MTN': 'bg-red-100 text-red-800',
        'Glo': 'bg-green-100 text-green-800',
        'Airtel': 'bg-blue-100 text-blue-800',
        '9mobile': 'bg-purple-100 text-purple-800',
        'mtn': 'bg-red-100 text-red-800',
        'glo': 'bg-green-100 text-green-800',
        'airtel': 'bg-blue-100 text-blue-800',
    };

    // Calculate local stats
    const localStats = {
        total_enumerators: topPerformers.length,
        total_top_performers: topPerformers.filter(p => p.members_registered >= 2).length,
        unique_networks: networks.length,
        filtered_count: filteredPerformersList.length,
    };

    const getNetworkColor = (network) => {
        return networkColors[network] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AdminLayout title="Data Sub">
            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-blue-100 text-sm font-medium">Total Enumerators</p>
                                <p className="text-3xl font-bold">{localStats.total_enumerators}</p>
                                <p className="text-blue-100 text-xs">All registered</p>
                            </div>
                            <div className="w-12 h-12 bg-blue-400 rounded-full flex items-center justify-center">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-yellow-100 text-sm font-medium">Top Performers</p>
                                <p className="text-3xl font-bold">{localStats.total_top_performers}</p>
                                <p className="text-yellow-100 text-xs">With 3+ members</p>
                            </div>
                            <div className="w-12 h-12 bg-yellow-400 rounded-full flex items-center justify-center">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-green-100 text-sm font-medium">Unique Networks</p>
                                <p className="text-3xl font-bold">{localStats.unique_networks}</p>
                                <p className="text-green-100 text-xs">Available networks</p>
                            </div>
                            <div className="w-12 h-12 bg-green-400 rounded-full flex items-center justify-center">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-purple-100 text-sm font-medium">Filtered Results</p>
                                <p className="text-3xl font-bold">{localStats.filtered_count}</p>
                                {selectedNetworkLocal && <p className="text-purple-100 text-xs">{selectedNetworkLocal} network</p>}
                            </div>
                            <div className="w-12 h-12 bg-purple-400 rounded-full flex items-center justify-center">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Controls Section */}
                <div className="bg-white shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {/* Network Filter */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Select Network
                                </label>
                                <select
                                    value={selectedNetworkLocal}
                                    onChange={(e) => handleNetworkChange(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                >
                                    <option value="">All Networks</option>
                                    {networks.map((network) => (
                                        <option key={network} value={network}>
                                            {network}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Search */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Search Performers
                                </label>
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Search by name, code, or email..."
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                />
                            </div>

                            {/* Data Plan Selector */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Select Data Plan
                                </label>
                                <select
                                    value={selectedDataPlan}
                                    onChange={(e) => setSelectedDataPlan(e.target.value)}
                                    disabled={!selectedNetworkLocal}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                >
                                    <option value="">
                                        {selectedNetworkLocal ? `Select ${selectedNetworkLocal} Plan` : 'Select Network First'}
                                    </option>
                                    {getFilteredDataPlans().map((plan) => (
                                        <option key={plan.planApiId} value={plan.planApiId}>
                                            {plan.plan} - ₦{plan.price}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Selection Controls */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Selection Actions
                                </label>
                                <div className="flex space-x-2">
                                    <button
                                        onClick={handleSelectAll}
                                        className="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-md text-sm font-medium"
                                    >
                                        {selectAll ? 'Deselect All' : 'Select All'}
                                    </button>
                                    <button
                                        onClick={handleSendData}
                                        disabled={selectedItems.size === 0 || !selectedDataPlan || isSending}
                                        className="flex-1 bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-400 text-white px-3 py-2 rounded-md text-sm font-medium disabled:cursor-not-allowed"
                                    >
                                        {isSending ? (
                                            <div className="flex items-center justify-center">
                                                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Processing... {sendingProgress.current > 0 && `${sendingProgress.current}/${sendingProgress.total}`}
                                            </div>
                                        ) : (
                                            `Send Data (${selectedItems.size})`
                                        )}
                                    </button>
                                </div>
                                <div className="mt-2">
                                    <button
                                        onClick={handleMarkCompleted}
                                        disabled={markingCompleted || filteredPerformersList.length === 0}
                                        className="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-3 py-2 rounded-md text-sm font-medium disabled:cursor-not-allowed flex items-center justify-center"
                                    >
                                        {markingCompleted ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Processing...
                                            </>
                                        ) : (
                                            <>
                                                <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Mark All Filtered as Completed ({filteredPerformersList.length})
                                            </>
                                        )}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Default Data Plans Info */}
                <div className="bg-blue-50 border border-blue-200 rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <h3 className="text-lg leading-6 font-medium text-blue-900 mb-4">
                            Default Data Plans for Individual Sending
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {Object.entries(defaultDataPlans).map(([network, plan]) => (
                                <div key={network} className="bg-white rounded-lg p-3 border border-blue-200">
                                    <div className="flex items-center">
                                        <div className={`w-3 h-3 rounded-full mr-2 ${
                                            network === 'MTN' ? 'bg-yellow-500' :
                                            network === 'GLO' ? 'bg-green-500' :
                                            network === 'AIRTEL' ? 'bg-red-500' : 'bg-gray-500'
                                        }`}></div>
                                        <span className="font-semibold text-gray-900">{network}</span>
                                    </div>
                                    <div className="mt-1 text-sm text-gray-600">
                                        Default Plan: <span className="font-mono font-bold text-blue-600">{plan}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <p className="mt-3 text-sm text-blue-700">
                            💡 Individual "Send Data" buttons automatically use the default plan for each enumerator's network.
                        </p>
                    </div>
                </div>

                {/* Top Performers List */}
                <div className="bg-white shadow rounded-lg">
                    <div className="px-4 py-5 sm:p-6">
                        <div className="flex items-center justify-between mb-6">
                            <div className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={selectAll}
                                    onChange={handleSelectAll}
                                    className="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded mr-3"
                                />
                                <h3 className="text-xl leading-6 font-bold text-gray-900 flex items-center">
                                    <div className="w-8 h-8 bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center mr-3">
                                        <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                    </div>
                                    Top 10 Performers
                                </h3>
                            </div>
                            <div className="flex items-center space-x-4">
                                {selectedItems.size > 0 && (
                                    <span className="text-sm text-green-600 font-medium">
                                        {selectedItems.size} selected
                                    </span>
                                )}
                                <div className="text-sm text-gray-500">
                                    Showing {filteredPerformersList.length} of {localStats.total_top_performers} performers
                                </div>
                            </div>
                        </div>

                        {filteredPerformersList.length > 0 ? (
                            <div className="space-y-4">
                                {filteredPerformersList.map((performer, index) => (
                                    <div key={performer.id} className="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition-shadow">
                                        <div className="flex items-center justify-between">
                                            {/* Left Section - Rank and Basic Info */}
                                            <div className="flex items-center flex-1">
                                                {/* Checkbox */}
                                                <input
                                                    type="checkbox"
                                                    checked={selectedItems.has(performer.id)}
                                                    onChange={() => handleItemSelect(performer.id)}
                                                    className="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded mr-4"
                                                />
                                                
                                                {/* Rank Badge */}
                                                <div className={`w-10 h-10 rounded-full flex items-center justify-center mr-4 text-white font-bold text-lg ${
                                                    index === 0 ? 'bg-yellow-500 shadow-lg' : 
                                                    index === 1 ? 'bg-gray-400 shadow-md' : 
                                                    index === 2 ? 'bg-orange-600 shadow-md' : 'bg-gray-300'
                                                }`}>
                                                    {index + 1}
                                                </div>
                                                
                                                {/* Performer Info */}
                                                <div className="flex-1">
                                                    <div className="flex items-center mb-2">
                                                        <h4 className="text-lg font-semibold text-gray-900 mr-3">
                                                            {performer.full_name}
                                                        </h4>
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getNetworkColor(performer.browsing_network)}`}>
                                                            {performer.browsing_network}
                                                        </span>
                                                    </div>
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-600">
                                                        <div className="flex items-center">
                                                            <svg className="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                                            </svg>
                                                            Code: <span className="font-medium text-gray-900 ml-1">{performer.code}</span>
                                                        </div>
                                                        <div className="flex items-center">
                                                            <svg className="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                            </svg>
                                                            {performer.email}
                                                        </div>
                                                        <div className="flex items-center">
                                                            <svg className="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                            </svg>
                                                            {performer.whatsapp}
                                                        </div>
                                                        <div className="flex items-center">
                                                            <svg className="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            </svg>
                                                            {performer.lga}, {performer.ward}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Right Section - Stats and Actions */}
                                            <div className="flex items-center gap-4 ml-6">
                                                {/* Member Count */}
                                                <div className="text-right">
                                                    <div className="text-2xl font-bold text-green-600">
                                                        {performer.members_registered}
                                                    </div>
                                                    <div className="text-xs text-gray-500">members</div>
                                                </div>

                                                {/* Actions */}
                                                <div className="flex flex-col space-y-2">
                                                    <Link
                                                        href={`/admin/enumerator/${performer.code}/members`}
                                                        className="inline-flex items-center px-3 py-2 border border-yellow-600 shadow-sm text-sm font-medium rounded-md text-yellow-600 bg-white hover:bg-yellow-50 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                                    >
                                                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        View Members
                                                    </Link>
                                                    
                                                    <button 
                                                        onClick={() => handleSendIndividualData(performer)}
                                                        disabled={individualSending.has(performer.id) || !performer.browsing_number || !performer.browsing_network}
                                                        className="inline-flex items-center px-3 py-2 border border-green-600 shadow-sm text-sm font-medium rounded-md text-green-600 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                    >
                                                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                        </svg>
                                                        {individualSending.has(performer.id) ? 'Sending...' : 'Send Data'}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Browsing Number Display */}
                                        <div className="mt-3 pt-3 border-t border-gray-200">
                                            <div className="flex items-center text-sm text-gray-600">
                                                <svg className="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                Browsing Number: <span className="font-medium text-gray-900 ml-1">{performer.browsing_number}</span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No performers found</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    {selectedNetworkLocal ? `No top performers found for ${selectedNetworkLocal} network` : 'No top performers found'}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
