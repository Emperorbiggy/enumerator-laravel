import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function DataPlanManagement() {
    const { multiRegistrations, filteredRegistrations, networks, selectedNetwork, stats } = usePage().props;
    const [selectedNetworkLocal, setSelectedNetworkLocal] = useState(selectedNetwork);
    const [selectedItems, setSelectedItems] = useState(new Set());
    const [selectAll, setSelectAll] = useState(false);
    const [showDataPlanModal, setShowDataPlanModal] = useState(false);
    const [selectedDataPlan, setSelectedDataPlan] = useState('');
    const [searchTerm, setSearchTerm] = useState('');

    // Data plans for different networks
    const dataPlans = {
        'MTN': [
            { id: 'mtn_500mb', name: '500MB Daily', price: 100, validity: '24 hours' },
            { id: 'mtn_1gb', name: '1GB Daily', price: 200, validity: '24 hours' },
            { id: 'mtn_2gb', name: '2GB Daily', price: 350, validity: '24 hours' },
            { id: 'mtn_3gb', name: '3GB Weekly', price: 500, validity: '7 days' },
            { id: 'mtn_10gb', name: '10GB Monthly', price: 1500, validity: '30 days' },
        ],
        'Glo': [
            { id: 'glo_500mb', name: '500MB Daily', price: 100, validity: '24 hours' },
            { id: 'glo_1gb', name: '1GB Daily', price: 200, validity: '24 hours' },
            { id: 'glo_2gb', name: '2GB Daily', price: 350, validity: '24 hours' },
            { id: 'glo_4gb', name: '4GB Weekly', price: 500, validity: '7 days' },
            { id: 'glo_15gb', name: '15GB Monthly', price: 1500, validity: '30 days' },
        ],
        'Airtel': [
            { id: 'airtel_500mb', name: '500MB Daily', price: 100, validity: '24 hours' },
            { id: 'airtel_1gb', name: '1GB Daily', price: 200, validity: '24 hours' },
            { id: 'airtel_2gb', name: '2GB Daily', price: 350, validity: '24 hours' },
            { id: 'airtel_5gb', name: '5GB Weekly', price: 500, validity: '7 days' },
            { id: 'airtel_12gb', name: '12GB Monthly', price: 1500, validity: '30 days' },
        ],
        '9mobile': [
            { id: '9mobile_500mb', name: '500MB Daily', price: 100, validity: '24 hours' },
            { id: '9mobile_1gb', name: '1GB Daily', price: 200, validity: '24 hours' },
            { id: '9mobile_2gb', name: '2GB Daily', price: 350, validity: '24 hours' },
            { id: '9mobile_4gb', name: '4GB Weekly', price: 500, validity: '7 days' },
            { id: '9mobile_10gb', name: '10GB Monthly', price: 1500, validity: '30 days' },
        ]
    };

    // Handle network selection
    const handleNetworkChange = (network) => {
        setSelectedNetworkLocal(network);
        setSelectedItems(new Set());
        setSelectAll(false);
        const url = network ? `/admin/data-plan-management?network=${network}` : '/admin/data-plan-management';
        window.location.href = url;
    };

    // Handle individual selection
    const handleItemSelect = (browsingNumber) => {
        const newSelected = new Set(selectedItems);
        if (newSelected.has(browsingNumber)) {
            newSelected.delete(browsingNumber);
        } else {
            newSelected.add(browsingNumber);
        }
        setSelectedItems(newSelected);
        setSelectAll(newSelected.size === filteredRegistrations.length);
    };

    // Handle select all
    const handleSelectAll = () => {
        if (selectAll) {
            setSelectedItems(new Set());
        } else {
            const allNumbers = filteredRegistrations.map(item => item.browsing_number);
            setSelectedItems(new Set(allNumbers));
        }
        setSelectAll(!selectAll);
    };

    // Handle data plan selection and send
    const handleSendDataPlan = () => {
        if (selectedItems.size === 0) {
            alert('Please select at least one person to send data plan');
            return;
        }
        if (!selectedDataPlan) {
            alert('Please select a data plan');
            return;
        }

        const selectedPeople = Array.from(selectedItems).map(number => {
            return filteredRegistrations.find(item => item.browsing_number === number);
        });

        const plan = dataPlans[selectedNetworkLocal]?.find(p => p.id === selectedDataPlan);
        
        console.log('Sending data plan:', {
            people: selectedPeople,
            plan: plan,
            network: selectedNetworkLocal
        });

        // Here you would make an API call to send the data plans
        alert(`Sending ${plan.name} to ${selectedItems.size} people on ${selectedNetworkLocal} network. Total cost: ₦${plan.price * selectedItems.size}`);
        
        setShowDataPlanModal(false);
        setSelectedDataPlan('');
        setSelectedItems(new Set());
        setSelectAll(false);
    };

    // Filter registrations based on search
    const filteredData = filteredRegistrations.filter(item =>
        searchTerm === '' || 
        item.names.some(name => name.toLowerCase().includes(searchTerm.toLowerCase())) ||
        item.browsing_number.includes(searchTerm) ||
        item.emails.some(email => email.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    const selectedCount = selectedItems.size;
    const currentPlans = dataPlans[selectedNetworkLocal] || [];

    return (
        <AdminLayout title="Data Plan Management">
            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-blue-100 text-sm font-medium">Total Multi-Registrations</p>
                                <p className="text-3xl font-bold">{stats.total_multi_registrations}</p>
                            </div>
                            <div className="w-12 h-12 bg-blue-400 rounded-full flex items-center justify-center">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-green-100 text-sm font-medium">Network Providers</p>
                                <p className="text-3xl font-bold">{stats.unique_networks}</p>
                            </div>
                            <div className="w-12 h-12 bg-green-400 rounded-full flex items-center justify-center">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-purple-100 text-sm font-medium">Filtered Results</p>
                                <p className="text-3xl font-bold">{stats.filtered_count}</p>
                                {selectedNetworkLocal && <p className="text-purple-100 text-xs">{selectedNetworkLocal}</p>}
                            </div>
                            <div className="w-12 h-12 bg-purple-400 rounded-full flex items-center justify-center">
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z" />
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
                                    Search People
                                </label>
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Search by name, phone, or email..."
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                />
                            </div>

                            {/* Selection Actions */}
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
                                        onClick={() => setShowDataPlanModal(true)}
                                        disabled={selectedCount === 0}
                                        className="flex-1 bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-400 text-white px-3 py-2 rounded-md text-sm font-medium disabled:cursor-not-allowed"
                                    >
                                        Send Data ({selectedCount})
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Results Table */}
                <div className="bg-white shadow rounded-lg">
                    <div className="px-4 py-5 sm:p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg leading-6 font-medium text-gray-900">
                                Multi-Registrations ({filteredData.length})
                            </h3>
                            {selectedCount > 0 && (
                                <span className="text-sm text-green-600 font-medium">
                                    {selectedCount} selected
                                </span>
                            )}
                        </div>

                        {filteredData.length > 0 ? (
                            <div className="overflow-hidden">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left">
                                                <input
                                                    type="checkbox"
                                                    checked={selectAll}
                                                    onChange={handleSelectAll}
                                                    className="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded"
                                                />
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                People
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Contact Info
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Network
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Registrations
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Period
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {filteredData.map((item) => (
                                            <tr key={item.browsing_number} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedItems.has(item.browsing_number)}
                                                        onChange={() => handleItemSelect(item.browsing_number)}
                                                        className="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded"
                                                    />
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-1">
                                                        {item.names.map((name, index) => (
                                                            <div key={index} className="text-sm text-gray-900">
                                                                {name}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-1">
                                                        <div className="text-sm text-gray-900">{item.browsing_number}</div>
                                                        {item.emails.map((email, index) => (
                                                            <div key={index} className="text-xs text-gray-500">
                                                                {email}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {item.browsing_network}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-900">{item.registration_count} registrations</div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-xs text-gray-500">
                                                        {new Date(item.first_registration).toLocaleDateString()} - {new Date(item.last_registration).toLocaleDateString()}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No multi-registrations found</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    {selectedNetworkLocal ? `No multi-registrations found for ${selectedNetworkLocal}` : 'No multi-registrations found'}
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Data Plan Modal */}
                {showDataPlanModal && (
                    <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                        <div className="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                            <div className="mt-3">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                                        Select Data Plan for {selectedNetworkLocal}
                                    </h3>
                                    <button
                                        onClick={() => setShowDataPlanModal(false)}
                                        className="text-gray-400 hover:text-gray-500"
                                    >
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div className="mb-4">
                                    <p className="text-sm text-gray-600">
                                        Sending data plan to {selectedCount} people on {selectedNetworkLocal} network
                                    </p>
                                </div>

                                <div className="space-y-3 max-h-64 overflow-y-auto">
                                    {currentPlans.map((plan) => (
                                        <label
                                            key={plan.id}
                                            className="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                                        >
                                            <div className="flex items-center">
                                                <input
                                                    type="radio"
                                                    name="dataPlan"
                                                    value={plan.id}
                                                    checked={selectedDataPlan === plan.id}
                                                    onChange={(e) => setSelectedDataPlan(e.target.value)}
                                                    className="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300"
                                                />
                                                <div className="ml-3">
                                                    <div className="text-sm font-medium text-gray-900">{plan.name}</div>
                                                    <div className="text-xs text-gray-500">Validity: {plan.validity}</div>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-sm font-bold text-gray-900">₦{plan.price}</div>
                                                <div className="text-xs text-gray-500">per person</div>
                                            </div>
                                        </label>
                                    ))}
                                </div>

                                <div className="mt-6 flex justify-end space-x-3">
                                    <button
                                        onClick={() => setShowDataPlanModal(false)}
                                        className="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={handleSendDataPlan}
                                        disabled={!selectedDataPlan}
                                        className="bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium disabled:cursor-not-allowed"
                                    >
                                        Send Data Plan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
