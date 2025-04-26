import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                polysans: ['PolySansTrial', 'ui-sans-serif', 'system-ui', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                purple: {
                    brand: '#9276FA', // Richiamabile con 'text-purple-brand', 'bg-purple-brand', ecc.
                },
            },
            scale: {
                '1015': '1.015',
            }
        },
    },

    plugins: [forms, typography],
};
