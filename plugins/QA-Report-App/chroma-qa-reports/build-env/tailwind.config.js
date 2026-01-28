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
                    // Brand Colors
                    brand: {
                        cream: '#F9F7F2',
                        ink: '#1A1A1A',
                        secondary: '#9D8253',
                    }
                }
            },
            fontFamily: {
                sans: ['Outfit', 'sans-serif'],
                outfit: ['Outfit', 'sans-serif'],
                serif: ['"DM Serif Display"', 'serif'],
            }
        },
    },
    plugins: [],
    corePlugins: {
        preflight: false, // Disable preflight to avoid conflict with WP Admin styles
    }
}
