/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.php',
    './resources/**/*.js',
    './template-parts/**/*.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Outfit', 'system-ui', 'sans-serif'],
        serif: ['Playfair Display', 'ui-serif', 'Georgia', 'serif'],
      },
      colors: {
        brand: {
          ink: '#263238',
          cream: '#FFFCF8',
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
          yellowLight: '#FDF6E3',
        },
      },
      borderRadius: {
        '4xl': '2.5rem',
        '5xl': '3.5rem',
      },
      boxShadow: {
        soft: '0 20px 40px -10px rgba(74, 108, 124, 0.08)',
        card: '0 10px 30px -5px rgba(0, 0, 0, 0.04)',
      },
    },
  },
  plugins: [],
};
