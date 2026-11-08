const colors = require('tailwindcss/colors')
const svgToDataUri = require('mini-svg-data-uri')

function withOpacityValue(cssVariable) {
  return ({ opacityVariable, opacityValue }) => {
    if (opacityValue !== undefined) {
      return `rgba(var(${cssVariable}), ${opacityValue})`;
    }
    if (opacityVariable !== undefined) {
      return `rgba(var(${cssVariable}), var(${opacityVariable}, 1))`;
    }
    return `rgb(var(${cssVariable}))`;
  };
}

module.exports = {
  content: [
    './resources/views/**/*.php',
    './resources/scripts/**/*.js',
    './resources/scripts/**/*.vue',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: withOpacityValue('--color-primary-50'),
          100: withOpacityValue('--color-primary-100'),
          200: withOpacityValue('--color-primary-200'),
          300: withOpacityValue('--color-primary-300'),
          400: withOpacityValue('--color-primary-400'),
          500: withOpacityValue('--color-primary-500'),
          600: withOpacityValue('--color-primary-600'),
          700: withOpacityValue('--color-primary-700'),
          800: withOpacityValue('--color-primary-800'),
          900: withOpacityValue('--color-primary-900'),
        },
        black: '#040405',
        red: colors.red,
        teal: colors.teal,
        gray: colors.slate,
        // Token InvoiceShelf : la barre latérale (chrome)
        chrome: {
          DEFAULT: withOpacityValue('--color-chrome'),
          raised: withOpacityValue('--color-chrome-raised'),
          fg: withOpacityValue('--color-chrome-fg'),
          muted: withOpacityValue('--color-chrome-muted'),
          accent: withOpacityValue('--color-chrome-accent'),
        },
        // Token InvoiceShelf : surfaces (cartes > zones internes > fond)
        surface: {
          DEFAULT: withOpacityValue('--color-surface'),
          secondary: withOpacityValue('--color-surface-secondary'),
          tertiary: withOpacityValue('--color-surface-tertiary'),
          muted: withOpacityValue('--color-surface-muted'),
        },
        // Token InvoiceShelf : bordures
        line: {
          light: withOpacityValue('--color-line-light'),
          DEFAULT: withOpacityValue('--color-line-default'),
          strong: withOpacityValue('--color-line-strong'),
        },
        heading: withOpacityValue('--color-heading'),
        'body-text': withOpacityValue('--color-body-text'),
        muted: withOpacityValue('--color-muted'),
        // Token InvoiceShelf : badges de statut (texte + fond pastel)
        status: {
          green: {
            DEFAULT: withOpacityValue('--color-status-green'),
            bg: withOpacityValue('--color-status-green-bg'),
          },
          yellow: {
            DEFAULT: withOpacityValue('--color-status-yellow'),
            bg: withOpacityValue('--color-status-yellow-bg'),
          },
          red: {
            DEFAULT: withOpacityValue('--color-status-red'),
            bg: withOpacityValue('--color-status-red-bg'),
          },
          blue: {
            DEFAULT: withOpacityValue('--color-status-blue'),
            bg: withOpacityValue('--color-status-blue-bg'),
          },
          purple: {
            DEFAULT: withOpacityValue('--color-status-purple'),
            bg: withOpacityValue('--color-status-purple-bg'),
          },
          gray: {
            DEFAULT: withOpacityValue('--color-status-gray'),
            bg: withOpacityValue('--color-status-gray-bg'),
          },
        },
        'hero-from': withOpacityValue('--color-hero-from'),
        'hero-to': withOpacityValue('--color-hero-to'),
        danger: withOpacityValue('--color-danger'),
      },
      spacing: {
        88: '22rem',
      },
      backgroundImage: (theme) => ({
        'multiselect-caret': `url("${svgToDataUri(
          `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
  <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
</svg>`
        )}")`,
        'multiselect-spinner': `url("${svgToDataUri(
          `<svg viewBox="0 0 512 512" fill="${theme(
            'colors.primary.500'
          )}" xmlns="http://www.w3.org/2000/svg"><path d="M456.433 371.72l-27.79-16.045c-7.192-4.152-10.052-13.136-6.487-20.636 25.82-54.328 23.566-118.602-6.768-171.03-30.265-52.529-84.802-86.621-144.76-91.424C262.35 71.922 256 64.953 256 56.649V24.56c0-9.31 7.916-16.609 17.204-15.96 81.795 5.717 156.412 51.902 197.611 123.408 41.301 71.385 43.99 159.096 8.042 232.792-4.082 8.369-14.361 11.575-22.424 6.92z"></path></svg>`
        )}")`,
        'multiselect-remove': `url("${svgToDataUri(
          `<svg viewBox="0 0 320 512" fill="${theme(
            'colors.white'
          )}" xmlns="http://www.w3.org/2000/svg"><path d="M207.6 256l107.72-107.72c6.23-6.23 6.23-16.34 0-22.58l-25.03-25.03c-6.23-6.23-16.34-6.23-22.58 0L160 208.4 52.28 100.68c-6.23-6.23-16.34-6.23-22.58 0L4.68 125.7c-6.23 6.23-6.23 16.34 0 22.58L112.4 256 4.68 363.72c-6.23 6.23-6.23 16.34 0 22.58l25.03 25.03c6.23 6.23 16.34 6.23 22.58 0L160 303.6l107.72 107.72c6.23 6.23 16.34 6.23 22.58 0l25.03-25.03c6.23-6.23 6.23-16.34 0-22.58L207.6 256z"></path></svg>`
        )}")`,
      }),
    },

    fontFamily: {
      base: ['Geologica', 'sans-serif'],
    },
    boxShadow: {
      // Ombre discrète teintée indigo, comme les cartes InvoiceShelf
      soft: '0 1px 3px 0 rgb(28, 24, 69 / 0.07), 0 1px 2px -1px rgb(28, 24, 69 / 0.05)',
      card: '0 1px 2px 0 rgb(28, 24, 69 / 0.05), 0 4px 16px -4px rgb(28, 24, 69 / 0.08)',
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    require('@tailwindcss/aspect-ratio'),
    require('tailwind-scrollbar'),
    require('@rvxlab/tailwind-plugin-ios-full-height'),
    require('@tailwindcss/line-clamp'),
  ],
}
