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
                // The shipped webpack development bundle hides module sources in eval.
                // Rollup renames exports, but cannot update identifiers inside strings.
                // Decode static JSON literals before bundling, preserving the vendor file.
                return code.replace(/^eval\((".*")\);?$/gm, (_match, source) => JSON.parse(source));
            },
        },
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
