import React, { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext();

export const useAuth = () => useContext(AuthContext);

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null); // { name: 'Smith Family', token: '...' } or { isAdmin: true }
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Check local storage or existing WP session
        const storedToken = localStorage.getItem('chroma_portal_token');
        const storedFamily = localStorage.getItem('chroma_portal_family');

        // Simple check if user is already WP Admin (provided by wp_localize_script)
        // We might need a separate endpoint to "check session" properly, but for now:
        if (storedToken && storedFamily) {
            setUser({ token: storedToken, name: storedFamily, isAdmin: false });
        }

        // Check Backend validation in parallel?
        // For MVP, if we have a token, we assume logged in until a 403 API response logs us out.

        setLoading(false);
    }, []);

    const login = async (pin) => {
        try {
            const settings = window.chromaPortalSettings;
            const res = await fetch(`${settings.root}chroma-portal/v1/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': settings.nonce
                },
                body: JSON.stringify({ pin })
            });

            const data = await res.json();

            if (data.success) {
                const userData = { token: data.token, name: data.family, isAdmin: false };
                setUser(userData);
                localStorage.setItem('chroma_portal_token', data.token);
                localStorage.setItem('chroma_portal_family', data.family);
                return { success: true };
            } else {
                return { success: false, message: data.message || 'Invalid PIN' };
            }
        } catch (e) {
            return { success: false, message: 'Connection Error' };
        }
    };

    const logout = () => {
        setUser(null);
        localStorage.removeItem('chroma_portal_token');
        localStorage.removeItem('chroma_portal_family');
    };

    // Admin Override (If backend says isAdmin later)
    const setAdmin = () => {
        setUser({ isAdmin: true, name: 'Director' });
    }

    return (
        <AuthContext.Provider value={{ user, loading, login, logout, setAdmin }}>
            {children}
        </AuthContext.Provider>
    );
};
