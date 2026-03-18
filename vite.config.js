import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig(({ command, mode }) => {
    const isProduction = mode === 'production';
    
    return {
        plugins: [
            laravel({
                input: 'resources/js/app.jsx',
                refresh: !isProduction,
                buildDirectory: 'build',
            }),
            react(),
        ],
        define: {
            'process.env': {
                VITE_API_URL: process.env.VITE_API_URL || (isProduction ? 'https://your-domain.com' : 'http://localhost:8000'),
            },
        },
        server: {
            host: '0.0.0.0',
            port: 5173,
            cors: true,
        },
        build: {
            target: 'es2015',
            minify: 'terser',
            sourcemap: !isProduction,
            rollupOptions: {
                output: {
                    manualChunks: {
                        vendor: ['react', 'react-dom'],
                        inertia: ['@inertiajs/react'],
                    },
                },
            },
            terserOptions: {
                compress: {
                    drop_console: isProduction,
                    drop_debugger: isProduction,
                },
            },
        },
        optimizeDeps: {
            include: ['react', 'react-dom', '@inertiajs/react'],
        },
    };
});
