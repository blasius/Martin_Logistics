import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import * as fs from 'node:fs';
import * as path from 'node:path';
import * as os from 'node:os';

const DOMAIN = 'Martin_Logistics.test';
const HERD_CERT_PATH = path.join(os.homedir(), '.config', 'herd', 'config', 'valet', 'Certificates');
const KEY_FILE = path.join(HERD_CERT_PATH, `${DOMAIN}.key`);
const CERT_FILE = path.join(HERD_CERT_PATH, `${DOMAIN}.crt`);

// Check if we are in a local environment with certificates available
const isLocal = fs.existsSync(KEY_FILE) && fs.existsSync(CERT_FILE);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
    resolve: {
        alias: {
            // Allows using '@' to reference the js folder (e.g., '@/components/Button.vue')
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    server: isLocal ? {
        host: '127.0.0.1',
        port: 5173,
        https: {
            key: fs.readFileSync(KEY_FILE),
            cert: fs.readFileSync(CERT_FILE),
        },
        hmr: {
            host: 'martin_logistics.test',
            protocol: 'wss'
        },
    } : {}, // If not local/no certs, use default server config
});
