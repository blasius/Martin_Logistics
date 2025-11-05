import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

// FIX: We must use the 'node:' protocol for Node built-in modules (fs and path)
// in modern ES modules (which your config file is).
import * as fs from 'node:fs';
import * as path from 'node:path';
// NEW: Import the 'os' module to get the user's home directory dynamically
import * as os from 'node:os';

// Define the relative path from the user's home directory to the certificates.
// This assumes the certificates are always stored in the same path relative to the home folder.
const HERD_CERT_PATH = path.join(
    os.homedir(),
    '.config',
    'herd',
    'config',
    'valet',
    'Certificates'
);

// Define the specific key and cert filenames
const DOMAIN = 'Martin_Logistics.test';
const KEY_FILE = path.join(HERD_CERT_PATH, `${DOMAIN}.key`);
const CERT_FILE = path.join(HERD_CERT_PATH, `${DOMAIN}.crt`);


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
    server: {
        host: '127.0.0.1',
        port: 5173,
        https: {
            // Dynamically construct the full path using os.homedir() and path.join()
            // path.resolve is no longer needed since path.join already creates an absolute path
            key: fs.readFileSync(KEY_FILE),
            cert: fs.readFileSync(CERT_FILE),
        },
        hmr: {
            host: 'martin_logistics.test', // Your main domain
            protocol: 'wss' // Use secure WebSocket protocol
        },
    },
});
