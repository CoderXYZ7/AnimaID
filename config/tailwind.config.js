/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.html",
    "./admin/*.html",
    "./src/**/*.{js,php}",
    "./api/**/*.{js,php}"
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
