// vite.config.mjs
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ command }) => ({
    // base: Defines the public base path when served in production.
    // During 'serve' (dev), it defaults to '/'
    // During 'build', we set it relative to your web root, matching the outDir.
    base: command === 'serve' ? '' : '/build/',

    plugins: [
        tailwindcss(), // Add the Tailwind CSS plugin
    ],

    build: {
        // generate manifest.json in outDir
        manifest: true,
        // output directory relative to project root
        outDir: 'public/build',
        rollupOptions: {
            // overwrite default .html entry
            // input files relative to project root
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
        },
    },

    server: {
        // required to load scripts from custom host
        origin: 'http://localhost:5173', // Ensure this matches the Vite dev server address

        // Optional: configure server host/port if needed
        host: 'localhost', // Or '0.0.0.0' to accept connections from network
        port: 5173,
        strictPort: true, // Don't try other ports if 5173 is busy

        // Configure HMR (Hot Module Replacement)
        hmr: {
            host: 'localhost:8000', // Host for HMR websocket connection
        },

        // Optional: Proxy PHP requests if needed, though usually not
        // required if you run PHP's server separately.
        // proxy: {
        //     '/': {
        //         target: 'http://localhost:8000', // Your PHP server URL
        //         changeOrigin: true,
        //     }
        // }
    }
}));