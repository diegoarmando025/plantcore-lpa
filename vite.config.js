import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
export default defineConfig({ plugins: [laravel({ input: ['resources/css/lpa.css','resources/js/lpa.js'], refresh:true })] });
