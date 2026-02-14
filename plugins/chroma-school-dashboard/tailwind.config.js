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
                    redLight: '#F4E5E2',
                    blue: '#4A6C7C',
                    blueDark: '#2F4858',
                    blueLight: '#E3E9EC',
                    green: '#8DA399',
                    greenLight: '#E3EBE8',
                    yellow: '#E6BE75',
                    yellowLight: '#FDF6E3'
                }
            }
        }
    },
    plugins: [],
}
