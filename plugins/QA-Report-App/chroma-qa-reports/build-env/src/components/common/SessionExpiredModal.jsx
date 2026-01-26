import React, { useState, useEffect } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import { LogIn, AlertCircle, RefreshCw, X } from 'lucide-react';

/**
 * Session Expired Modal - shown on 401 response
 * 
 * Opens WP login in popup window, then refreshes nonce on success.
 */
export function SessionExpiredModal({
    open,
    onClose,
    onSessionRestored
}) {
    const [isLoggingIn, setIsLoggingIn] = useState(false);
    const [loginWindow, setLoginWindow] = useState(null);

    // Check if login window was closed
    useEffect(() => {
        if (!loginWindow) return;

        const checkClosed = setInterval(() => {
            if (loginWindow.closed) {
                setIsLoggingIn(false);
                setLoginWindow(null);
                clearInterval(checkClosed);

                // Try to verify session was restored
                verifySession();
            }
        }, 500);

        return () => clearInterval(checkClosed);
    }, [loginWindow]);

    const handleLogin = () => {
        setIsLoggingIn(true);

        // Open WP login in popup
        const width = 500;
        const height = 600;
        const left = window.screen.width / 2 - width / 2;
        const top = window.screen.height / 2 - height / 2;

        const popup = window.open(
            `${window.cqaData.adminUrl}?interim-login=1`,
            'wp_login',
            `width=${width},height=${height},left=${left},top=${top},menubar=no,toolbar=no,location=no,status=no`
        );

        setLoginWindow(popup);
    };

    const verifySession = async () => {
        try {
            // Fetch fresh nonce via heartbeat
            const response = await fetch(
                `${window.cqaData.adminUrl}admin-ajax.php?action=heartbeat`,
                { credentials: 'same-origin' }
            );

            if (response.ok) {
                // Session restored, refresh the page to get new nonce
                onSessionRestored?.();
            }
        } catch (error) {
            console.error('Session verification failed:', error);
        }
    };

    const handleRefresh = () => {
        window.location.reload();
    };

    return (
        <Dialog.Root open={open} onOpenChange={() => { }}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 bg-black/50 z-50" />
                <Dialog.Content className="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white rounded-xl shadow-xl p-6 w-full max-w-md z-50">
                    <div className="text-center">
                        <div className="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <AlertCircle className="w-8 h-8 text-amber-600" />
                        </div>

                        <Dialog.Title className="text-xl font-semibold text-gray-900">
                            Session Expired
                        </Dialog.Title>

                        <Dialog.Description className="mt-2 text-gray-600">
                            Your session has expired. Please log in again to continue.
                            <br />
                            <span className="text-sm text-gray-500">
                                Don't worry — your draft has been saved locally.
                            </span>
                        </Dialog.Description>
                    </div>

                    <div className="mt-6 space-y-3">
                        <button
                            onClick={handleLogin}
                            disabled={isLoggingIn}
                            className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50"
                        >
                            {isLoggingIn ? (
                                <>
                                    <RefreshCw className="w-4 h-4 animate-spin" />
                                    Waiting for login...
                                </>
                            ) : (
                                <>
                                    <LogIn className="w-4 h-4" />
                                    Log In
                                </>
                            )}
                        </button>

                        <button
                            onClick={handleRefresh}
                            className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                        >
                            <RefreshCw className="w-4 h-4" />
                            Refresh Page
                        </button>
                    </div>

                    <p className="mt-4 text-xs text-center text-gray-500">
                        A popup window will open for login. Please allow popups for this site.
                    </p>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}

export default SessionExpiredModal;
