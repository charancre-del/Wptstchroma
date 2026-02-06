import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { motion } from 'framer-motion';

const LoginScreen = () => {
    const { login } = useAuth();
    const [pin, setPin] = useState('');
    const [error, setError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleNumClick = (num) => {
        if (pin.length < 6) {
            setPin(prev => prev + num);
            setError('');
        }
    };

    const handleClear = () => {
        setPin('');
        setError('');
    };

    const handleBackspace = () => {
        setPin(prev => prev.slice(0, -1));
    };

    const handleSubmit = async () => {
        if (pin.length < 4) {
            setError('PIN too short');
            return;
        }
        setIsSubmitting(true);
        const result = await login(pin);
        setIsSubmitting(false);
        if (!result.success) {
            setError(result.message);
            setPin('');
        }
    };

    console.log("Rendering LoginScreen...");

    return (
        <div className="chroma-login-screen" style={{
            width: '100vw',
            height: '100vh',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            background: 'linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%)',
            position: 'fixed',
            top: 0,
            left: 0,
            zIndex: 999999,
            opacity: 1,
            visibility: 'visible',
            pointerEvents: 'auto'
        }}>
            <div
                className="glass-card login-card"
                style={{
                    opacity: 1,
                    transform: 'scale(1)',
                    background: 'white',
                    padding: '60px',
                    borderRadius: '30px',
                    boxShadow: '0 25px 50px rgba(0,0,0,0.25)',
                    textAlign: 'center',
                    maxWidth: '480px',
                    width: '90%'
                }}
            >
                <div className="brand" style={{ marginBottom: '30px' }}>
                    <h2 style={{ fontSize: '2.5rem', marginBottom: '10px', color: '#1e3a5f' }}>Parent Portal</h2>
                    <p style={{ color: '#64748b', fontWeight: '500' }}>Enter your security PIN to access resources</p>
                </div>

                {error && <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} style={{ color: '#ef4444', marginBottom: '20px', fontWeight: '600' }}>{error}</motion.div>}

                <form onSubmit={(e) => { e.preventDefault(); handleSubmit(); }}>
                    <div className="input-group" style={{ marginBottom: '30px' }}>
                        <input
                            type="password"
                            inputMode="numeric"
                            pattern="[0-9]*"
                            autoComplete="current-password"
                            placeholder="Enter PIN"
                            value={pin}
                            onChange={(e) => {
                                const val = e.target.value.replace(/[^0-9]/g, '');
                                setPin(val);
                                setError('');
                            }}
                            autoFocus
                            style={{
                                width: '100%',
                                padding: '15px 20px',
                                fontSize: '24px',
                                textAlign: 'center',
                                border: '2px solid #e2e8f0',
                                borderRadius: '15px',
                                background: '#f8fafc',
                                color: '#1e293b',
                                letterSpacing: '0.5em',
                                outline: 'none'
                            }}
                        />
                    </div>

                    <button
                        type="submit"
                        className="portal-btn"
                        style={{
                            width: '100%',
                            padding: '16px',
                            fontSize: '1.1rem',
                            fontWeight: '600',
                            color: 'white',
                            border: 'none',
                            borderRadius: '15px',
                            cursor: 'pointer',
                            background: 'linear-gradient(135deg, #6366f1, #0ea5e9)',
                            opacity: isSubmitting ? 0.7 : 1
                        }}
                        disabled={isSubmitting}
                    >
                        {isSubmitting ? 'Verifying...' : 'Access Portal'}
                    </button>
                </form>
            </div>
        </div>
    );
};

export default LoginScreen;
