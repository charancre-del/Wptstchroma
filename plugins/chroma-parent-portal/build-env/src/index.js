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

        // NUCLEAR OPTION: Force dimensions and visibility via JavaScript
        // This overrides any WordPress theme CSS that might be interfering
        const forceStyles = () => {
            rootElement.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                min-width: 100vw !important;
                min-height: 100vh !important;
                display: flex !important;
                z-index: 999999 !important;
                visibility: visible !important;
                opacity: 1 !important;
                background: #FDFBF7 !important;
                margin: 0 !important;
                padding: 0 !important;
            `;

            // Also ensure html and body have proper height
            document.documentElement.style.cssText = 'height: 100% !important; overflow: hidden !important;';
            document.body.style.cssText = 'height: 100% !important; overflow: hidden !important; margin: 0 !important;';
        };

        forceStyles();

        console.log("After force styles - Root Element Dimensions:", {
            width: rootElement.offsetWidth,
            height: rootElement.offsetHeight,
            display: window.getComputedStyle(rootElement).display,
            position: window.getComputedStyle(rootElement).position
        });

        const root = createRoot(rootElement);
        root.render(<PortalRoot />);
        console.log("Portal Mounted Successfully.");

        // Reapply styles after render to ensure they stick
        setTimeout(() => {
            forceStyles();
            console.log("Post-render Root Dimensions:", {
                width: rootElement.offsetWidth,
                height: rootElement.offsetHeight,
                childCount: rootElement.children.length
            });
        }, 100);
    } catch (e) {
        console.error("Portal Mount Failed:", e);
        rootElement.innerHTML = '<div style="color:red; padding:20px;">Portal Error: ' + e.message + '</div>';
    }
} else {
    console.warn("Portal Root Not Found. Ensure [chroma_parent_portal] shortcode is present.");
}
