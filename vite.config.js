import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Polices auto-hébergées (perf / Core Web Vitals) — charte HRK :
            //  - Montserrat : titrage, caractère moderne & hôtelier
            //  - Poppins    : texte courant, lisibilité mobile
            fonts: [
                bunny('Montserrat', { weights: [400, 500, 600, 700] }),
                bunny('Poppins', { weights: [300, 400, 500, 600] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
