// vite.config.js
//vite configuration file for laravel
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        // Laravel vite plugin - handles asset compilation and hot module replacement
        laravel({
            // Entry points for the build process
            input: ['resources/css/app.css', 'resources/js/app.js'],
            // Automatically reload the page when the source code changes - very important for laravel vite
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Allow access from any IP address - useful for mobile devices on the same network (0.0.0.0)
        // Host to listen on - 0.0.0.0 means all interfaces (accessible from network)
        host: '0.0.0.0',
        // Watch for file changes in the project directory (exclude storage/framework/views to avoid unnecessary reloads)
        watch: {
            // Ignore compiled Blade view cache files from file watcher
            // Prevents unnecessary reloads when Laravel caches views
            ignored: ['**/storage/framework/views/**'],
        },
    },
});


