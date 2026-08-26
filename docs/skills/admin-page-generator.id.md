# Admin Page Generator

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

Buat halaman admin Vue3 mengikuti pola yang sudah ditetapkan proyek.

## Tumpukan Teknologi
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## Struktur File
```
admin/public/web/src/
├── api/{module}.ts          # Axios API module
├── views/{module}/          # Page components
├── components/              # Shared components
├── stores/                  # Pinia stores
└── router/index.ts          # Route definitions
```

## Template Modul API
```typescript
// admin/public/web/src/api/example.ts
import { api } from './index'

export const exampleApi = {
  list(params?: any) { return api.get('/example', { params }) },
  create(data: any) { return api.post('/example', data) },
  update(id: number, data: any) { return api.put(`/example/${id}`, data) },
  destroy(id: number) { return api.delete(`/example/${id}`) },
}
```

## Template Halaman Daftar
```vue
<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { exampleApi } from '@/api/example'

const loading = ref(false)
const list = ref<any[]>([])
const pagination = reactive({ page: 1, perPage: 20, total: 0 })

async function fetchList() {
  loading.value = true
  const data = await exampleApi.list({ ...pagination })
  list.value = data.list; pagination.total = data.pagination.total
  loading.value = false
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除？', '提示', { type: 'warning' })
  await exampleApi.destroy(row.id); ElMessage.success('已删除'); fetchList()
}

onMounted(fetchList)
</script>
```

## Aturan

1. **Copyright**: Setiap file `.ts`/`.vue` diawali `Copyright (c) 2026 erik...`
2. **Klien API**: Gunakan instance `api` yang sudah dikonfigurasi dari `@/api/index` — otomatis membuka bungkus envelope `ApiResponse<T>`
3. **Tampilan uang**: Gunakan `formatFen()` dari `@/utils/format` — semua nilai backend dalam sen (分)
4. **Badge platform**: Gunakan komponen `<PlatformBadge :platform="row.platform" />`
5. **Kartu metrik**: Gunakan `<MetricCard>` dengan format='money'|'number'|'percent'
6. **Rute**: Tambahkan ke `router/index.ts` di dalam array `children`
7. **SideNav**: Tambahkan item menu di `components/layout/SideNav.vue`

## Menambahkan Halaman Baru — Checklist Lengkap
1. Buat `api/{module}.ts`
2. Buat `views/{module}/{Page}.vue`
3. Tambahkan rute di `router/index.ts`
4. Tambahkan item menu di `components/layout/SideNav.vue`
5. Jalankan `npx vue-tsc --noEmit` untuk memverifikasi TypeScript
