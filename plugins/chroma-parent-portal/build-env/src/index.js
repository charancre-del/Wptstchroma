import { createRoot } from '@wordpress/element';
import App from './App';
import './styles/main.scss';

const rootElement = document.getElementById('chroma-parent-portal-root');
if (rootElement) {
    createRoot(rootElement).render(<App />);
}
