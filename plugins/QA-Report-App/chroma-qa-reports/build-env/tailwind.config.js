/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./src/**/*.{js,jsx,ts,tsx}",
    ],
    theme: {
        extend: {
            colors: {
                cqa: {
                    primary: {
                        DEFAULT: '#6366f1',
                        dark: '#4f46e5',
                        light: '#818cf8',
                    },
                    success: '#10b981',
                    warning: '#f59e0b',
                    danger: '#ef4444',
                    info: '#3b82f6',
                }
            }
        },
    },
    plugins: [],
    corePlugins: {
        preflight: false, // Disable preflight to avoid conflict with WP Admin styles
    }
}
