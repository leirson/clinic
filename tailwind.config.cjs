module.exports = {
  content: [
    './*.php',
    './includes/**/*.php',
    './modules/**/*.php',
    './portal/**/*.php',
    './resources/js/**/*.js',
    './resources/entries/**/*.js',
    './sw.js',
  ],
  theme: {
    extend: {
      colors: {
        primary: 'rgb(var(--cor-primaria-rgb, 14 165 233) / <alpha-value>)',
        'primary-dark': 'rgb(var(--cor-secundaria-rgb, 29 78 216) / <alpha-value>)',
      },
    },
  },
  safelist: [
    'from-sky-600','to-blue-800','from-cyan-500','to-teal-700','from-violet-500','to-fuchsia-700',
    'from-violet-600','to-purple-900','from-sky-400','to-cyan-700','from-pink-500','to-rose-700',
    'from-amber-500','to-orange-700','from-red-500','to-rose-800','from-indigo-500','to-emerald-800',
    'bg-gradient-to-b','bg-gradient-to-br','bg-gradient-to-r'
  ],
  plugins: [],
};
