import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import path from 'path'; // Import the path module

export default defineConfig({
    plugins: [
        tailwindcss(), // Add the Tailwind CSS plugin
    ],
    build: {
        // Output directory relative to project root
        outDir: 'public/assets',
        // Empty the output directory before building
        emptyOutDir: true,
        // Generate manifest file
        manifest: '.vite/manifest.json', // Place manifest inside public/assets/.vite
        rollupOptions: {
            input: {
                // Define your entry points
                app: path.resolve(__dirname, 'resources/js/app.js'),
                // Add other entry points if needed later
                // styles: path.resolve(__dirname, 'resources/css/app.css') // Vite handles CSS imported in JS
            },
            output: {
                // Ensures assets are placed directly in outDir without extra subfolders
                entryFileNames: `[name].[hash].js`,
                chunkFileNames: `[name].[hash].js`,
                assetFileNames: `[name].[hash].[ext]`
            }
        },
    },
    server: {
        // Configure the development server
        port: 5173, // Default Vite port
        strictPort: true, // Exit if port is already in use
        // Optional: Proxy requests to your PHP backend if running PHP's built-in server
        // proxy: {
        //     '/': {
        //         target: 'http://localhost:8000', // Your PHP dev server address
        //         changeOrigin: true,
        //     }
        // }
        // Configure server origin for HMR (Hot Module Replacement)
        origin: 'http://localhost:5173'
    },
    // Set the public directory for asset resolution during dev
    publicDir: 'public',
    // Resolve aliases if needed (optional)
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '~': path.resolve(__dirname, 'resources'),
        },
    },
});