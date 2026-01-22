import React from 'react';
import { AuthProvider } from './context/AuthContext';
import MainLayout from './components/MainLayout';

const App = () => {
    return (
        <AuthProvider>
            <div className="mesh-bg">
                <div className="circle c1"></div>
                <div className="circle c2"></div>
                <div className="circle c3"></div>
            </div>
            <MainLayout />
        </AuthProvider>
    );
};

export default App;
