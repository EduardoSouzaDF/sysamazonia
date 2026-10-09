import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        {
            name: 'decode-legacy-theme-modules',
            enforce: 'pre',
            transform(code, id) {
                if (!id.endsWith('/resources/js/core.bundle.js')) return;

                return code.replace(/^eval\((".*")\);?$/gm, (_match, source) => JSON.parse(source));
            },
        },
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/dashboard.css',
                'resources/css/monitoring.css',
                'resources/js/monitoring.js',
                'resources/css/registrations.css',
                'resources/css/pages-table.css',
                'resources/js/dashboard.js',
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
