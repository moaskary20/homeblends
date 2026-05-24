import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/shop-cart.js',
                'resources/js/shop-checkout.js',
                'resources/css/shop-header.css',
                'resources/css/shop-home.css',
                'resources/js/shop-home.js',
                'resources/css/shop-about.css',
                'resources/js/shop-about.js',
                'resources/css/shop-contact.css',
                'resources/js/shop-contact.js',
                'resources/css/shop-design-team.css',
                'resources/js/shop-design-team.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
