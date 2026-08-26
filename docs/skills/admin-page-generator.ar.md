# مولّد صفحات الإدارة (Admin Page Generator)

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

توليد صفحات إدارة Vue3 وفقًا للأنماط المعتمدة في المشروع.

## التقنيات
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## هيكل الملفات
```
admin/public/web/src/
├── api/{module}.ts          # Axios API module
├── views/{module}/          # Page components
├── components/              # Shared components
├── stores/                  # Pinia stores
└── router/index.ts          # Route definitions
```

## قالب وحدة API
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

## قالب صفحة القائمة
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

## القواعد

1. **حقوق النشر**: يبدأ كل ملف `.ts`/`.vue` بـ `Copyright (c) 2026 erik...`
2. **عميل API**: استخدام مثيل `api` المُعد مسبقًا من `@/api/index` — فهو يفك تلقائيًا مغلف `ApiResponse<T>`
3. **عرض المال**: استخدام `formatFen()` من `@/utils/format` — جميع قيم الواجهة الخلفية بالفين (分)
4. **شارات المنصات**: استخدام مكوّن `<PlatformBadge :platform="row.platform" />`
5. **بطاقات المقاييس**: استخدام `<MetricCard>` مع format='money'|'number'|'percent'
6. **المسارات**: الإضافة إلى `router/index.ts` داخل مصفوفة `children`
7. **القائمة الجانبية**: إضافة عنصر القائمة في `components/layout/SideNav.vue`

## إضافة صفحة جديدة — قائمة تحقق كاملة
1. إنشاء `api/{module}.ts`
2. إنشاء `views/{module}/{Page}.vue`
3. إضافة مسار في `router/index.ts`
4. إضافة عنصر قائمة في `components/layout/SideNav.vue`
5. تشغيل `npx vue-tsc --noEmit` للتحقق من TypeScript
