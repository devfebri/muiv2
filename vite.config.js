import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/admin.js',
            ],
            refresh: true,
            fonts: [
                bunny('Plus Jakarta Sans', {
                    alias: 'jakarta',
                    weights: [400, 500, 600, 700, 800],
                }),
                bunny('Playfair Display', {
                    alias: 'playfair',
                    weights: [500, 600, 700],
                    styles: ['normal', 'italic'],
                    preload: [{ weight: 600 }],
                }),
                bunny('Amiri', {
                    alias: 'amiri',
                    weights: [400, 700],
                    subsets: ['arabic'],
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        chunkSizeWarningLimit: 1200,
    },
    server: {
        watch: {
            // Unggahan (PDF/gambar) & berkas runtime tidak perlu dipantau; di Windows berkas yang
            // sedang ditulis bisa terkunci (EBUSY) dan membuat dev server berhenti.
            ignored: ['**/storage/**', '**/public/uploads/**', '**/public/build/**', '**/vendor/**'],
        },
    },
});
