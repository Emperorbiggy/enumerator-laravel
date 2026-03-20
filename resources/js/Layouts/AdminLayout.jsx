import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { ToastProvider } from '@/Components/ToastContainer';

export default function AdminLayout({ children, title }) {
    const { url } = usePage();
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const navigation = [
        { name: 'Dashboard', href: '/admin/dashboard', icon: 'dashboard' },
        { name: 'Enumerators', href: '/admin/enumerators', icon: 'users' },
        { name: 'Stats', href: '/admin/enumerator-performance', icon: 'chart-bar' },
        { name: 'Data Sub', href: '/admin/data-sub', icon: 'database' },
    ];

    const icons = {
        dashboard: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
        ),
        users: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        ),
        'chart-bar': (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        ),
        database: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
            </svg>
        ),
    };

    const isActive = (href) => {
        return url.startsWith(href);
    };

    return (
        <ToastProvider>
            <div className="min-h-screen bg-gray-50 flex">
                {/* Mobile sidebar backdrop */}
                {sidebarOpen && (
                    <div 
                        className="fixed inset-0 z-40 lg:hidden bg-gray-600 bg-opacity-75"
                        onClick={() => setSidebarOpen(false)}
                    ></div>
                )}

                {/* Sidebar */}
                <div className={`
                    fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-yellow-600 to-yellow-700 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0
                    ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}
                `}>
                    <div className="flex items-center justify-center h-16 px-4 bg-yellow-800">
                        <h1 className="text-xl font-bold text-white">Admin Panel</h1>
                    </div>
                    
                    <nav className="mt-8">
                        <div className="px-4 space-y-2">
                            {navigation.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`
                                        group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-all duration-200
                                        ${isActive(item.href) 
                                            ? 'bg-yellow-900 text-white shadow-lg' 
                                            : 'text-yellow-100 hover:bg-yellow-700 hover:text-white'
                                        }
                                    `}
                                >
                                    <div className="flex-shrink-0 mr-3">
                                        {icons[item.icon]}
                                    </div>
                                    {item.name}
                                </Link>
                            ))}
                        </div>
                    </nav>

                    {/* User info at bottom */}
                    <div className="absolute bottom-0 w-full p-4">
                        <div className="bg-yellow-800 rounded-lg p-3 text-center">
                            <p className="text-yellow-100 text-sm">Admin User</p>
                            <p className="text-yellow-200 text-xs">System Administrator</p>
                        </div>
                    </div>
                </div>

                {/* Main content */}
                <div className="flex-1 flex flex-col overflow-hidden">
                    {/* Top header */}
                    <header className="bg-white shadow-sm border-b border-gray-200">
                        <div className="px-4 sm:px-6 lg:px-8">
                            <div className="flex items-center justify-between h-16">
                                {/* Mobile menu button */}
                                <button
                                    onClick={() => setSidebarOpen(!sidebarOpen)}
                                    className="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-yellow-500"
                                >
                                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>

                                {/* Header content */}
                                <div className="flex-1 flex items-center justify-between">
                                    <div className="flex-1">
                                        <h1 className="text-lg font-semibold text-gray-900">{title}</h1>
                                    </div>
                                    
                                    {/* Actions */}
                                    <div className="ml-4 flex items-center space-x-4">
                                        <Link
                                            href="/"
                                            className="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
                                        >
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                            <span className="hidden sm:inline ml-2">Go to Site</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>

                    {/* Main content area */}
                    <main className="flex-1 overflow-y-auto">
                        {children}
                    </main>
                </div>
            </div>
        </ToastProvider>
    );
}
