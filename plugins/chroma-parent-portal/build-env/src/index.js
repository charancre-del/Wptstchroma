import { createRoot } from '@wordpress/element';
import App from './App';
import './styles/main.scss';

const rootElement = document.getElementById('chroma-parent-portal-root');

import ErrorBoundary from './components/common/ErrorBoundary';

const PortalRoot = () => (
    <ErrorBoundary>
        <App />
    </ErrorBoundary>
);

if (rootElement) {
    try {
        console.log("Mounting Chroma Parent Portal...");
        console.log("Root Element:", rootElement);
        console.log("Root Element Computed Styles:", window.getComputedStyle(rootElement));
        console.log("Root Element Dimensions:", {
            width: rootElement.offsetWidth,
            height: rootElement.offsetHeight,
            display: window.getComputedStyle(rootElement).display,
            position: window.getComputedStyle(rootElement).position
        });
        const root = createRoot(rootElement);
        root.render(<PortalRoot />);
        console.log("Portal Mounted Successfully.");
        // Log after render
        setTimeout(() => {
            console.log("Post-render Root Dimensions:", {
                width: rootElement.offsetWidth,
                height: rootElement.offsetHeight,
                childCount: rootElement.children.length
            });
            console.log("Root Element HTML:", rootElement.innerHTML.substring(0, 500));
        }, 100);
    } catch (e) {
        console.error("Portal Mount Failed:", e);
        rootElement.innerHTML = '<div style="color:red; padding:20px;">Portal Error: ' + e.message + '</div>';
    }
} else {
    console.warn("Portal Root Not Found. Ensure [chroma_parent_portal] shortcode is present.");
}
