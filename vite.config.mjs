import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
    ],
    build: {
        outDir: 'public/dist',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                main: './src/js/app.js',
                css: './src/css/main.css'
            },
        },
    },
    server: {
        port: 5173,
        strictPort: true,
        // php requests if running PHP dev server separately
        // origin: 'http://127.0.0.1:8000' // Example if using php -S localhost:8000
        // hmr: { // Hot Module Replacement settings if needed
        //     host: 'localhost',
        // },
    },
    base: '/dist/',
});