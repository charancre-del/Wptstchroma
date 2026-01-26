import React, { useState, useEffect } from 'react';
import useUIStore from '@stores/useUIStore';
import { WifiOff, Wifi, RefreshCw } from 'lucide-react';

const OfflineBanner = () => {
    const [isOnline, setIsOnline] = useState(navigator.onLine);

    useEffect(() => {
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    if (isOnline) return null;

    return (
        <div className="bg-yellow-500 text-white px-4 py-2 text-sm font-medium flex justify-between items-center shadow-md relative z-50">
            <div className="flex items-center gap-2">
                <WifiOff size={16} />
                <span>You are currently offline. Changes are saved locally.</span>
            </div>
        </div>
    );
};

export const SyncPrompt = ({ hasPendingChanges, onSync }) => {
    if (!hasPendingChanges) return null;

    return (
        <div className="bg-blue-600 text-white px-4 py-2 text-sm font-medium flex justify-between items-center shadow-md animate-slide-down relative z-50">
            <div className="flex items-center gap-2">
                <Wifi size={16} />
                <span>Back Online. You have unsaved local changes.</span>
            </div>
            <button
                onClick={onSync}
                className="bg-white text-blue-700 px-3 py-1 rounded text-xs font-bold uppercase tracking-wide hover:bg-blue-50 transition-colors flex items-center gap-1"
            >
                <RefreshCw size={12} /> Sync Now
            </button>
        </div>
    );
};

export default OfflineBanner;
