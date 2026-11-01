/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#fff7ed',
          100: '#ffedd5',
          200: '#fed7aa',
          300: '#fdba74',
          400: '#fb923c',
          500: '#f97316',
          600: '#ea580c',
          700: '#c2410c',
          800: '#9a3412',
          900: '#7c2d12',
          950: '#431407',
        },
        secondary: {
          50: '#ecfeff',
          100: '#cffafe',
          200: '#a5f3fc',
          300: '#67e8f9',
          400: '#22d3ee',
          500: '#06b6d4',
          600: '#0891b2',
          700: '#0e7490',
          800: '#155e75',
          900: '#164e63',
          950: '#083344',
        },
        // Sıcak nötr palet: tüm sayfalardaki gray-* sınıfları bu tonları kullanır.
        gray: {
          50: '#faf8f6',
          100: '#f4f1ed',
          200: '#e9e4de',
          300: '#d8d1c9',
          400: '#aaa197',
          500: '#7d746a',
          600: '#5f574f',
          700: '#453f39',
          800: '#2d2925',
          900: '#1f1c19',
          950: '#141210',
        },
        ink: {
          600: '#4a3f36',
          700: '#382f28',
          800: '#28211c',
          900: '#1d1814',
          950: '#15110e',
        },
        panel: {
          bg: '#171412',
          card: '#211d1a',
          border: '#36302b',
          accent: '#ea580c',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
      boxShadow: {
        soft: '0 1px 2px rgba(41, 28, 16, 0.04), 0 6px 20px -8px rgba(41, 28, 16, 0.10)',
        lift: '0 18px 40px -18px rgba(41, 28, 16, 0.30)',
        glow: '0 10px 26px -10px rgba(234, 88, 12, 0.60)',
      },
      keyframes: {
        shimmer: {
          '0%': { backgroundPosition: '200% 0' },
          '100%': { backgroundPosition: '-200% 0' },
        },
        // Yalnız opacity: transform, sayfa içindeki fixed modal'ların konumunu bozar.
        'fade-up': {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
      },
      animation: {
        shimmer: 'shimmer 1.4s ease-in-out infinite',
        // "backwards": bitince etkisi kalkmalı; aksi halde stacking context oluşur ve modal'lar sidebar'ın altında kalır.
        'fade-up': 'fade-up .35s ease-out backwards',
      },
    },
  },
  plugins: [],
}
