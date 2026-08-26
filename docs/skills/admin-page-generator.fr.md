# Admin Page Generator

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

Générer des pages de back-office Vue3 en suivant les conventions établies du projet.

## Pile technologique
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## Structure des fichiers
```
admin/public/web/src/
├── api/{module}.ts          # Module API Axios
├── views/{module}/          # Composants de page
├── components/              # Composants partagés
├── stores/                  # Stores Pinia
└── router/index.ts          # Définitions de routes
```

## Modèle de module API
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

## Modèle de page de liste
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

## Règles

1. **Copyright** : Chaque fichier `.ts`/`.vue` commence par `Copyright (c) 2026 erik...`
2. **Client API** : Utiliser l'instance `api` préconfigurée de `@/api/index` — elle déballe automatiquement l'enveloppe `ApiResponse<T>`
3. **Affichage des montants** : Utiliser `formatFen()` de `@/utils/format` — toutes les valeurs backend sont en fen (分)
4. **Badges de plateformes** : Utiliser le composant `<PlatformBadge :platform="row.platform" />`
5. **Cartes de métriques** : Utiliser `<MetricCard>` avec format='money'|'number'|'percent'
6. **Routes** : Ajouter dans `router/index.ts` dans le tableau `children`
7. **SideNav** : Ajouter un élément de menu dans `components/layout/SideNav.vue`

## Ajout d'une nouvelle page — Liste de contrôle complète
1. Créer `api/{module}.ts`
2. Créer `views/{module}/{Page}.vue`
3. Ajouter la route dans `router/index.ts`
4. Ajouter l'élément de menu dans `components/layout/SideNav.vue`
5. Exécuter `npx vue-tsc --noEmit` pour vérifier TypeScript
