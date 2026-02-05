module.exports = {
    content: [
        "./templates/**/*.php",
        "./assets/src/**/*.jsx"
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Outfit', 'sans-serif'],
                serif: ['Playfair Display', 'serif']
            },
            colors: {
                brand: {
                    ink: '#263238',
                    cream: '#FFFCF8'
                },
                chroma: {
                    red: '#D67D6B',
                    blue: '#4A6C7C',
                    blueDark: '#2F4858',
                    yellow: '#E6BE75'
                }
            }
        }
    },
    plugins: [],
}
