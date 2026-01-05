/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './app/**/*.{js,ts,jsx,tsx,mdx}',
    ],
    theme: {
        extend: {
            colors: {
                brand: { ink: '#263238', cream: '#FFFCF8' },
                chroma: {
                    red: '#D67D6B',
                    blue: '#4A6C7C',
                    yellow: '#E6BE75'
                }
            },
            fontFamily: {
                sans: ['var(--font-outfit)'],
                serif: ['var(--font-playfair)'],
            }
        },
    },
    plugins: [],
}
