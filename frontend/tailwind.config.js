/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Razer-inspired gaming theme
        brand: {
          DEFAULT: '#00ff00',
          50: '#f0fff4',
          100: '#c6f6d5',
          200: '#9ae6b4',
          300: '#68d391',
          400: '#48bb78',
          500: '#00ff00',
          600: '#00e600',
          700: '#00cc00',
          800: '#00b300',
          900: '#009900',
        },
        dark: {
          DEFAULT: '#0d0d0d',
          50: '#1a1a1a',
          100: '#262626',
          200: '#333333',
          300: '#404040',
          400: '#4d4d4d',
          500: '#595959',
          600: '#666666',
          700: '#808080',
          800: '#999999',
          900: '#b3b3b3',
        },
        accent: {
          DEFAULT: '#00ff00',
          light: '#33ff33',
          dark: '#00cc00',
          hover: '#00e600',
          glow: 'rgba(0, 255, 0, 0.3)',
        },
        surface: {
          DEFAULT: '#0d0d0d',
          card: '#1a1a1a',
          hover: '#262626',
          muted: '#333333',
          border: '#404040',
        },
        text: {
          primary: '#ffffff',
          secondary: '#b3b3b3',
          muted: '#808080',
        },
        status: {
          success: '#00ff00',
          successBg: 'rgba(0, 255, 0, 0.1)',
          warning: '#ffaa00',
          warningBg: 'rgba(255, 170, 0, 0.1)',
          danger: '#ff3333',
          dangerBg: 'rgba(255, 51, 51, 0.1)',
          info: '#00aaff',
          infoBg: 'rgba(0, 170, 255, 0.1)',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
        display: ['Inter', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        'card': '0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.2)',
        'cardHover': '0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3)',
        'glow': '0 0 20px rgba(0, 255, 0, 0.3)',
        'glow-lg': '0 0 40px rgba(0, 255, 0, 0.4)',
        'glow-sm': '0 0 10px rgba(0, 255, 0, 0.2)',
        'inner-glow': 'inset 0 0 20px rgba(0, 255, 0, 0.1)',
      },
      backgroundImage: {
        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
        'gradient-conic': 'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
        'hero-pattern': 'linear-gradient(135deg, #0d0d0d 0%, #1a1a1a 50%, #0d0d0d 100%)',
        'card-gradient': 'linear-gradient(180deg, rgba(0, 255, 0, 0.05) 0%, transparent 100%)',
        'shine': 'linear-gradient(110deg, transparent 25%, rgba(255, 255, 255, 0.1) 50%, transparent 75%)',
      },
      animation: {
        'glow-pulse': 'glow-pulse 2s ease-in-out infinite',
        'fade-in': 'fade-in 0.5s ease-out',
        'fade-in-up': 'fade-in-up 0.5s ease-out',
        'slide-in-right': 'slide-in-right 0.3s ease-out',
        'shimmer': 'shimmer 2s linear infinite',
        'float': 'float 3s ease-in-out infinite',
      },
      keyframes: {
        'glow-pulse': {
          '0%, 100%': { boxShadow: '0 0 20px rgba(0, 255, 0, 0.3)' },
          '50%': { boxShadow: '0 0 40px rgba(0, 255, 0, 0.5)' },
        },
        'fade-in': {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        'fade-in-up': {
          '0%': { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        'slide-in-right': {
          '0%': { opacity: '0', transform: 'translateX(20px)' },
          '100%': { opacity: '1', transform: 'translateX(0)' },
        },
        'shimmer': {
          '0%': { backgroundPosition: '-200% 0' },
          '100%': { backgroundPosition: '200% 0' },
        },
        'float': {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-10px)' },
        },
      },
      transitionTimingFunction: {
        'bounce-in': 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
      },
    },
  },
  plugins: [],
}
