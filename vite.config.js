import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            '@/Components': fileURLToPath(new URL('./resources/js/Components', import.meta.url)),
            '@/Layouts': fileURLToPath(new URL('./resources/js/Layouts', import.meta.url)),
            '@/Pages': fileURLToPath(new URL('./resources/js/Pages', import.meta.url)),
            '@/Themes': fileURLToPath(new URL('./resources/js/Themes', import.meta.url)),
            '@/Services': fileURLToPath(new URL('./resources/js/Services', import.meta.url)),
            '@/Models': fileURLToPath(new URL('./resources/js/Models', import.meta.url)),
            '@/Utils': fileURLToPath(new URL('./resources/js/Utils', import.meta.url)),
            '@/actions': fileURLToPath(new URL('./resources/js/actions', import.meta.url)),
            '@/routes': fileURLToPath(new URL('./resources/js/routes', import.meta.url)),
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
