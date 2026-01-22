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

        // DIAGNOSTIC: Check parent chain
        console.log("=== PARENT CHAIN DIAGNOSTICS ===");
        let current = rootElement;
        let depth = 0;
        while (current && depth < 10) {
            const styles = window.getComputedStyle(current);
            console.log(`Level ${depth}: ${current.tagName}#${current.id || 'no-id'}.${current.className || 'no-class'}`, {
                width: current.offsetWidth,
                height: current.offsetHeight,
                display: styles.display,
                position: styles.position,
                maxWidth: styles.maxWidth,
                maxHeight: styles.maxHeight,
                overflow: styles.overflow
            });
            current = current.parentElement;
            depth++;
        }
        console.log("=== END DIAGNOSTICS ===");

        // ULTRA NUCLEAR: Fix entire parent chain + force dimensions
        const forceStyles = () => {
            // Fix all parents up to body
            let parent = rootElement.parentElement;
            let level = 0;
            while (parent && level < 10) {
                parent.style.cssText = `
                    max-width: none !important;
                    max-height: none !important;
                    width: 100% !important;
                    height: 100% !important;
                    overflow: visible !important;
                    display: block !important;
                `;
                parent = parent.parentElement;
                level++;
            }

            // Fix html and body
            document.documentElement.style.cssText = 'height: 100% !important; overflow: hidden !important; max-width: none !important; max-height: none !important;';
            document.body.style.cssText = 'height: 100% !important; overflow: hidden !important; margin: 0 !important; max-width: none !important; max-height: none !important;';

            // Fix root element
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
                max-width: none !important;
                max-height: none !important;
                display: flex !important;
                z-index: 999999 !important;
                visibility: visible !important;
                opacity: 1 !important;
                background: #FDFBF7 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            `;
        };

        forceStyles();

        console.log("After force styles - Root Element Dimensions:", {
            width: rootElement.offsetWidth,
            height: rootElement.offsetHeight,
            clientWidth: rootElement.clientWidth,
            clientHeight: rootElement.clientHeight,
            display: window.getComputedStyle(rootElement).display,
            position: window.getComputedStyle(rootElement).position
        });

        const root = createRoot(rootElement);
        root.render(<PortalRoot />);
        console.log("Portal Mounted Successfully.");

        // Reapply styles after render
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
