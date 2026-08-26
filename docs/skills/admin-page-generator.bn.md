# Admin Page Generator

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

প্রজেক্টের প্রতিষ্ঠিত প্যাটার্ন অনুসরণ করে Vue3 অ্যাডমিন পেজ তৈরি করুন।

## টেক স্ট্যাক
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## ফাইল স্ট্রাকচার
```
admin/public/web/src/
├── api/{module}.ts          # Axios API module
├── views/{module}/          # Page components
├── components/              # Shared components
├── stores/                  # Pinia stores
└── router/index.ts          # Route definitions
```

## API মডিউল টেমপ্লেট
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

## লিস্ট পেজ টেমপ্লেট
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

## নিয়ম

1. **Copyright**: প্রতিটি `.ts`/`.vue` ফাইল `Copyright (c) 2026 erik...` দিয়ে শুরু হয়
2. **API ক্লায়েন্ট**: `@/api/index` থেকে প্রি-কনফিগারড `api` ইনস্ট্যান্স ব্যবহার করুন — এটি `ApiResponse<T>` এনভেলপ অটো আনর্যাপ করে
3. **টাকা ডিসপ্লে**: `@/utils/format` থেকে `formatFen()` ব্যবহার করুন — সব ব্যাকএন্ড ভ্যালু ফেন (分)-এ
4. **প্ল্যাটফর্ম ব্যাজ**: `<PlatformBadge :platform="row.platform" />` কম্পোনেন্ট ব্যবহার করুন
5. **মেট্রিক কার্ড**: `<MetricCard>` ব্যবহার করুন format='money'|'number'|'percent' সহ
6. **রাউট**: `router/index.ts`-এর `children` অ্যারের মধ্যে যোগ করুন
7. **SideNav**: `components/layout/SideNav.vue`-এ মেনু আইটেম যোগ করুন

## নতুন পেজ যোগ — সম্পূর্ণ চেকলিস্ট
1. `api/{module}.ts` তৈরি করুন
2. `views/{module}/{Page}.vue` তৈরি করুন
3. `router/index.ts`-এ রাউট যোগ করুন
4. `components/layout/SideNav.vue`-এ মেনু আইটেম যোগ করুন
5. TypeScript ভেরিফাই করতে `npx vue-tsc --noEmit` চালান
