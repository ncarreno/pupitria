import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Geist', ...defaultTheme.fontFamily.sans],
                mono: ['"Geist Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Único color de acento: verde pino desaturado. El resto es la escala `stone`.
                pino: {
                    50: '#f2f8f6',
                    100: '#ddeee9',
                    200: '#bddcd3',
                    300: '#90c3b6',
                    400: '#62a595',
                    500: '#428a7a',
                    600: '#317063',
                    700: '#295b51',
                    800: '#244b44',
                    900: '#203f3a',
                    950: '#0f2421',
                },
            },
            boxShadow: {
                suave: '0 1px 2px rgba(41, 37, 36, 0.04), 0 12px 32px -14px rgba(41, 37, 36, 0.12)',
                elevada: '0 2px 4px rgba(41, 37, 36, 0.04), 0 24px 48px -18px rgba(41, 37, 36, 0.22)',
                papel: '0 1px 1px rgba(41, 37, 36, 0.06), 0 18px 40px -20px rgba(41, 37, 36, 0.35)',
            },
            transitionTimingFunction: {
                suave: 'cubic-bezier(0.16, 1, 0.3, 1)',
            },
            keyframes: {
                aparecer: {
                    from: { opacity: '0', transform: 'translateY(8px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                flotar: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
                brillo: {
                    from: { transform: 'translateX(-100%)' },
                    to: { transform: 'translateX(100%)' },
                },
                latido: {
                    '0%': { transform: 'scale(1)', opacity: '0.6' },
                    '80%, 100%': { transform: 'scale(2.4)', opacity: '0' },
                },
            },
            animation: {
                aparecer: 'aparecer 0.6s cubic-bezier(0.16, 1, 0.3, 1) backwards',
                flotar: 'flotar 6s ease-in-out infinite',
                'flotar-lento': 'flotar 8s ease-in-out -3s infinite',
                brillo: 'brillo 1.6s ease-in-out infinite',
                latido: 'latido 2s cubic-bezier(0, 0, 0.2, 1) infinite',
            },
        },
    },

    plugins: [forms],
};
