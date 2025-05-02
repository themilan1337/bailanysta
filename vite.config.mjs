// vite.config.mjs
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ command }) => ({
    base: command === 'serve' ? '' : '/build/',

    plugins: [
        tailwindcss(),
    ],

    build: {
        manifest: true,
        outDir: 'public/build',
        rollupOptions: {
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
        },
    },

    server: {
        origin: 'http://localhost:5173',

        host: 'localhost',
        port: 5173,
        strictPort: true,

        hmr: {
            host: 'localhost:8000',
        },
    }
}));