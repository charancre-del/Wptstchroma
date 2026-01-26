import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './index.css';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('cqa-react-app');
    if (container) {
        const root = createRoot(container);
        root.render(<App />);
    }
});
