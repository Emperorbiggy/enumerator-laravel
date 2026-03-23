import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function EnumeratorDetails({ enumerator, performance }) {
    const [copied, setCopied] = useState('');
    const [isEditing, setIsEditing] = useState(false);
    const [editForm, setEditForm] = useState({
        browsing_network: enumerator.browsing_network || '',
        browsing_number: enumerator.browsing_number || '',
    });
    const [isSaving, setIsSaving] = useState(false);
    const [toast, setToast] = useState(null);
    
    const { flash } = usePage().props;

    const networks = [
        'MTN',
        'GLO',
        'AIRTEL',
        '9MOBILE',
        'ETISALAT',
    ];

    // Show toast if there's a flash message
    useEffect(() => {
        if (flash?.success) {
            showToast(flash.success, 'success');
        }
        if (flash?.error) {
            showToast(flash.error, 'error');
        }
    }, [flash]);

    const showToast = (message, type = 'success') => {
        setToast({ message, type });
        setTimeout(() => {
            setToast(null);
        }, 3000);
    };

    const copyToClipboard = (text, field) => {
        navigator.clipboard.writeText(text);
        setCopied(field);
        setTimeout(() => setCopied(''), 2000);
    };

    const CopyButton = ({ text, field, label }) => (
        <button
            onClick={() => copyToClipboard(text, field)}
            className="ml-2 px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
        >
            {copied === field ? 'Copied!' : `Copy ${label}`}
        </button>
    );

    const handleEdit = () => {
        setEditForm({
            browsing_network: enumerator.browsing_network || '',
            browsing_number: enumerator.browsing_number || '',
        });
        setIsEditing(true);
    };

    const handleSave = () => {
        setIsSaving(true);
        
        router.put(
            route('admin.enumerators.update', enumerator.id),
            editForm,
            {
                onSuccess: () => {
                    setIsEditing(false);
                    setIsSaving(false);
                    // Toast will be shown by useEffect when flash message arrives
                },
                onError: (errors) => {
                    setIsSaving(false);
                    // Show error message as toast
                    if (errors.browsing_number) {
                        showToast(errors.browsing_number, 'error');
                    } else if (errors.general) {
                        showToast(errors.general, 'error');
                    } else {
                        showToast('Failed to update browsing details. Please try again.', 'error');
                    }
                },
                preserveScroll: true,
            }
        );
    };

    const handleCancel = () => {
        setIsEditing(false);
        setEditForm({
            browsing_network: enumerator.browsing_network || '',
            browsing_number: enumerator.browsing_number || '',
        });
    };
    return (
        <AdminLayout title={`Enumerator Details - ${enumerator.full_name}`}>
            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
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
                                    <dd className="mt-1 text-sm text-gray-900 flex items-center">
                                        {enumerator.email}
                                        {enumerator.email && <CopyButton text={enumerator.email} field="email" label="Email" />}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">WhatsApp Number</dt>
                                    <dd className="mt-1 text-sm text-gray-900 flex items-center">
                                        {enumerator.whatsapp}
                                        {enumerator.whatsapp && <CopyButton text={enumerator.whatsapp} field="whatsapp" label="WhatsApp" />}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Performance Metrics */}
                    <div className="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                        <div className="px-4 py-5 sm:px-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Performance Metrics
                            </h3>
                            <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                                <div className="bg-blue-50 p-4 rounded-lg">
                                    <dt className="text-sm font-medium text-blue-600">Total Members Registered</dt>
                                    <dd className="mt-2 text-3xl font-bold text-blue-900">{performance?.members_registered || 0}</dd>
                                </div>
                                <div className="bg-green-50 p-4 rounded-lg">
                                    <dt className="text-sm font-medium text-green-600">Daily Registration Rate</dt>
                                    <dd className="mt-2 text-3xl font-bold text-green-900">{performance?.registration_rate || 0}</dd>
                                </div>
                                <div className="bg-purple-50 p-4 rounded-lg">
                                    <dt className="text-sm font-medium text-purple-600">Days Active</dt>
                                    <dd className="mt-2 text-3xl font-bold text-purple-900">
                                        {Math.floor((new Date() - new Date(enumerator.registered_at)) / (1000 * 60 * 60 * 24))}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Recent Members */}
                    {performance?.recent_members && performance.recent_members.length > 0 && (
                        <div className="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                            <div className="px-4 py-5 sm:px-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Recent Members Registered (Last 5)
                                </h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Name
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Phone
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Gender
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Registration Date
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {performance.recent_members.map((member, index) => (
                                                <tr key={index}>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {member.first_name} {member.last_name}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {member.phone_number}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {member.gender}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {new Date(member.registration_date).toLocaleDateString()}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}

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
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                    Contact & Network Information
                                </h3>
                                <button
                                    onClick={handleEdit}
                                    className="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded-md text-sm font-medium"
                                >
                                    Edit Browsing Details
                                </button>
                            </div>
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
                                    <dd className="mt-1 text-sm text-gray-900 flex items-center">
                                        {enumerator.bank_name}
                                        {enumerator.bank_name && <CopyButton text={enumerator.bank_name} field="bank_name" label="Bank" />}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Account Number</dt>
                                    <dd className="mt-1 text-sm text-gray-900 flex items-center">
                                        {enumerator.account_number}
                                        {enumerator.account_number && <CopyButton text={enumerator.account_number} field="account_number" label="Account" />}
                                    </dd>
                                </div>
                                <div className="sm:col-span-2">
                                    <dt className="text-sm font-medium text-gray-500">Account Name</dt>
                                    <dd className="mt-1 text-sm text-gray-900 flex items-center">
                                        {enumerator.account_name}
                                        {enumerator.account_name && <CopyButton text={enumerator.account_name} field="account_name" label="Name" />}
                                    </dd>
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

                    {/* Edit Modal */}
                    {isEditing && (
                        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                            <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                                <div className="mt-3">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                        Edit Browsing Details
                                    </h3>
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Browsing Network
                                            </label>
                                            <select
                                                value={editForm.browsing_network}
                                                onChange={(e) => setEditForm({...editForm, browsing_network: e.target.value})}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"
                                            >
                                                <option value="">Select Network</option>
                                                {networks.map(network => (
                                                    <option key={network} value={network}>
                                                        {network}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Browsing Number
                                            </label>
                                            <input
                                                type="text"
                                                value={editForm.browsing_number}
                                                onChange={(e) => setEditForm({...editForm, browsing_number: e.target.value})}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"
                                                placeholder="Enter browsing number"
                                            />
                                        </div>
                                    </div>
                                    <div className="mt-6 flex justify-end space-x-3">
                                        <button
                                            onClick={handleCancel}
                                            disabled={isSaving}
                                            className="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            onClick={handleSave}
                                            disabled={isSaving}
                                            className="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
                                        >
                                            {isSaving ? 'Saving...' : 'Save Changes'}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Toast Notification */}
                    {toast && (
                        <div className={`fixed top-4 right-4 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden transform transition-all duration-300 ease-in-out ${
                            toast.type === 'success' ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500'
                        }`}>
                            <div className="p-4">
                                <div className="flex items-start">
                                    <div className="flex-shrink-0">
                                        {toast.type === 'success' ? (
                                            <svg className="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        ) : (
                                            <svg className="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        )}
                                    </div>
                                    <div className="ml-3 w-0 flex-1 pt-0.5">
                                        <p className={`text-sm font-medium ${
                                            toast.type === 'success' ? 'text-green-900' : 'text-red-900'
                                        }`}>
                                            {toast.type === 'success' ? 'Success' : 'Error'}
                                        </p>
                                        <p className={`mt-1 text-sm ${
                                            toast.type === 'success' ? 'text-green-700' : 'text-red-700'
                                        }`}>
                                            {toast.message}
                                        </p>
                                    </div>
                                    <div className="ml-4 flex-shrink-0 flex">
                                        <button
                                            className="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                                            onClick={() => setToast(null)}
                                        >
                                            <span className="sr-only">Dismiss</span>
                                            <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                            </svg>
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
