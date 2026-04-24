import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import daisyui from 'daisyui';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, typography, daisyui],

    daisyui: {
    themes: [
        "light",
        {
            synthwave: {
                "primary": "#5b8cff",
                "secondary": "#7c5cff",
                "accent": "#22c55e",
                "neutral": "#15151b",
                "base-100": "#101014",
                "base-200": "#18181f",
                "base-300": "#23232b",
                "base-content": "#f5f7ff",
                "info": "#38bdf8",
                "success": "#22c55e",
                "warning": "#f59e0b",
                "error": "#ef4444",
            },
        },
    ],
},
};