import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  define: {
    // Injected at build time; keeps import.meta out of runtime source (jest-safe).
    __UNIFY_API_URL__: JSON.stringify(process.env.VITE_API_URL || '/api/v1'),
  },
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      injectManifest: {
        swSrc: 'src/sw.ts',
        swDest: 'dist/sw.js',
      },
      includeAssets: ['favicon.ico', 'logo.png', 'offline.html'],
      manifest: {
        name: 'Unify - University Assistant',
        short_name: 'Unify',
        description: 'Unify University Assistant for 600 CS Students',
        theme_color: '#1976D2',
        background_color: '#ffffff',
        display: 'standalone',
        orientation: 'portrait',
        icons: [
          { src: 'logo.png', sizes: '192x192', type: 'image/png' },
          { src: 'logo.png', sizes: '512x512', type: 'image/png' },
        ],
      },
      devOptions: { enabled: false },
    }),
  ],
  server: {
    port: 5173,
    // Accept the sandbox preview host (and any local host) so the live
    // preview and LAN testing work without host allowlist errors.
    allowedHosts: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom', 'react-router-dom'],
          mui: ['@mui/material', '@emotion/react', '@emotion/styled'],
          utils: ['date-fns', 'date-fns-jalali', 'axios'],
        },
      },
    },
    chunkSizeWarningLimit: 300,
    reportCompressedSize: true,
  },
});
