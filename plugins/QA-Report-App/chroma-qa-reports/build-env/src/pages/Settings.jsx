import React, { useState, useEffect } from 'react';
import { useSettings, useUpdateSettings } from '@hooks/useQueries';
import { Save, Lock, Bot, Database, Check } from 'lucide-react';

const Settings = () => {
    const { data: settings, isLoading } = useSettings();
    const updateSettingsMutation = useUpdateSettings();
    const [formData, setFormData] = useState({
        google_client_id: '',
        google_client_secret: '',
        gemini_api_key: '',
        enable_ai: 'yes'
    });
    const [isSaved, setIsSaved] = useState(false);

    useEffect(() => {
        if (settings) {
            setFormData({
                google_client_id: settings.google_client_id || '',
                google_client_secret: settings.google_client_secret || '',
                gemini_api_key: settings.gemini_api_key || '',
                enable_ai: settings.enable_ai || 'yes'
            });
        }
    }, [settings]);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? (checked ? 'yes' : 'no') : value
        }));
        setIsSaved(false);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        updateSettingsMutation.mutate(formData, {
            onSuccess: () => {
                setIsSaved(true);
                setTimeout(() => setIsSaved(false), 3000);
            }
        });
    };

    if (isLoading) {
        return <div className="p-8 text-center text-gray-500">Loading settings...</div>;
    }

    return (
        <div className="p-6 max-w-4xl mx-auto">
            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">Plugin Settings</h1>
                <p className="text-gray-500 mt-1">Configure integrations and features.</p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Google Drive Integration */}
                <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                        <div className="p-2 bg-blue-100 text-blue-600 rounded-lg">
                            <Database size={20} />
                        </div>
                        <div>
                            <h2 className="font-semibold text-gray-900">Google Drive Integration</h2>
                            <p className="text-xs text-gray-500">Required for photo storage and report exports</p>
                        </div>
                    </div>
                    <div className="p-6 space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Client ID
                            </label>
                            <input
                                type="text"
                                name="google_client_id"
                                value={formData.google_client_id}
                                onChange={handleChange}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                placeholder="OAuth Client ID"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Client Secret
                            </label>
                            <div className="relative">
                                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={16} />
                                <input
                                    type="password"
                                    name="google_client_secret"
                                    value={formData.google_client_secret}
                                    onChange={handleChange}
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                    placeholder="OAuth Client Secret"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* AI Configuration */}
                <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                        <div className="p-2 bg-purple-100 text-purple-600 rounded-lg">
                            <Bot size={20} />
                        </div>
                        <div>
                            <h2 className="font-semibold text-gray-900">AI Features (Gemini)</h2>
                            <p className="text-xs text-gray-500">Enable advanced reporting features</p>
                        </div>
                    </div>
                    <div className="p-6 space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-medium text-gray-900">Enable AI Summaries</h3>
                                <p className="text-xs text-gray-500">Allow AI to generate findings summaries</p>
                            </div>
                            <label className="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="enable_ai"
                                    checked={formData.enable_ai === 'yes'}
                                    onChange={handleChange}
                                    className="sr-only peer"
                                />
                                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        {formData.enable_ai === 'yes' && (
                            <div className="animate-fade-in">
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Gemini API Key
                                </label>
                                <div className="relative">
                                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={16} />
                                    <input
                                        type="password"
                                        name="gemini_api_key"
                                        value={formData.gemini_api_key}
                                        onChange={handleChange}
                                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all"
                                        placeholder="AI API Key"
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Save Button */}
                <div className="flex items-center justify-end gap-4">
                    {isSaved && (
                        <span className="text-green-600 text-sm font-medium flex items-center gap-2 animate-fade-in">
                            <Check size={16} /> Saved successfully
                        </span>
                    )}
                    <button
                        type="submit"
                        disabled={updateSettingsMutation.isPending}
                        className="flex items-center gap-2 px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {updateSettingsMutation.isPending ? (
                            <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        ) : (
                            <Save size={18} />
                        )}
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    );
};

export default Settings;
