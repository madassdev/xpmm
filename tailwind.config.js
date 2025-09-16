import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors:{
                "primary" : "#F89A39",
            }
        },
    },
    safelist: [
        'bg-teal-100','text-teal-700','ring-teal-200',
        'bg-sky-100','text-sky-700','ring-sky-200',
        'bg-amber-100','text-amber-700','ring-amber-200',
        'bg-purple-100','text-purple-700','ring-purple-200',
        'bg-indigo-100','text-indigo-700','ring-indigo-200',
        'bg-pink-100','text-pink-700','ring-pink-200',
      ],

    plugins: [forms],
};
