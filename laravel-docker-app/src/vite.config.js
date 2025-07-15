import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '0.0.0.0', // Bind to all network interfaces
        port: 5173, // Explicitly set the port
        hmr: {
            host: 'localhost',
            port: 5173,
        },
        watch: {
            usePolling: true, // Wichtig für Docker
            interval: 1000, // Check for changes every second
        },
    },
    optimizeDeps: {
        force: true, // Force re-optimization when deps change
    },
    build: {
        rollupOptions: {
            cache: false,
            output: {
                entryFileNames: 'assets/[name]-[hash].js',
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]'
            }
        },
        manifest: true,
        outDir: 'public/build',
        assetsDir: 'assets',
        emptyOutDir: true
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/datastore-builder.js',
                'resources/js/filament.js',
                'resources/js/datastore-builder-toolbox.js'
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});