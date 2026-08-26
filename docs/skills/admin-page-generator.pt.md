# Gerador de Páginas de Administração

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

Gere páginas de administração Vue3 seguindo os padrões estabelecidos do projeto.

## Pilha de Tecnologias
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## Estrutura de Arquivos
```
admin/public/web/src/
├── api/{module}.ts          # Módulo da API Axios
├── views/{module}/          # Componentes de página
├── components/              # Componentes compartilhados
├── stores/                  # Stores Pinia
└── router/index.ts          # Definições de rotas
```

## Modelo de Módulo da API
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

## Modelo de Página de Listagem
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

## Regras

1. **Copyright**: Todo arquivo `.ts`/`.vue` começa com `Copyright (c) 2026 erik...`
2. **Cliente da API**: Use a instância `api` pré-configurada de `@/api/index` — ela desembrulha automaticamente o envelope `ApiResponse<T>`
3. **Exibição de dinheiro**: Use `formatFen()` de `@/utils/format` — todos os valores do backend estão em fen (分)
4. **Badges de plataforma**: Use o componente `<PlatformBadge :platform="row.platform" />`
5. **Cartões de métricas**: Use `<MetricCard>` com format='money'|'number'|'percent'
6. **Rotas**: Adicione em `router/index.ts` dentro do array `children`
7. **SideNav**: Adicione o item de menu em `components/layout/SideNav.vue`

## Adicionando uma Nova Página — Checklist Completo
1. Crie `api/{module}.ts`
2. Crie `views/{module}/{Page}.vue`
3. Adicione a rota em `router/index.ts`
4. Adicione o item de menu em `components/layout/SideNav.vue`
5. Execute `npx vue-tsc --noEmit` para verificar o TypeScript
