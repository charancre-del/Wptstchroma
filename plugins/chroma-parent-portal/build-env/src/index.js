import { createRoot } from '@wordpress/element';
import App from './App';
import ErrorBoundary from './components/common/ErrorBoundary';
import './styles/main.scss';

const rootElement = document.getElementById('chroma-parent-portal-root');

const PortalRoot = () => (
    <ErrorBoundary>
        <App />
    </ErrorBoundary>
);

if (rootElement) {
    try {
        console.log("=== Chroma Parent Portal: Starting Mount ===");

        const forceStyles = () => {
            // 1. Fix the root element itself
            const style = rootElement.style;
            style.setProperty('display', 'flex', 'important');
            style.setProperty('position', 'fixed', 'important');
            style.setProperty('top', '0', 'important');
            style.setProperty('left', '0', 'important');
            style.setProperty('width', '100vw', 'important');
            style.setProperty('height', '100vh', 'important');
            style.setProperty('z-index', '999999', 'important');
            style.setProperty('background-color', '#FDFBF7', 'important');
            style.setProperty('visibility', 'visible', 'important');
            style.setProperty('opacity', '1', 'important');
            style.setProperty('margin', '0', 'important');
            style.setProperty('padding', '0', 'important');
            style.setProperty('overflow', 'visible', 'important');

            // 2. Iterate up the parent chain and force visibility/size
            let parent = rootElement.parentElement;
            let depth = 0;
            while (parent && depth < 20) {
                // Skip script/style tags
                if (['SCRIPT', 'STYLE', 'LINK', 'META'].includes(parent.tagName)) {
                    parent = parent.parentElement;
                    continue;
                }

                parent.style.setProperty('max-width', 'none', 'important');
                parent.style.setProperty('max-height', 'none', 'important');
                parent.style.setProperty('overflow', 'visible', 'important');
                parent.style.setProperty('visibility', 'visible', 'important');
                parent.style.setProperty('opacity', '1', 'important');

                // If the parent is hidden, force it to be a block
                const currentDisplay = window.getComputedStyle(parent).display;
                if (currentDisplay === 'none') {
                    parent.style.setProperty('display', 'block', 'important');
                }

                parent = parent.parentElement;
                depth++;
            }

            // 3. Force HTML and Body to be full screen
            document.documentElement.style.setProperty('height', '100%', 'important');
            document.documentElement.style.setProperty('overflow', 'hidden', 'important');
            document.body.style.setProperty('height', '100%', 'important');
            document.body.style.setProperty('overflow', 'hidden', 'important');
            document.body.style.setProperty('margin', '0', 'important');
        };

        forceStyles();

        const root = createRoot(rootElement);
        root.render(<PortalRoot />);

        console.log("=== Chroma Parent Portal: Render Triggered ===");

        // Re-apply styles after a delay to override theme late-loads
        setTimeout(() => {
            forceStyles();
            console.log("Post-Mount Diagnostic:", {
                rootHeight: rootElement.offsetHeight,
                rootWidth: rootElement.offsetWidth,
                children: rootElement.children.length,
                isAppVisible: !!document.querySelector('.portal-app-wrapper')
            });
        }, 500);

        // Periodically pulse styles if it's still height 0
        const pulse = setInterval(() => {
            if (rootElement.offsetHeight === 0) {
                console.warn("Portal Height still 0, pulsing styles...");
                forceStyles();
            } else {
                clearInterval(pulse);
            }
        }, 2000);

    } catch (e) {
        console.error("Portal Mount Fatal Error:", e);
        rootElement.innerHTML = '<div style="color:red; padding:40px; background:white; position:fixed; inset:0; z-index:999999;"><h1>Portal Mount Failed</h1><p>' + e.message + '</p></div>';
    }
} else {
    console.warn("Portal Root Not Found. Ensure [chroma_parent_portal] shortcode is present.");
}
