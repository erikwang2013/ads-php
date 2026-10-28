import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      // service 静态资源与安装页：路由不带 /api 前缀，需单独转发到 8788
      '/uploads': {
        target: 'http://127.0.0.1:8788',
        changeOrigin: true,
      },
      '/install': {
        target: 'http://127.0.0.1:8788',
        changeOrigin: true,
      },
      // service API 后端（:8788）
      '/api': {
        target: 'http://127.0.0.1:8788',
        changeOrigin: true,
      },
      // service 健康检查：/health 路由在 service 侧不带 /api 前缀，需单独转发到 8788
      '/health': {
        target: 'http://127.0.0.1:8788',
        changeOrigin: true,
      },
    },
  },
})
