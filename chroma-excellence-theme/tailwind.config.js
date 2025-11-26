/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './inc/**/*.php',
    './template-parts/**/*.php',
    './assets/js/**/*.js',
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
          navy: '#4A6C7C',
        },
        chroma: {
          red: '#D67D6B',
          redLight: '#F4E5E2',
          blue: '#4A6C7C',
          blueDark: '#2F4858',
          blueLight: '#E3E9EC',
          teal: '#4A6C7C',
          tealLight: '#E3E9EC',
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
    },
  },
  plugins: [],
};
