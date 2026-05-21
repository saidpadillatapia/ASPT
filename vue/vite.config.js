import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
	plugins: [vue()],
	build: {
		outDir: resolve(__dirname, '../public/vue'),
		emptyOutDir: true,
	},
	server: {
		port: 5173,
		proxy: {
			'/api': 'http://localhost:8000',
		},
	},
});
