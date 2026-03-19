import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function EnumeratorDetails({ enumerator }) {
    return (
        <>
            <Head title={`Enumerator Details - ${enumerator.full_name}`} />
            
            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <header className="bg-white shadow">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center py-6">
                            <div className="flex items-center">
                                <Link href={route('admin.enumerators')} className="text-yellow-600 hover:text-yellow-500 mr-4">
                                    ← Enumerators
                                </Link>
                                <h1 className="text-2xl font-bold text-gray-900">Enumerator Details</h1>
                            </div>
                            <Link
                                href={route('admin.logout')}
                                method="post"
                                className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                            >
                                Logout
                            </Link>
                        </div>
                    </div>
                </header>

                <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                    {/* Enumerator Info Card */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div className="px-4 py-5 sm:px-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Personal Information
                            </h3>
                            <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Enumerator Code</dt>
                                    <dd className="mt-1 text-sm text-gray-900 font-bold">{enumerator.code}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Full Name</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.full_name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Email Address</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.email}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">WhatsApp Number</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.whatsapp}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Location Information */}
                    <div className="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                        <div className="px-4 py-5 sm:px-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Location Information
                            </h3>
                            <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">State</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.state}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">LGA</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.lga}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Ward</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.ward}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Polling Unit</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.polling_unit}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Contact & Network Information */}
                    <div className="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                        <div className="px-4 py-5 sm:px-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Contact & Network Information
                            </h3>
                            <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Browsing Network</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.browsing_network}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Browsing Number</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.browsing_number}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Group Name</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.group_name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Coordinator Phone</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.coordinator_phone}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Bank Information */}
                    <div className="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                        <div className="px-4 py-5 sm:px-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Bank Information
                            </h3>
                            <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Bank Name</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.bank_name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Account Number</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.account_number}</dd>
                                </div>
                                <div className="sm:col-span-2">
                                    <dt className="text-sm font-medium text-gray-500">Account Name</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{enumerator.account_name}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Registration Information */}
                    <div className="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                        <div className="px-4 py-5 sm:px-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Registration Information
                            </h3>
                            <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Registration Date</dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {new Date(enumerator.registered_at).toLocaleDateString('en-US', {
                                            weekday: 'long',
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Time Since Registration</dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {Math.floor((new Date() - new Date(enumerator.registered_at)) / (1000 * 60 * 60 * 24))} days ago
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="mt-6 flex justify-end space-x-4">
                        <Link
                            href={route('admin.enumerators')}
                            className="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium"
                        >
                            Back to List
                        </Link>
                        <button
                            onClick={() => window.print()}
                            className="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                        >
                            Print Details
                        </button>
                    </div>
                </main>
            </div>
        </>
    );
}
