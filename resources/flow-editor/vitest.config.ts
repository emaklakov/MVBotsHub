import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

const __dirname = dirname(fileURLToPath(import.meta.url))

export default defineConfig({
    // Плагин Vue нужен здесь (а не только в vite.config.ts), потому что
    // с Фазы 0 реестр блоков (src/blocks/registry.ts) импортирует сами
    // .vue-компоненты — раньше composable-тесты .vue-файлов не касались
    // вовсе, поэтому плагина не требовалось.
    plugins: [vue()],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'src'),
        },
    },
    test: {
        environment: 'node',
        include: ['src/**/*.test.ts'],
    },
})
