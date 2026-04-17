import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Enumerators({ enumerators, lgas, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedLga, setSelectedLga] = useState(filters.lga || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const applyFilters = () => {
        router.get(route('admin.enumerators'), {
            search,
            lga: selectedLga,
            date_from: dateFrom,
            date_to: dateTo,
        }, { preserveState: true });
    };

    const clearFilters = () => {
        setSearch('');
        setSelectedLga('');
        setDateFrom('');
        setDateTo('');
        router.get(route('admin.enumerators'), {}, { preserveState: true });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        applyFilters();
    };

    return (
        <AdminLayout title="Enumerators Management">
            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {/* Filters */}
                    <div className="bg-white shadow rounded-lg mb-6">
                        <div className="px-4 py-5 sm:p-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">Filters</h3>
                            <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                                <div>
                                    <input
                                        type="text"
                                        placeholder="Search by name, email, code..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"
                                    />
                                </div>
                                <div>
                                    <select
                                        value={selectedLga}
                                        onChange={(e) => setSelectedLga(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"
                                    >
                                        <option value="">All LGAs</option>
                                        {Array.isArray(lgas) && lgas.map((lga) => (
                                            <option key={lga} value={lga}>{lga}</option>
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

                    {/* Results Summary */}
                    <div className="bg-white shadow rounded-lg mb-6">
                        <div className="px-4 py-5 sm:p-6">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                    Total Results: {enumerators.total}
                                </h3>
                                <div className="flex items-center space-x-4">
                                    <div className="text-sm text-gray-500">
                                        Showing {enumerators.from} to {enumerators.to} of {enumerators.total} entries
                                    </div>
                                    <a
                                        href={route('admin.enumerators.export')}
                                        className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium inline-flex items-center"
                                    >
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Export All Enumerators
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Enumerators Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        {/* Mobile scroll indicator */}
                        <div className="sm:hidden bg-gray-50 px-4 py-2 text-xs text-gray-600 border-b border-gray-200">
                            <div className="flex items-center">
                                <svg className="w-4 h-4 mr-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                                Swipe left or scroll horizontally to see more columns
                            </div>
                        </div>
                        
                        {/* Desktop scroll indicator */}
                        <div className="hidden sm:block bg-gray-50 px-4 py-2 text-xs text-gray-600 border-b border-gray-200">
                            <div className="flex items-center justify-between">
                                <span className="flex items-center">
                                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                    </svg>
                                    Scroll horizontally to see all columns
                                </span>
                                <span className="text-gray-400">
                                    {enumerators.data.length} of {enumerators.total} enumerators
                                </span>
                            </div>
                        </div>

                        <div className="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50 sticky top-0 z-10">
                                    <tr>
                                        <th className="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                            Code
                                        </th>
                                        <th className="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                            Name
                                        </th>
                                        <th className="hidden md:table-cell px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                            Email
                                        </th>
                                        <th className="hidden lg:table-cell px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                            WhatsApp
                                        </th>
                                        <th className="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                            LGA
                                        </th>
                                        <th className="hidden sm:table-cell px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                            Ward
                                        </th>
                                        <th className="hidden md:table-cell px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                            Registered
                                        </th>
                                        <th className="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {enumerators.data.map((enumerator) => (
                                        <tr key={enumerator.id} className="hover:bg-gray-50 border-b border-gray-100">
                                            <td className="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <div className="flex items-center">
                                                    <span className="text-xs sm:text-sm font-mono bg-gray-100 px-2 py-1 rounded">
                                                        {enumerator.code}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">
                                                    {enumerator.full_name}
                                                </div>
                                                <div className="md:hidden text-xs text-gray-500 mt-1">
                                                    {enumerator.email}
                                                </div>
                                                <div className="lg:hidden text-xs text-gray-500 mt-1">
                                                    📱 {enumerator.whatsapp}
                                                </div>
                                                <div className="sm:hidden text-xs text-gray-500 mt-1">
                                                    📍 {enumerator.ward}, {enumerator.lga}
                                                </div>
                                            </td>
                                            <td className="hidden md:table-cell px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div className="max-w-xs truncate" title={enumerator.email}>
                                                    {enumerator.email}
                                                </div>
                                            </td>
                                            <td className="hidden lg:table-cell px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {enumerator.whatsapp}
                                            </td>
                                            <td className="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {enumerator.lga}
                                                </span>
                                            </td>
                                            <td className="hidden sm:table-cell px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {enumerator.ward}
                                            </td>
                                            <td className="hidden md:table-cell px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {new Date(enumerator.registered_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <Link
                                                    href={route('admin.enumerators.show', enumerator.id)}
                                                    className="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-yellow-600 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                                                >
                                                    <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {enumerators.links.length > 1 && (
                            <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    {enumerators.prev_page_url ? (
                                        <Link
                                            href={enumerators.prev_page_url}
                                            className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Previous
                                        </Link>
                                    ) : (
                                        <span className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                                            Previous
                                        </span>
                                    )}
                                    {enumerators.next_page_url ? (
                                        <Link
                                            href={enumerators.next_page_url}
                                            className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Next
                                        </Link>
                                    ) : (
                                        <span className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                                            Next
                                        </span>
                                    )}
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Page <span className="font-medium">{enumerators.current_page}</span> of{' '}
                                            <span className="font-medium">{enumerators.last_page}</span>
                                        </p>
                                    </div>
                                    <div>
                                        <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            {enumerators.links.map((link, index) => (
                                                <span key={index}>
                                                    {link.url ? (
                                                        <Link
                                                            href={link.url}
                                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                                            className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                                link.active
                                                                    ? 'z-10 bg-yellow-50 border-yellow-500 text-yellow-600'
                                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                            }`}
                                                            preserveScroll
                                                        />
                                                    ) : (
                                                        <span
                                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                                            className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium cursor-not-allowed ${
                                                                link.active
                                                                    ? 'z-10 bg-yellow-50 border-yellow-500 text-yellow-600'
                                                                    : 'bg-white border-gray-300 text-gray-500'
                                                            }`}
                                                        />
                                                    )}
                                                </span>
                                            ))}
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
