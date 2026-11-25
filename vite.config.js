import { defineConfig, loadEnv } from 'vite';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(fileURLToPath(import.meta.url));
const hotFile = path.join(root, 'storage/vite.hot');
function clinicHotFilePlugin(env) {
  let written = '';
  return {
    name: 'clinic-hot-file',
    configureServer(server) {
      const write = () => {
        written = (env.VITE_DEV_SERVER_URL || `http://${env.VITE_DEV_HOST || 'localhost'}:${server.config.server.port}`).replace(/\/$/, '');
        const url = new URL(written);
        if (!['http:', 'https:'].includes(url.protocol)) throw new Error('VITE_DEV_SERVER_URL deve usar HTTP ou HTTPS');
        fs.mkdirSync(path.dirname(hotFile), { recursive: true });
        fs.writeFileSync(hotFile, written);
      };
      // Publish HMR only after successful bind, never for a server that failed to start.
      server.httpServer?.once('listening', write);
      const cleanup = () => {
        if (written && fs.existsSync(hotFile) && fs.readFileSync(hotFile, 'utf8') === written) fs.rmSync(hotFile, { force: true });
      };
      server.httpServer?.once('close', cleanup);
      process.once('exit', cleanup);
      for (const signal of ['SIGINT', 'SIGTERM']) process.once(signal, () => { cleanup(); process.exit(); });
      server.watcher.add([path.join(root, '*.php'), path.join(root, 'includes/**/*.php'), path.join(root, 'modules/**/*.php'), path.join(root, 'portal/**/*.php')]);
      server.watcher.on('change', file => {
        if (file.endsWith('.php')) server.ws.send({ type: 'full-reload', path: '*' });
      });
    },
  };
}
export default defineConfig(({ mode }) => {
  const env = { ...loadEnv(mode, root, 'VITE_'), ...process.env };
  const publicHost = env.VITE_DEV_HOST || 'localhost';
  const publicUrl = env.VITE_DEV_SERVER_URL;
  return {
    root,
    // Relative output works at domain root and in subdirectories, including CSS fonts.
    base: './',
    plugins: [clinicHotFilePlugin(env)],
    server: {
      host: env.VITE_LISTEN_HOST || '127.0.0.1',
      port: Number(env.VITE_DEV_PORT || 5173),
      strictPort: true,
      origin: publicUrl || undefined,
      allowedHosts: [publicHost, ...(publicUrl ? [new URL(publicUrl).hostname] : [])],
      cors: { origin: env.VITE_APP_ORIGIN || 'http://127.0.0.1:8000' },
      fs: {
        strict: true,
        allow: [root],
        deny: ['.env', '.env.*', '**/.git/**', '**/*.php', '**/*.sql', '**/config/**', '**/storage/**', '**/*.pem', '**/*.key'],
      },
      hmr: {
        host: env.VITE_HMR_HOST || publicHost,
        protocol: env.VITE_HMR_PROTOCOL || 'ws',
        // Browser-facing port; do not bind a separate TLS port behind a reverse proxy.
        clientPort: Number(env.VITE_HMR_PORT || env.VITE_DEV_PORT || 5173),
      },
    },
    build: {
      outDir: 'assets/build', emptyOutDir: true, manifest: true,
      rollupOptions: {
        input: Object.fromEntries(['app', 'auth', 'validar', 'site'].map(name => [name, path.join(root, `resources/entries/${name}.js`)])),
        output: {
          manualChunks(id) {
            if (id.includes('chart.js')) return 'chart-vendor';
            if (id.includes('/node_modules/')) return 'vendor';
          },
          entryFileNames: 'assets/[name]-[hash].js',
          chunkFileNames: 'assets/chunks/[name]-[hash].js',
          assetFileNames: 'assets/[name]-[hash][extname]',
        },
      },
    },
  };
});
