import React from 'react';
import { AuthProvider } from './context/AuthContext';
import MainLayout from './components/MainLayout';

const App = () => {
    return (
        <AuthProvider>
            <MainLayout />
        </AuthProvider>
    );
};

export default App;
