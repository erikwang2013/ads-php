# Admin Page Generator

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

Генерация страниц админ-панели на Vue3 по установленным паттернам проекта.

## Технологический стек
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## Структура файлов
```
admin/public/web/src/
├── api/{module}.ts          # Axios API module
├── views/{module}/          # Page components
├── components/              # Shared components
├── stores/                  # Pinia stores
└── router/index.ts          # Route definitions
```

## Шаблон API-модуля
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

## Шаблон страницы со списком
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

## Правила

1. **Copyright**: каждый файл `.ts`/`.vue` начинается с `Copyright (c) 2026 erik...`
2. **API-клиент**: используйте предварительно настроенный экземпляр `api` из `@/api/index` — он автоматически разворачивает обертку `ApiResponse<T>`
3. **Отображение денег**: используйте `formatFen()` из `@/utils/format` — все значения бэкенда в фэнях (分)
4. **Бейджи платформ**: используйте компонент `<PlatformBadge :platform="row.platform" />`
5. **Карточки метрик**: используйте `<MetricCard>` с format='money'|'number'|'percent'
6. **Маршруты**: добавляйте в `router/index.ts` внутри массива `children`
7. **SideNav**: добавляйте пункт меню в `components/layout/SideNav.vue`

## Добавление новой страницы — полный чек-лист
1. Создайте `api/{module}.ts`
2. Создайте `views/{module}/{Page}.vue`
3. Добавьте маршрут в `router/index.ts`
4. Добавьте пункт меню в `components/layout/SideNav.vue`
5. Выполните `npx vue-tsc --noEmit` для проверки TypeScript
