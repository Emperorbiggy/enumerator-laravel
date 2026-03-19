import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function DataSub() {
    const { topPerformers, filteredPerformers, networks, selectedNetwork, stats, externalData } = usePage().props;
    const [selectedNetworkLocal, setSelectedNetworkLocal] = useState(selectedNetwork);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedItems, setSelectedItems] = useState(new Set());
    const [selectAll, setSelectAll] = useState(false);
    const [externalDataState, setExternalDataState] = useState(externalData || null);

    // Handle network selection
    const handleNetworkChange = (network) => {
        setSelectedNetworkLocal(network);
        setSelectedItems(new Set());
        setSelectAll(false);
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
        setSelectAll(newSelected.size === filteredData.length);
    };

    // Handle select all
    const handleSelectAll = () => {
        if (selectAll) {
            setSelectedItems(new Set());
        } else {
            const allIds = filteredData.map(performer => performer.id);
            setSelectedItems(new Set(allIds));
        }
        setSelectAll(!selectAll);
    };

    // Filter performers based on search and network
    const filteredData = topPerformers.filter(performer => {
        const matchesSearch = searchTerm === '' || 
            performer.full_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            performer.code.toLowerCase().includes(searchTerm.toLowerCase()) ||
            performer.email.toLowerCase().includes(searchTerm.toLowerCase());
        
        const matchesNetwork = !selectedNetworkLocal || performer.browsing_network === selectedNetworkLocal;
        
        return matchesSearch && matchesNetwork;
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
        total_top_performers: topPerformers.length,
        unique_networks: networks.length,
        filtered_count: filteredData.length,
    };

    const getNetworkColor = (network) => {
        return networkColors[network] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AdminLayout title="Data Sub">
            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div className="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-yellow-100 text-sm font-medium">Top Performers</p>
                                <p className="text-3xl font-bold">{localStats.total_top_performers}</p>
                                <p className="text-yellow-100 text-xs">With 2+ members</p>
                            </div>
                            <div className="w-12 h-12 bg-yellow-400 rounded-full flex items-center justify-center">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-blue-100 text-sm font-medium">Network Providers</p>
                                <p className="text-3xl font-bold">{localStats.unique_networks}</p>
                                <p className="text-blue-100 text-xs">Available networks</p>
                            </div>
                            <div className="w-12 h-12 bg-blue-400 rounded-full flex items-center justify-center">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-green-100 text-sm font-medium">Filtered Results</p>
                                <p className="text-3xl font-bold">{localStats.filtered_count}</p>
                                {selectedNetworkLocal && <p className="text-green-100 text-xs">{selectedNetworkLocal} network</p>}
                            </div>
                            <div className="w-12 h-12 bg-green-400 rounded-full flex items-center justify-center">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {/* External API Data Section */}
                {externalDataState && (
                    <div className="bg-white shadow rounded-lg mb-6">
                        <div className="px-4 py-5 sm:p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 flex items-center">
                                    <div className="w-8 h-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                                        <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                                        </svg>
                                    </div>
                                    Available Data Plans
                                </h3>
                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    API Connected
                                </span>
                            </div>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {Array.isArray(externalDataState) ? (
                                    externalDataState.slice(0, 6).map((plan, index) => (
                                        <div key={index} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div className="flex items-center justify-between mb-2">
                                                <h4 className="text-sm font-medium text-gray-900">{plan.name || plan.plan_name || `Plan ${index + 1}`}</h4>
                                                <span className="text-lg font-bold text-green-600">₦{plan.price || plan.amount || '0'}</span>
                                            </div>
                                            <div className="text-xs text-gray-500 space-y-1">
                                                {plan.size && <div>Size: {plan.size}</div>}
                                                {plan.validity && <div>Validity: {plan.validity}</div>}
                                                {plan.network && <div>Network: {plan.network}</div>}
                                                {plan.type && <div>Type: {plan.type}</div>}
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="col-span-full">
                                        <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                            <div className="flex">
                                                <div className="flex-shrink-0">
                                                    <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div className="ml-3">
                                                    <h3 className="text-sm font-medium text-yellow-800">Data Format Notice</h3>
                                                    <div className="mt-2 text-sm text-yellow-700">
                                                        <p>External API data received but format needs to be processed.</p>
                                                        <p className="mt-1">Raw data: {JSON.stringify(externalDataState).substring(0, 100)}...</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                            
                            {Array.isArray(externalDataState) && externalDataState.length > 6 && (
                                <div className="mt-4 text-center">
                                    <button className="text-yellow-600 hover:text-yellow-500 text-sm font-medium">
                                        View all {externalDataState.length} data plans →
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                )}

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
                                        disabled={selectedItems.size === 0}
                                        className="flex-1 bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-400 text-white px-3 py-2 rounded-md text-sm font-medium disabled:cursor-not-allowed"
                                    >
                                        Send Data ({selectedItems.size})
                                    </button>
                                </div>
                            </div>
                        </div>
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
                                    Showing {filteredData.length} of {localStats.total_top_performers} performers
                                </div>
                            </div>
                        </div>

                        {filteredData.length > 0 ? (
                            <div className="space-y-4">
                                {filteredData.map((performer, index) => (
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
                                                    
                                                    <button className="inline-flex items-center px-3 py-2 border border-green-600 shadow-sm text-sm font-medium rounded-md text-green-600 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500">
                                                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                        </svg>
                                                        Send Data
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
