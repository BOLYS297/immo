/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: ['./src/**/*.{js,ts,jsx,tsx,mdx}'],
  theme: {
    extend: {
      letterSpacing: { tighter: '-0.03em' },
      keyframes: {
        float: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } },
        marquee: { from: { transform: 'translateX(0)' }, to: { transform: 'translateX(-50%)' } },
      },
      animation: { float: 'float 6s ease-in-out infinite', marquee: 'marquee 45s linear infinite' },
      colors: {
        immo: {
          dark: '#273480',  // Bleu Nuit Principal
          cyan: '#54C3E9',  // Cyan Dynamique (CTA & accents)
          sky: '#AEDDED',   // Bleu Ciel (hover & dégradés)
          satin: '#F4F4F4', // Blanc Satiné (fond par défaut)
        },
      },
      fontFamily: {
        gilroy: ['Gilroy', 'Outfit Variable', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        sans: ['Gilroy', 'Outfit Variable', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      backgroundImage: {
        'immo-gradient': 'linear-gradient(135deg, #54C3E9 0%, #AEDDED 55%, #273480 100%)',
      },
    },
  },
  plugins: [],
};
