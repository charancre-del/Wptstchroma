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
                        DEFAULT: '#9d8253', // Gold/Bronze
                        dark: '#7d6842',
                        light: '#bca882',
                    },
                    slate: {
                        DEFAULT: '#263238', // Dark Slate
                        light: '#37474f',
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
