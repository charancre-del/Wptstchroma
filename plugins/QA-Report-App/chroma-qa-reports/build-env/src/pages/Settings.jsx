import React from 'react';
import { Settings as SettingsIcon } from 'lucide-react';

export function Settings() {
    return (
        <div className="p-6 max-w-7xl mx-auto">
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
                <p className="text-gray-600">Configure QA Reports system settings</p>
            </div>

            <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <SettingsIcon className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">System Settings</h3>
                <p className="text-gray-500 mb-4">Full settings interface coming in Phase 4</p>
                <p className="text-sm text-gray-400">Google OAuth, AI config, notifications, user roles</p>
            </div>
        </div>
    );
}

export default Settings;
