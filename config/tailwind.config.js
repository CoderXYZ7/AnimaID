/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    "../public/**/*.html",
    "../public/**/*.js",
    "../src/**/*.{js,php}",
    "../api/**/*.{js,php}"
  ],
  safelist: [
    'bg-green-500',
    'bg-red-500',
  ],
  theme: {
    extend: {
      fontFamily: {
        'inter': ['Inter', 'sans-serif'],
      },
      colors: {
        'animaid-blue': '#3b82f6',
        'animaid-green': '#10b981',
        'animaid-orange': '#f59e0b',
        'animaid-red': '#ef4444',
        'animaid-purple': '#8b5cf6',
      }
    },
  },
  plugins: [],
}
