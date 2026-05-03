/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./*.{html,php}"],
  theme: {
    extend: {
      colors: {
        ink: '#0b1220',
        primary: '#0b1220',
        accent: '#f6c21f',
        'accent-strong': '#f59e0b',
      }
    },
  },
  plugins: [],
}
