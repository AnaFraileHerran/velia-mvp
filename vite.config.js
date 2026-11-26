import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/filament/direccion/theme.css',
                'resources/css/filament/turno/theme.css',
                // App de la auxiliar con shadcn-vue (25/11/2026)
                'resources/css/auxiliar.css',
                'resources/js/auxiliar/app.ts',
            ],
            refresh: true,
        }),
        vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
        tailwindcss(),
    ],
    resolve: { alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) } },
})
