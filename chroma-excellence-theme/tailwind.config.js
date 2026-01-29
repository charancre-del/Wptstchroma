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
        sans: ['Outfit', 'Outfit-Fallback', 'system-ui', 'sans-serif'],
        serif: ['Playfair Display', 'ui-serif', 'Georgia', 'serif'],
      },
      colors: {
        brand: {
          ink: '#263238',
          cream: '#FFFCF8',
          navy: '#4A6C7C',
        },
        chroma: {
          red: '#A84B38', // Darkened from #D67D6B for 4.5:1 contrast
          redLight: '#F4E5E2',
          orange: '#C26524', // Darkened from #E89654 for 4.5:1 contrast
          orangeLight: '#FEF0E6',
          blue: '#4A6C7C',
          blueDark: '#2F4858',
          blueLight: '#E3E9EC',
          green: '#4A7C59',
          greenLight: '#E3ECE6',
          yellow: '#C2A024',
          yellowLight: '#FEF8E6',
          purple: '#7D5BA6',
          purpleLight: '#F3EBF9',
          teal: '#248EC2',
          tealLight: '#E6F4FE',
        },
      },
    },
  },
  safelist: [
    // Animations & Delays
    'animate-pulse', 'animate-bounce', 'animate-spin', 'animate-fade-in-up',
    'fade-in-up', 'delay-100', 'delay-200', 'delay-300',

    // Custom shadows
    'shadow-card', 'shadow-cardHover', 'shadow-float', 'shadow-glow', 'shadow-soft',

    // Static utilities
    'w-2', 'h-2', 'rounded-full',

    // Chroma base colors (no opacity) - ALL prefixes
    {
      pattern: /(bg|text|border|from|to)-chroma-(red|blue|green|yellow|purple|orange|teal)(Light|Dark)?$/,
      variants: ['hover', 'group-hover', 'focus'],
    },
    // Chroma opacity variants - limit to actually used values
    {
      pattern: /(bg|text|border|from|to)-chroma-(red|blue|green|yellow|purple|orange|teal)(Light|Dark)?\/(5|10|15|20|30|40|50|80|90)$/,
      variants: ['hover'],
    },

    // Brand base colors
    {
      pattern: /(bg|text|border)-brand-(ink|cream|navy)$/,
      variants: ['hover'],
    },
    // Brand opacity variants
    {
      pattern: /(bg|text|border)-brand-(ink|cream|navy)\/(5|10|20|30|40|50|60|70|80|90)$/,
      variants: ['hover'],
    },
  ],
  plugins: [],
};
