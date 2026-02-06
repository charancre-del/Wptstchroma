import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { motion, AnimatePresence } from 'framer-motion';

const LoginScreen = () => {
    const { login } = useAuth();
    const [pin, setPin] = useState('');
    const [error, setError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Auto-submit when PIN reaches 6 digits
    useEffect(() => {
        if (pin.length === 6) {
            handleAutoSubmit();
        }
    }, [pin]);

    const handleAutoSubmit = async () => {
        setIsSubmitting(true);
        const result = await login(pin);
        setIsSubmitting(false);
        if (!result.success) {
            setError(result.message || 'Invalid PIN');
            setPin('');
        }
    };

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
        setError('');
    };

    const handleSubmit = async (e) => {
        if (e) e.preventDefault();
        if (pin.length < 4) {
            setError('PIN must be at least 4 digits');
            return;
        }
        setIsSubmitting(true);
        const result = await login(pin);
        setIsSubmitting(false);
        if (!result.success) {
            setError(result.message || 'Invalid PIN');
            setPin('');
        }
    };

    return (
        <div className="chroma-login-screen" style={{
            width: '100vw',
            height: '100vh',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
            position: 'fixed',
            top: 0,
            left: 0,
            zIndex: 999999,
            overflowY: 'auto',
            padding: '20px'
        }}>
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="glass-card login-card"
                style={{
                    background: 'rgba(255, 255, 255, 0.95)',
                    backdropFilter: 'blur(10px)',
                    padding: '40px 30px',
                    borderRadius: '40px',
                    boxShadow: '0 40px 100px rgba(0,0,0,0.4)',
                    textAlign: 'center',
                    maxWidth: '400px',
                    width: '100%',
                    border: '1px solid rgba(255,255,255,0.2)'
                }}
            >
                <div className="brand" style={{ marginBottom: '30px' }}>
                    <div style={{ fontSize: '3rem', marginBottom: '10px' }}>🔐</div>
                    <h2 style={{ fontSize: '2rem', fontWeight: '800', color: '#1e293b', marginBottom: '8px' }}>Parent Portal</h2>
                    <p style={{ color: '#64748b', fontSize: '0.95rem' }}>Enter your security PIN</p>
                </div>

                <AnimatePresence>
                    {error && (
                        <motion.div
                            initial={{ opacity: 0, height: 0 }}
                            animate={{ opacity: 1, height: 'auto' }}
                            exit={{ opacity: 0, height: 0 }}
                            style={{ color: '#ef4444', marginBottom: '20px', fontWeight: '600', fontSize: '0.9rem' }}
                        >
                            {error}
                        </motion.div>
                    )}
                </AnimatePresence>

                <div className="pin-display" style={{ marginBottom: '30px', display: 'flex', justifyContent: 'center', gap: '10px' }}>
                    {[...Array(6)].map((_, i) => (
                        <div key={i} style={{
                            width: '45px',
                            height: '55px',
                            borderBottom: `3px solid ${pin.length > i ? '#6366f1' : '#e2e8f0'}`,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            fontSize: '24px',
                            fontWeight: '700',
                            color: '#1e293b',
                            transition: 'all 0.2s'
                        }}>
                            {pin.length > i ? '•' : ''}
                        </div>
                    ))}
                </div>

                <div className="keypad" style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(3, 1fr)',
                    gap: '15px',
                    marginBottom: '30px'
                }}>
                    {[1, 2, 3, 4, 5, 6, 7, 8, 9].map(num => (
                        <button
                            key={num}
                            onClick={() => handleNumClick(num.toString())}
                            style={{
                                padding: '20px',
                                fontSize: '1.5rem',
                                fontWeight: '600',
                                background: '#f8fafc',
                                border: '1px solid #f1f5f9',
                                borderRadius: '20px',
                                cursor: 'pointer',
                                color: '#1e293b',
                                transition: 'all 0.1s'
                            }}
                            onMouseDown={(e) => e.target.style.transform = 'scale(0.95)'}
                            onMouseUp={(e) => e.target.style.transform = 'scale(1)'}
                        >
                            {num}
                        </button>
                    ))}
                    <button onClick={handleClear} style={{ background: '#fee2e2', color: '#ef4444', borderRadius: '20px', border: 'none', fontWeight: '600' }}>C</button>
                    <button onClick={() => handleNumClick('0')} style={{ padding: '20px', fontSize: '1.5rem', fontWeight: '600', background: '#f8fafc', borderRadius: '20px', border: '1px solid #f1f5f9' }}>0</button>
                    <button onClick={handleBackspace} style={{ background: '#f1f5f9', color: '#64748b', borderRadius: '20px', border: 'none' }}>⌫</button>
                </div>

                <button
                    onClick={handleSubmit}
                    className="portal-btn"
                    style={{
                        width: '100%',
                        padding: '18px',
                        fontSize: '1rem',
                        fontWeight: '700',
                        color: 'white',
                        border: 'none',
                        borderRadius: '20px',
                        cursor: 'pointer',
                        background: 'linear-gradient(135deg, #6366f1, #4f46e5)',
                        boxShadow: '0 10px 20px -5px rgba(99, 102, 241, 0.4)',
                        opacity: isSubmitting ? 0.7 : 1,
                        transition: 'all 0.2s'
                    }}
                    disabled={isSubmitting}
                >
                    {isSubmitting ? 'Verifying...' : 'Access Portal'}
                </button>

                <div style={{ marginTop: '20px' }}>
                    <input
                        type="password"
                        value={pin}
                        onChange={(e) => {
                            const val = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
                            setPin(val);
                            setError('');
                        }}
                        autoFocus
                        style={{ opacity: 0, position: 'absolute', pointerEvents: 'none' }}
                    />
                </div>
            </motion.div>
        </div>
    );
};

export default LoginScreen;
