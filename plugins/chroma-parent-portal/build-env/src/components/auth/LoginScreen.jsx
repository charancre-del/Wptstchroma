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

    return (
        <div className="login-container" style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '80vh' }}>
            <motion.div
                className="glass-panel"
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                style={{ padding: '40px', width: '350px', textAlign: 'center' }}
            >
                <img src={window.chromaPortalSettings.assetsUrl + 'logo.png'} alt="Chroma ELA" style={{ width: '120px', marginBottom: '20px' }} onError={(e) => e.target.style.display = 'none'} />
                <h2>Welcome Family</h2>
                <p style={{ marginBottom: '30px', color: '#666' }}>Please enter your access PIN</p>

                <div className="pin-display" style={{ background: 'rgba(255,255,255,0.5)', padding: '15px', borderRadius: '10px', fontSize: '24px', letterSpacing: '5px', marginBottom: '20px', minHeight: '60px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    {pin.replace(/./g, '•')}
                </div>

                {error && <div style={{ color: 'red', marginBottom: '10px' }}>{error}</div>}

                <div className="num-pad" style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px' }}>
                    {[1, 2, 3, 4, 5, 6, 7, 8, 9].map(num => (
                        <button key={num} onClick={() => handleNumClick(num)} className="portal-btn" style={{ background: 'white', color: '#333', fontSize: '18px', padding: '15px' }}>
                            {num}
                        </button>
                    ))}
                    <button onClick={handleClear} className="portal-btn" style={{ background: 'rgba(255,0,0,0.1)', color: 'red' }}>C</button>
                    <button onClick={() => handleNumClick(0)} className="portal-btn" style={{ background: 'white', color: '#333', fontSize: '18px' }}>0</button>
                    <button onClick={handleBackspace} className="portal-btn" style={{ background: 'rgba(0,0,0,0.1)', color: '#333' }}>←</button>
                </div>

                <button onClick={handleSubmit} className="portal-btn" style={{ marginTop: '30px', width: '100%', fontSize: '18px' }} disabled={isSubmitting}>
                    {isSubmitting ? 'Verifying...' : 'Enter Portal'}
                </button>
            </motion.div>
        </div>
    );
};

export default LoginScreen;
