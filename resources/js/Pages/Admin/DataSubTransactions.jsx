import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function DataSubTransactions({ 
    transactions, 
    pagination, 
    stats, 
    filters, 
    available_networks, 
    error 
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [selectedNetwork, setSelectedNetwork] = useState(filters.network || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const applyFilters = () => {
        router.get(route('admin.data-sub-transactions'), {
            search,
            status: selectedStatus,
            network: selectedNetwork,
            date_from: dateFrom,
            date_to: dateTo,
            page: 1
        }, { preserveState: true });
    };

    const clearFilters = () => {
        setSearch('');
        setSelectedStatus('');
        setSelectedNetwork('');
        setDateFrom('');
        setDateTo('');
        router.get(route('admin.data-sub-transactions'), {}, { preserveState: true });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        applyFilters();
    };

    const handlePageChange = (page) => {
        router.get(route('admin.data-sub-transactions'), {
            search,
            status: selectedStatus,
            network: selectedNetwork,
            date_from: dateFrom,
            date_to: dateTo,
            page
        }, { preserveState: true });
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleString();
    };

    const formatAmount = (amount) => {
        return new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN'
        }).format(amount || 0);
    };

    const getStatusColor = (statusClass) => {
        return statusClass || 'bg-gray-100 text-gray-800';
    };

    return (
        <AdminLayout title="Data Subscription Transactions">
            <Head title="Data Subscription Transactions" />

            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <span className="text-white text-sm font-medium">T</span>
                                    </div>
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Total Transactions
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {stats.total_count.toLocaleString()}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <span className="text-white text-sm font-medium">S</span>
                                    </div>
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Successful
                                        </dt>
                                        <dd className="text-lg font-medium text-green-600">
                                            {stats.success_count.toLocaleString()}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                        <span className="text-white text-sm font-medium">F</span>
                                    </div>
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Failed
                                        </dt>
                                        <dd className="text-lg font-medium text-red-600">
                                            {stats.failed_count.toLocaleString()}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <div className="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                        <span className="text-white text-sm font-medium">P</span>
                                    </div>
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Pending
                                        </dt>
                                        <dd className="text-lg font-medium text-yellow-600">
                                            {stats.pending_count.toLocaleString()}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">Filters</h3>
                        <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-6 gap-4">
                            <div>
                                <input
                                    type="text"
                                    placeholder="Search by phone, transaction ID, name..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"
                                />
                            </div>
                            <div>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => setSelectedStatus(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="success">Success</option>
                                    <option value="failed">Failed</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={selectedNetwork}
                                    onChange={(e) => setSelectedNetwork(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"
                                >
                                    <option value="">All Networks</option>
                                    {available_networks.map((network) => (
                                        <option key={network} value={network}>
                                            {network}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"
                                    placeholder="From date"
                                />
                            </div>
                            <div>
                                <input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"
                                    placeholder="To date"
                                />
                            </div>
                            <div className="flex space-x-2">
                                <button
                                    type="submit"
                                    className="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                                >
                                    Apply
                                </button>
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium"
                                >
                                    Clear
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {/* Error Message */}
                {error && (
                    <div className="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                        <div className="text-red-800">{error}</div>
                    </div>
                )}

                {/* Results Summary */}
                <div className="bg-white shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg leading-6 font-medium text-gray-900">
                                Transactions
                            </h3>
                            <div className="text-sm text-gray-500">
                                Showing {pagination.from} to {pagination.to} of {pagination.total} transactions
                            </div>
                        </div>
                    </div>
                </div>

                {/* Transactions Table */}
                <div className="bg-white shadow rounded-lg">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Transaction ID
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Enumerator
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Phone
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Plan
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Registered Users
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {transactions.length > 0 ? (
                                    transactions.map((transaction) => (
                                        <tr key={transaction.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {transaction.transaction_id}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {formatDate(transaction.created_at)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">
                                                    {transaction.enumerator_name}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {transaction.enumerator_code}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {transaction.phone}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">
                                                    {transaction.plan_name}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {transaction.network}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {formatAmount(transaction.amount)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(transaction.status_class)}`}>
                                                    {transaction.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {transaction.registered_users_count || 0}
                                                {transaction.data_source && (
                                                    <div className="text-xs text-gray-400">
                                                        ({transaction.data_source})
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="8" className="px-6 py-4 text-center text-sm text-gray-500">
                                            No transactions found
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {pagination.last_page > 1 && (
                        <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div className="flex-1 flex justify-between sm:hidden">
                                <button
                                    onClick={() => handlePageChange(pagination.current_page - 1)}
                                    disabled={!pagination.has_previous}
                                    className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Previous
                                </button>
                                <button
                                    onClick={() => handlePageChange(pagination.current_page + 1)}
                                    disabled={!pagination.has_more}
                                    className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Next
                                </button>
                            </div>
                            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-sm text-gray-700">
                                        Showing <span className="font-medium">{pagination.from}</span> to{' '}
                                        <span className="font-medium">{pagination.to}</span> of{' '}
                                        <span className="font-medium">{pagination.total}</span> results
                                    </p>
                                </div>
                                <div>
                                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <button
                                            onClick={() => handlePageChange(pagination.current_page - 1)}
                                            disabled={!pagination.has_previous}
                                            className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Previous
                                        </button>
                                        
                                        {/* Page numbers */}
                                        {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
                                            let pageNum;
                                            if (pagination.last_page <= 5) {
                                                pageNum = i + 1;
                                            } else if (pagination.current_page <= 3) {
                                                pageNum = i + 1;
                                            } else if (pagination.current_page >= pagination.last_page - 2) {
                                                pageNum = pagination.last_page - 4 + i;
                                            } else {
                                                pageNum = pagination.current_page - 2 + i;
                                            }
                                            
                                            return (
                                                <button
                                                    key={pageNum}
                                                    onClick={() => handlePageChange(pageNum)}
                                                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                        pageNum === pagination.current_page
                                                            ? 'z-10 bg-yellow-50 border-yellow-500 text-yellow-600'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    }`}
                                                >
                                                    {pageNum}
                                                </button>
                                            );
                                        })}
                                        
                                        <button
                                            onClick={() => handlePageChange(pagination.current_page + 1)}
                                            disabled={!pagination.has_more}
                                            className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Next
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
