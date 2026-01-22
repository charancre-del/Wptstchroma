import { createRoot } from '@wordpress/element';
import App from './App';
import './styles/main.scss';

const rootElement = document.getElementById('chroma-parent-portal-root');

if (rootElement) {
    try {
        console.log("Mounting Chroma Parent Portal...");
        const root = createRoot(rootElement);
        root.render(<App />);
        console.log("Portal Mounted Successfully.");
    } catch (e) {
        console.error("Portal Mount Failed:", e);
        rootElement.innerHTML = '<div style="color:red; padding:20px;">Portal Error: ' + e.message + '</div>';
    }
} else {
    console.warn("Portal Root Not Found. Ensure [chroma_parent_portal] shortcode is present.");
}
