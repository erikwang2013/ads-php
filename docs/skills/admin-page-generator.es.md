# Admin Page Generator

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

Genera páginas de administración Vue3 siguiendo los patrones establecidos del proyecto.

## Pila tecnológica
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## Estructura de archivos
```
admin/public/web/src/
├── api/{module}.ts          # Módulo de API Axios
├── views/{module}/          # Componentes de página
├── components/              # Componentes compartidos
├── stores/                  # Pinia stores
└── router/index.ts          # Definiciones de rutas
```

## Plantilla de módulo de API
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

## Plantilla de página de lista
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

## Reglas

1. **Copyright**: Todo archivo `.ts`/`.vue` comienza con `Copyright (c) 2026 erik...`
2. **Cliente de API**: Usa la instancia `api` preconfigurada de `@/api/index` — desempaqueta automáticamente el envoltorio `ApiResponse<T>`
3. **Visualización de dinero**: Usa `formatFen()` de `@/utils/format` — todos los valores del backend están en fen (分)
4. **Insignias de plataforma**: Usa el componente `<PlatformBadge :platform="row.platform" />`
5. **Tarjetas de métricas**: Usa `<MetricCard>` con format='money'|'number'|'percent'
6. **Rutas**: Añade a `router/index.ts` dentro del array `children`
7. **SideNav**: Añade el elemento de menú en `components/layout/SideNav.vue`

## Añadir una página nueva — Lista de verificación completa
1. Crea `api/{module}.ts`
2. Crea `views/{module}/{Page}.vue`
3. Añade la ruta en `router/index.ts`
4. Añade el elemento de menú en `components/layout/SideNav.vue`
5. Ejecuta `npx vue-tsc --noEmit` para verificar TypeScript
