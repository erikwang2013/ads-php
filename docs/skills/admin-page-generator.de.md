# Admin-Seiten-Generator

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

Vue3-Adminseiten gemäß den etablierten Mustern des Projekts generieren.

## Technologie-Stack
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## Dateistruktur
```
admin/public/web/src/
├── api/{module}.ts          # Axios API module
├── views/{module}/          # Page components
├── components/              # Shared components
├── stores/                  # Pinia stores
└── router/index.ts          # Route definitions
```

## API-Modul-Vorlage
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

## Listenseiten-Vorlage
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

## Regeln

1. **Copyright**: Jede `.ts`/`.vue`-Datei beginnt mit `Copyright (c) 2026 erik...`
2. **API-Client**: Die vorkonfigurierte `api`-Instanz aus `@/api/index` verwenden — sie entpackt den `ApiResponse<T>`-Envelope automatisch
3. **Geldanzeige**: `formatFen()` aus `@/utils/format` verwenden — alle Backend-Werte sind in Fen (分)
4. **Plattform-Badges**: Die Komponente `<PlatformBadge :platform="row.platform" />` verwenden
5. **Kennzahlen-Karten**: `<MetricCard>` mit format='money'|'number'|'percent' verwenden
6. **Routen**: In `router/index.ts` innerhalb des `children`-Arrays hinzufügen
7. **Seitennavigation**: Menüpunkt in `components/layout/SideNav.vue` hinzufügen

## Neue Seite hinzufügen — Vollständige Checkliste
1. `api/{module}.ts` erstellen
2. `views/{module}/{Page}.vue` erstellen
3. Route in `router/index.ts` hinzufügen
4. Menüpunkt in `components/layout/SideNav.vue` hinzufügen
5. `npx vue-tsc --noEmit` ausführen, um TypeScript zu verifizieren
