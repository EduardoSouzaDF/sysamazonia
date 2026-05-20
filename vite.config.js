import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                    'resources/comp_themes/keenicons/styles.bundle.css',
                    'resources/css/styles.css',
                    'resources/comp_themes/apexcharts/apexcharts.css',
                    'resources/js/core.bundle.js',
                    'resources/comp_themes/apexcharts/apexcharts.min.js',
                    'resources/comp_themes/ktui/ktui.min.js',
                        'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
