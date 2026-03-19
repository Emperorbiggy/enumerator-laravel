import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';

export default function Register() {
    const [formData, setFormData] = useState({
        full_name: '',
        email: '',
        whatsapp: '',
        state: 'Osun',
        lga: '',
        ward: '',
        polling_unit: '',
        browsing_network: '',
        browsing_number: '',
        bank_name: '',
        account_name: '',
        account_number: '',
        group_name: '',
        coordinator_phone: '',
    });

    const [lgas, setLgas] = useState([]);
    const [wards, setWards] = useState([]);
    const [pollingUnits, setPollingUnits] = useState([]);
    const [banks, setBanks] = useState([]);
    const [totalCount, setTotalCount] = useState(0);
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [showSuccess, setShowSuccess] = useState(false);
    const [successData, setSuccessData] = useState(null);
    const [errors, setErrors] = useState({});
    const [accountVerification, setAccountVerification] = useState(null);
    const [verifyingAccount, setVerifyingAccount] = useState(false);
    const [showAccountConfirmation, setShowAccountConfirmation] = useState(false);


    const networks = ['MTN', 'Glo', 'Airtel', '9mobile'];

    // Fetch initial data
    useEffect(() => {
        fetchLGAs();
        fetchTotalCount();
        fetchBanks();
    }, []);

    // Fetch LGAs
    const fetchLGAs = async () => {
        try {
            const response = await fetch('/api/enumerator/lgas');
            const data = await response.json();
            if (data.success) {
                setLgas(data.data);
            }
        } catch (error) {
            console.error('Error fetching LGAs:', error);
        }
    };

    // Fetch total count
    const fetchTotalCount = async () => {
        try {
            const response = await fetch('/api/enumerator/count');
            const data = await response.json();
            if (data.success) {
                setTotalCount(data.data.total);
            }
        } catch (error) {
            console.error('Error fetching count:', error);
        }
    };

    // Fetch banks from Paystack
    const fetchBanks = async () => {
        try {
            const response = await fetch('/api/paystack/banks?country=nigeria&perPage=100');
            const data = await response.json();
            if (data.status) {
                setBanks(data.data);
            }
        } catch (error) {
            console.error('Error fetching banks:', error);
        }
    };

    // Handle LGA change
    const handleLGAChange = async (e) => {
        const lga = e.target.value;
        setFormData({ ...formData, lga, ward: '', polling_unit: '' });
        setWards([]);
        setPollingUnits([]);

        if (lga) {
            try {
                const response = await fetch(`/api/enumerator/wards?lga=${encodeURIComponent(lga)}`);
                const data = await response.json();
                if (data.success) {
                    setWards(data.data);
                }
            } catch (error) {
                console.error('Error fetching wards:', error);
            }
        }
    };

    // Handle Ward change
    const handleWardChange = async (e) => {
        const ward = e.target.value;
        setFormData({ ...formData, ward, polling_unit: '' });
        setPollingUnits([]);

        if (ward) {
            try {
                const response = await fetch(`/api/enumerator/polling-units?ward=${encodeURIComponent(ward)}`);
                const data = await response.json();
                if (data.success) {
                    setPollingUnits(data.data);
                }
            } catch (error) {
                console.error('Error fetching polling units:', error);
            }
        }
    };

    // Handle form input changes
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData({ ...formData, [name]: value });
        
        // Clear error for this field
        if (errors[name]) {
            setErrors({ ...errors, [name]: '' });
        }

        // Handle bank change and account verification
        if (name === 'bank_name') {
            setAccountVerification(null);
            setShowAccountConfirmation(false);
            // Auto-verify if account number is already entered
            if (formData.account_number && formData.account_number.length === 10) {
                verifyAccount(formData.account_number, value);
            }
        }

        // Auto-verify account when 10 digits are entered and bank is selected
        if (name === 'account_number' && value.length === 10 && formData.bank_name) {
            verifyAccount(value, formData.bank_name);
        }
    };

    // Validate form
    const validateForm = () => {
        const newErrors = {};

        if (!formData.full_name.trim()) newErrors.full_name = 'Full name is required';
        if (!formData.email.trim()) {
            newErrors.email = 'Email is required';
        } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
            newErrors.email = 'Email is invalid';
        }
        if (!formData.whatsapp.trim()) newErrors.whatsapp = 'WhatsApp number is required';
        if (!formData.lga) newErrors.lga = 'LGA is required';
        if (!formData.ward) newErrors.ward = 'Ward is required';
        if (!formData.polling_unit) newErrors.polling_unit = 'Polling unit is required';
        if (!formData.browsing_network) newErrors.browsing_network = 'Network is required';
        if (!formData.browsing_number.trim()) newErrors.browsing_number = 'Browsing number is required';
        if (!formData.bank_name) newErrors.bank_name = 'Bank is required';
        if (!formData.account_name.trim()) newErrors.account_name = 'Account name is required';
        if (!formData.account_number.trim()) {
            newErrors.account_number = 'Account number is required';
        } else if (!/^\d{10}$/.test(formData.account_number)) {
            newErrors.account_number = 'Account number must be 10 digits';
        }
        if (!formData.group_name.trim()) newErrors.group_name = 'Group name is required';
        if (!formData.coordinator_phone.trim()) newErrors.coordinator_phone = 'Coordinator phone is required';

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    // Verify account number
    const verifyAccount = async (accountNumber, bankName) => {
        if (!accountNumber || !bankName || accountNumber.length !== 10) {
            return;
        }

        setVerifyingAccount(true);
        try {
            const selectedBank = banks.find(bank => bank.name === bankName);
            if (!selectedBank) {
                setErrors({ account_name: 'Invalid bank selected' });
                return;
            }

            const response = await fetch(`/api/paystack/resolve-account?account_number=${accountNumber}&bank_code=${selectedBank.code}`);
            const data = await response.json();

            if (data.status) {
                setAccountVerification(data.data);
                setFormData(prev => ({ ...prev, account_name: data.data.account_name }));
                setShowAccountConfirmation(true);
                setErrors(prev => ({ ...prev, account_name: '' }));
            } else {
                setErrors({ account_name: data.message || 'Account verification failed' });
                setAccountVerification(null);
            }
        } catch (error) {
            setErrors({ account_name: 'Error verifying account. Please try again.' });
            setAccountVerification(null);
        } finally {
            setVerifyingAccount(false);
        }
    };

    // Handle manual account verification
    const handleVerifyAccount = () => {
        if (formData.account_number && formData.bank_name) {
            verifyAccount(formData.account_number, formData.bank_name);
        }
    };

    // Confirm account details
    const confirmAccountDetails = () => {
        setShowAccountConfirmation(false);
    };

    // Handle form submission
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        // Check if account is verified
        if (!accountVerification) {
            setErrors({ account_name: 'Please verify your account number before submitting' });
            return;
        }

        setSubmitting(true);

        try {
            const response = await fetch('/api/enumerator/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                setSuccessData(data.data);
                setShowSuccess(true);
                setTotalCount(prev => prev + 1);
                
                // Reset form
                setFormData({
                    full_name: '',
                    email: '',
                    whatsapp: '',
                    state: 'Osun',
                    lga: '',
                    ward: '',
                    polling_unit: '',
                    browsing_network: '',
                    browsing_number: '',
                    bank_name: '',
                    account_name: '',
                    account_number: '',
                    group_name: '',
                    coordinator_phone: '',
                });
                setWards([]);
                setPollingUnits([]);
                setErrors({});
                setAccountVerification(null);
            } else {
                setErrors(data.errors || { general: data.message || 'Registration failed' });
            }
        } catch (error) {
            console.error('Registration error:', error);
            setErrors({ general: 'Registration failed. Please try again.' });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <>
            <Head title="Enumerator Registration - Accord Party" />
            
            <div className="min-h-screen bg-gradient-to-br from-yellow-300 to-yellow-400">
                <div className="container mx-auto px-4 py-8 max-w-6xl">
                    {/* Header */}
                    <div className="bg-white rounded-t-2xl p-8 text-center border-b-4 border-yellow-400">
                        <h1 className="text-4xl font-bold text-yellow-800 mb-4">
                            <i className="fas fa-sun text-yellow-400 mr-3"></i>
                            Enumeration Registration
                        </h1>
                        <p className="text-gray-600 text-lg">Complete the form below to receive your unique enumerator code</p>
                    </div>

                    {/* Stats Bar */}
                    <div className="bg-gray-50 p-6 flex justify-between items-center border-b">
                        <div className="text-yellow-800 font-semibold">
                            <i className="fas fa-users mr-2"></i>
                            Registered Enumerators: 
                            <span className="bg-yellow-400 text-yellow-800 px-4 py-2 rounded-full ml-3">
                                {totalCount}
                            </span>
                        </div>
                    </div>

                    {/* Form Container */}
                    <div className="bg-white rounded-b-2xl p-8 shadow-2xl">
                        {showSuccess && successData && (
                            <div className="mb-8 p-6 bg-green-50 border-2 border-green-200 rounded-xl text-center">
                                <div className="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i className="fas fa-check text-white text-3xl"></i>
                                </div>
                                <h2 className="text-2xl font-bold text-green-800 mb-2">Registration Successful!</h2>
                                <p className="text-green-600 mb-4">Congratulations Enumerator!</p>
                                <div className="bg-gradient-to-r from-yellow-400 to-yellow-500 text-yellow-900 p-6 rounded-xl mb-4">
                                    <div className="text-sm uppercase tracking-wider mb-2">Your Enumerator Code</div>
                                    <div className="text-5xl font-bold tracking-wider">{successData.code}</div>
                                </div>
                                <div className="bg-yellow-50 p-4 rounded-lg text-left border-l-4 border-yellow-400">
                                    <p className="text-yellow-800">
                                        <strong>Check your email!</strong> Your enumerator code has been sent to:
                                        <br />
                                        <span className="bg-yellow-100 px-3 py-1 rounded block mt-2">{successData.email}</span>
                                    </p>
                                </div>
                                <a
                                    href="https://chat.whatsapp.com/F2f6AeKVSDS2zRWhXcdAKH?mode=gi_t"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-4 block w-full bg-green-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition-all duration-300 flex items-center justify-center gap-2 animate-pulse hover:animate-none hover:scale-105 transform"
                                >
                                    <i className="fab fa-whatsapp text-xl"></i>
                                    Join WhatsApp Group
                                </a>
                                <button
                                    onClick={() => setShowSuccess(false)}
                                    className="mt-4 text-yellow-600 hover:text-yellow-700 underline font-medium transition-colors"
                                >
                                    Register Another
                                </button>
                            </div>
                        )}

                        {!showSuccess && (
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {errors.general && (
                                    <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                                        {errors.general}
                                    </div>
                                )}

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Full Name */}
                                    <div className="md:col-span-2">
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-user text-yellow-400 mr-2"></i>
                                            Full Name <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            name="full_name"
                                            value={formData.full_name}
                                            onChange={handleInputChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.full_name ? 'border-red-500' : 'border-gray-300'}`}
                                            placeholder="Enter your full name"
                                        />
                                        {errors.full_name && <p className="text-red-500 text-sm mt-1">{errors.full_name}</p>}
                                    </div>

                                    {/* Email */}
                                    <div className="md:col-span-2">
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-envelope text-yellow-400 mr-2"></i>
                                            Email Address <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            name="email"
                                            value={formData.email}
                                            onChange={handleInputChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.email ? 'border-red-500' : 'border-gray-300'}`}
                                            placeholder="Enter your email address"
                                        />
                                        {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email}</p>}
                                        <p className="text-gray-500 text-sm mt-1">Your enumerator code will be sent to this email</p>
                                    </div>

                                    {/* WhatsApp */}
                                    <div>
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fab fa-whatsapp text-yellow-400 mr-2"></i>
                                            WhatsApp Number <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="tel"
                                            name="whatsapp"
                                            value={formData.whatsapp}
                                            onChange={handleInputChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.whatsapp ? 'border-red-500' : 'border-gray-300'}`}
                                            placeholder="e.g., 08012345678"
                                        />
                                        {errors.whatsapp && <p className="text-red-500 text-sm mt-1">{errors.whatsapp}</p>}
                                    </div>

                                    {/* State */}
                                    <div>
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-map-marker-alt text-yellow-400 mr-2"></i>
                                            State <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.state}
                                            readOnly
                                            className="w-full px-4 py-3 border-2 rounded-lg bg-gray-100 cursor-not-allowed"
                                        />
                                    </div>

                                    {/* LGA */}
                                    <div className="md:col-span-2">
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-city text-yellow-400 mr-2"></i>
                                            Local Government <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            name="lga"
                                            value={formData.lga}
                                            onChange={handleLGAChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.lga ? 'border-red-500' : 'border-gray-300'}`}
                                        >
                                            <option value="">Select Local Government</option>
                                            {lgas.map((lga) => (
                                                <option key={lga.id} value={lga.name}>
                                                    {lga.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.lga && <p className="text-red-500 text-sm mt-1">{errors.lga}</p>}
                                    </div>

                                    {/* Ward */}
                                    <div className="md:col-span-2">
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-map-pin text-yellow-400 mr-2"></i>
                                            Ward <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            name="ward"
                                            value={formData.ward}
                                            onChange={handleWardChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.ward ? 'border-red-500' : 'border-gray-300'}`}
                                            disabled={!formData.lga}
                                        >
                                            <option value="">Select LGA first</option>
                                            {wards.map((ward) => (
                                                <option key={ward.id} value={ward.name}>
                                                    {ward.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.ward && <p className="text-red-500 text-sm mt-1">{errors.ward}</p>}
                                    </div>

                                    {/* Polling Unit */}
                                    <div className="md:col-span-2 bg-yellow-50 p-6 rounded-xl border-2 border-yellow-400">
                                        <label className="block text-yellow-800 font-semibold mb-2 text-lg">
                                            <i className="fas fa-school text-yellow-400 mr-2"></i>
                                            Polling Unit <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            name="polling_unit"
                                            value={formData.polling_unit}
                                            onChange={handleInputChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 bg-white ${errors.polling_unit ? 'border-red-500' : 'border-gray-300'}`}
                                            disabled={!formData.ward}
                                        >
                                            <option value="">Select Ward first</option>
                                            {pollingUnits.map((pu) => (
                                                <option key={pu.id} value={pu.name}>
                                                    {pu.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.polling_unit && <p className="text-red-500 text-sm mt-1">{errors.polling_unit}</p>}
                                        <p className="text-yellow-700 text-sm mt-2">Select your assigned polling unit</p>
                                    </div>

                                    {/* Browsing Network */}
                                    <div>
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-wifi text-yellow-400 mr-2"></i>
                                            Browsing Network <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            name="browsing_network"
                                            value={formData.browsing_network}
                                            onChange={handleInputChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.browsing_network ? 'border-red-500' : 'border-gray-300'}`}
                                        >
                                            <option value="">Select Network</option>
                                            {networks.map((network) => (
                                                <option key={network} value={network}>
                                                    {network}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.browsing_network && <p className="text-red-500 text-sm mt-1">{errors.browsing_network}</p>}
                                    </div>

                                    {/* Browsing Number */}
                                    <div>
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-phone text-yellow-400 mr-2"></i>
                                            Browsing Number <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="tel"
                                            name="browsing_number"
                                            value={formData.browsing_number}
                                            onChange={handleInputChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.browsing_number ? 'border-red-500' : 'border-gray-300'}`}
                                            placeholder="Enter browsing number"
                                        />
                                        {errors.browsing_number && <p className="text-red-500 text-sm mt-1">{errors.browsing_number}</p>}
                                    </div>

                                    {/* Bank Details */}
                                    {/* Bank */}
                                    <div className="md:col-span-2">
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-university text-yellow-400 mr-2"></i>
                                            Select Your Bank <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            name="bank_name"
                                            value={formData.bank_name}
                                            onChange={handleInputChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.bank_name ? 'border-red-500' : 'border-gray-300'}`}
                                        >
                                            <option value="">-- Select your bank --</option>
                                            {banks.map((bank, index) => (
                                                <option key={`bank-${index}`} value={bank.name}>
                                                    {bank.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.bank_name && <p className="text-red-500 text-sm mt-1">{errors.bank_name}</p>}
                                    </div>

                                    {/* Account Number */}
                                    <div>
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-credit-card text-yellow-400 mr-2"></i>
                                            Account Number <span className="text-red-500">*</span>
                                        </label>
                                        <div className="flex gap-2">
                                            <input
                                                type="text"
                                                name="account_number"
                                                value={formData.account_number}
                                                onChange={handleInputChange}
                                                maxLength={10}
                                                className={`flex-1 px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.account_number ? 'border-red-500' : 'border-gray-300'}`}
                                                placeholder="10-digit account number"
                                            />
                                            <button
                                                type="button"
                                                onClick={handleVerifyAccount}
                                                disabled={!formData.account_number || !formData.bank_name || formData.account_number.length !== 10 || verifyingAccount}
                                                className="px-4 py-3 bg-yellow-400 text-yellow-800 rounded-lg font-semibold hover:bg-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                                            >
                                                {verifyingAccount ? (
                                                    <>
                                                        <div className="w-4 h-4 border-2 border-yellow-800 border-t-transparent rounded-full animate-spin inline-block mr-2"></div>
                                                        Verifying...
                                                    </>
                                                ) : (
                                                    'Verify'
                                                )}
                                            </button>
                                        </div>
                                        {errors.account_number && <p className="text-red-500 text-sm mt-1">{errors.account_number}</p>}
                                        {accountVerification && (
                                            <p className="text-green-600 text-sm mt-1">
                                                <i className="fas fa-check-circle mr-1"></i>
                                                Verified: {accountVerification.account_name}
                                            </p>
                                        )}
                                    </div>

                                    {/* Account Name */}
                                    <div>
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-signature text-yellow-400 mr-2"></i>
                                            Bank Account Name <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            name="account_name"
                                            value={formData.account_name}
                                            onChange={handleInputChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.account_name ? 'border-red-500' : 'border-gray-300'} ${accountVerification ? 'bg-green-50' : 'bg-gray-100 cursor-not-allowed'}`}
                                            placeholder="Account name will be auto-filled"
                                            readOnly
                                            disabled={!accountVerification}
                                        />
                                        {errors.account_name && <p className="text-red-500 text-sm mt-1">{errors.account_name}</p>}
                                        {accountVerification && (
                                            <p className="text-green-600 text-sm mt-1">
                                                <i className="fas fa-check-circle mr-1"></i>
                                                Account verified successfully
                                            </p>
                                        )}
                                    </div>

                                    {/* Group Name */}
                                    <div>
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-users text-yellow-400 mr-2"></i>
                                            Name of Your Group <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            name="group_name"
                                            value={formData.group_name}
                                            onChange={handleInputChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.group_name ? 'border-red-500' : 'border-gray-300'}`}
                                            placeholder="Enter group name"
                                        />
                                        {errors.group_name && <p className="text-red-500 text-sm mt-1">{errors.group_name}</p>}
                                    </div>

                                    {/* Coordinator Phone */}
                                    <div>
                                        <label className="block text-gray-700 font-semibold mb-2">
                                            <i className="fas fa-phone-alt text-yellow-400 mr-2"></i>
                                            Group Coordinator's Phone <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="tel"
                                            name="coordinator_phone"
                                            value={formData.coordinator_phone}
                                            onChange={handleInputChange}
                                            className={`w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:border-yellow-400 ${errors.coordinator_phone ? 'border-red-500' : 'border-gray-300'}`}
                                            placeholder="e.g., 08012345678"
                                        />
                                        {errors.coordinator_phone && <p className="text-red-500 text-sm mt-1">{errors.coordinator_phone}</p>}
                                    </div>

                                    {/* Submit Button */}
                                    <div className="md:col-span-2">
                                        <button
                                            type="submit"
                                            disabled={submitting}
                                            className="w-full bg-gradient-to-r from-yellow-400 to-yellow-500 text-yellow-900 py-4 rounded-lg font-bold text-lg hover:from-yellow-500 hover:to-yellow-600 transition-all disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-3"
                                        >
                                            <i className="fas fa-hand-peace"></i>
                                            {submitting ? (
                                                <>
                                                    <div className="w-5 h-5 border-3 border-yellow-900 border-t-transparent rounded-full animate-spin"></div>
                                                    Processing...
                                                </>
                                            ) : (
                                                'Register & Get Enumerator Code'
                                            )}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            </div>

            {/* Account Confirmation Modal */}
            {showAccountConfirmation && accountVerification && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl p-8 max-w-md w-full">
                        <div className="text-center">
                            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i className="fas fa-check text-green-600 text-2xl"></i>
                            </div>
                            <h3 className="text-xl font-bold text-gray-800 mb-4">Account Verified!</h3>
                            <div className="bg-gray-50 p-4 rounded-lg mb-6 text-left">
                                <p className="text-sm text-gray-600 mb-2">Account Details:</p>
                                <p className="font-semibold text-gray-800">{accountVerification.account_name}</p>
                                <p className="text-sm text-gray-600">Account: {accountVerification.account_number}</p>
                            </div>
                            <p className="text-gray-600 mb-6">Please confirm that these details are correct before proceeding with your registration.</p>
                            <div className="flex gap-3">
                                <button
                                    onClick={() => {
                                        setShowAccountConfirmation(false);
                                        setAccountVerification(null);
                                        setFormData(prev => ({ ...prev, account_name: '', account_number: '' }));
                                    }}
                                    className="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                                >
                                    Not Correct
                                </button>
                                <button
                                    onClick={confirmAccountDetails}
                                    className="flex-1 px-4 py-2 bg-yellow-400 text-yellow-800 rounded-lg font-semibold hover:bg-yellow-500"
                                >
                                    Confirm & Continue
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
