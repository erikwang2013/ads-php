# Admin Page Generator

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

プロジェクトの確立されたパターンに従って Vue3 管理バックエンドページを生成します。

## Tech Stack
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## File Structure
```
admin/public/web/src/
├── api/{module}.ts          # Axios API module
├── views/{module}/          # Page components
├── components/              # Shared components
├── stores/                  # Pinia stores
└── router/index.ts          # Route definitions
```

## API Module Template
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

## List Page Template
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

## Rules

1. **Copyright**: すべての `.ts`/`.vue` ファイルは `Copyright (c) 2026 erik...` で始まる
2. **API client**: `@/api/index` の設定済み `api` インスタンスを使用 — `ApiResponse<T>` ラッパーを自動的にアンラップする
3. **Money display**: `@/utils/format` の `formatFen()` を使用 — バックエンドの値はすべて分 (fen) 単位
4. **Platform badges**: `<PlatformBadge :platform="row.platform" />` コンポーネントを使用
5. **Metric cards**: `<MetricCard>` を format='money'|'number'|'percent' で使用
6. **Routes**: `router/index.ts` の `children` 配列に追加
7. **SideNav**: `components/layout/SideNav.vue` にメニュー項目を追加

## Adding a New Page — Full Checklist
1. `api/{module}.ts` を作成
2. `views/{module}/{Page}.vue` を作成
3. `router/index.ts` にルートを追加
4. `components/layout/SideNav.vue` にメニュー項目を追加
5. `npx vue-tsc --noEmit` を実行して TypeScript を検証
