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
        <div className="login-screen">
            <div
                className="glass-card login-card"
                style={{ opacity: 1, transform: 'scale(1)' }}
            >
                <div className="brand" style={{ marginBottom: '30px' }}>
                    <h2 style={{ fontSize: '2.5rem', marginBottom: '10px' }}>Parent Portal</h2>
                    <p style={{ color: '#64748b', fontWeight: '500' }}>Enter your security PIN to access resources</p>
                </div>

                <div className="pin-display">
                    {[...Array(4)].map((_, i) => (
                        <div key={i} className={`digit ${pin.length > i ? 'active' : ''}`}></div>
                    ))}
                </div>

                {error && <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} style={{ color: '#ef4444', marginBottom: '20px', fontWeight: '600' }}>{error}</motion.div>}

                <div className="pin-grid">
                    {[1, 2, 3, 4, 5, 6, 7, 8, 9].map(num => (
                        <button key={num} onClick={() => handleNumClick(num)}>{num}</button>
                    ))}
                    <button onClick={handleClear} className="clear">C</button>
                    <button onClick={() => handleNumClick(0)}>0</button>
                    <button onClick={handleBackspace}>←</button>
                </div>

                <button
                    onClick={handleSubmit}
                    className="portal-btn"
                    style={{
                        marginTop: '40px',
                        width: '100%',
                        padding: '16px',
                        fontSize: '1.1rem',
                        background: 'linear-gradient(135deg, #6366f1, #0ea5e9)'
                    }}
                    disabled={isSubmitting}
                >
                    {isSubmitting ? 'Verifying...' : 'Access Portal'}
                </button>
            </motion.div>
        </div>
    );
};

export default LoginScreen;
