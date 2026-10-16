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
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Biru navy logo Tazkia (#194189 = 700)
                tazkia: {
                    50: '#f1f5fb',
                    100: '#e1eaf7',
                    200: '#c3d3ee',
                    300: '#97b2e0',
                    400: '#5f84c9',
                    500: '#3a62ad',
                    600: '#274f9c',
                    700: '#194189',
                    800: '#14366f',
                    900: '#0f2a57',
                    950: '#0a1c3b',
                },
                // Oranye logo Tazkia (#EE7B1D = 500)
                accent: {
                    50: '#fff6ed',
                    100: '#ffead4',
                    200: '#ffd2a8',
                    300: '#ffb271',
                    400: '#f99444',
                    500: '#ee7b1d',
                    600: '#de6113',
                    700: '#b84812',
                },
                ink: {
                    DEFAULT: '#111827',
                    soft: '#4b5563',
                    muted: '#8b94a3',
                },
                canvas: '#f6f8fc',
                line: '#e5e9f2',
            },
            boxShadow: {
                card: '0 1px 2px rgba(15, 42, 87, 0.04), 0 8px 24px -8px rgba(15, 42, 87, 0.08)',
                focus: '0 0 0 4px rgba(25, 65, 137, 0.12)',
            },
            borderRadius: {
                xl2: '1.125rem',
            },
        },
    },

    plugins: [forms],
};
